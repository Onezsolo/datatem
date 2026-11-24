<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatupaaCatalogItem;
use App\Services\Patupaa\PatupaaCatalogSync;
use App\Services\Patupaa\PatupaaClient;
use App\Services\Patupaa\PatupaaException;
use App\Services\Patupaa\PatupaaSettings;
use App\Models\ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatupaaServicesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = PatupaaCatalogItem::query()
            ->when($request->filled('type'), fn ($query) => $query->where('remote_type', $request->string('type')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('plan_code', 'like', $term));
            })
            ->orderBy('remote_type')
            ->orderBy('name')
            ->paginate(100);

        $counts = PatupaaCatalogItem::query()
            ->selectRaw('remote_type, count(*) as total, sum(case when remote_enabled = 1 and admin_enabled = 1 then 1 else 0 end) as available')
            ->groupBy('remote_type')
            ->get();

        $currentUser = auth('api')->user() ?? $request->user();

        $providers = $currentUser?->isDeveloper()
            ? ServiceProvider::query()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (ServiceProvider $provider): array => [
                'id' => $provider->id,
                'name' => $provider->name,
                'driver' => $provider->driver,
                'base_url' => $provider->base_url,
                'services' => $provider->services,
                'is_active' => $provider->is_active,
                'is_default' => $provider->is_default,
                'sort_order' => $provider->sort_order,
                'masked_key' => $provider->maskedKey(),
                'last_synced_at' => $provider->last_synced_at?->toIso8601String(),
                'last_sync_error' => $provider->last_sync_error,
            ])
            : collect();

        return response()->json([
            'providers' => $providers,
            'items' => $items,
            'counts' => $counts,
            'toggles' => PatupaaSettings::toggles(),
            'types' => PatupaaSettings::TYPES,
            'configured' => app(PatupaaClient::class)->configured(),
            'afa_price' => PatupaaSettings::afaPrice(),
        ]);
    }

    public function updateItem(Request $request, PatupaaCatalogItem $item): JsonResponse
    {
        $data = $request->validate([
            'admin_enabled' => ['required', 'boolean'],
        ]);

        $item->update(['admin_enabled' => (bool) $data['admin_enabled']]);

        return response()->json(['item' => $item->refresh()]);
    }

    public function sync(Request $request, PatupaaCatalogSync $sync): JsonResponse
    {
        $provider = $request->filled('provider_id')
            ? ServiceProvider::query()->findOrFail($request->integer('provider_id'))
            : ServiceProvider::ensureDefault();

        return $this->syncProvider($provider, $sync);
    }

    public function syncProvider(ServiceProvider $provider, PatupaaCatalogSync $sync): JsonResponse
    {
        try {
            $summary = $sync->sync($provider);

            return response()->json(['summary' => $summary, 'provider_id' => $provider->id]);
        } catch (PatupaaException $exception) {
            $provider->update(['last_sync_error' => str_ireplace(['patupaa', 'patupa'], 'provider', $exception->getMessage())]);
            return response()->json([
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => str_ireplace(['patupaa', 'patupa'], 'provider', $exception->getMessage()),
                ],
            ], 422);
        }
    }

    public function storeProvider(Request $request): JsonResponse
    {
        $data = $this->validateProvider($request);
        $data['services'] = $data['services'] ?? array_keys(PatupaaSettings::TYPES);

        $provider = ServiceProvider::create($data);

        if (! ServiceProvider::query()->where('is_default', true)->exists() || ($data['is_default'] ?? false)) {
            ServiceProvider::makeDefault($provider);
        }

        return response()->json(['provider' => $provider->refresh()], 201);
    }

    public function updateProvider(Request $request, ServiceProvider $provider): JsonResponse
    {
        $data = $this->validateProvider($request);

        if (($data['api_key'] ?? '') === '') {
            unset($data['api_key']);
        }

        if (($data['api_secret'] ?? '') === '') {
            unset($data['api_secret']);
        }

        $provider->update($data);

        if (! empty($data['is_default'])) {
            ServiceProvider::makeDefault($provider);
        } elseif ($provider->is_default && ! $provider->is_active) {
            $replacement = ServiceProvider::query()->where('is_active', true)->whereKeyNot($provider->id)->orderBy('sort_order')->first();

            if ($replacement !== null) {
                ServiceProvider::makeDefault($replacement);
            }
        }

        return response()->json(['provider' => $provider->refresh()]);
    }

    public function destroyProvider(ServiceProvider $provider): JsonResponse
    {
        if (ServiceProvider::query()->count() <= 1) {
            return response()->json(['error' => 'At least one provider must remain.'], 422);
        }

        $wasDefault = $provider->is_default;
        $provider->catalogItems()->delete();
        $provider->delete();

        if ($wasDefault) {
            $replacement = ServiceProvider::query()->where('is_active', true)->orderBy('sort_order')->first();

            if ($replacement !== null) {
                ServiceProvider::makeDefault($replacement);
            }
        }

        return response()->json(['deleted' => true]);
    }

    public function makeDefaultProvider(ServiceProvider $provider): JsonResponse
    {
        ServiceProvider::makeDefault($provider);

        return response()->json(['provider' => $provider->refresh()]);
    }

    public function updateToggles(Request $request): JsonResponse
    {
        $data = $request->validate([
            'toggles' => ['required', 'array'],
            'toggles.*' => ['boolean'],
            'afa_price' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        if (array_key_exists('afa_price', $data)) {
            PatupaaSettings::setAfaPrice((float) $data['afa_price']);
        }

        return response()->json([
            'toggles' => PatupaaSettings::setToggles($data['toggles']),
            'afa_price' => PatupaaSettings::afaPrice(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProvider(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'driver' => ['nullable', 'string', Rule::in(['patupaa'])],
            'base_url' => ['required', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'api_secret' => ['nullable', 'string', 'max:500'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', Rule::in(array_keys(PatupaaSettings::TYPES))],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }
}
