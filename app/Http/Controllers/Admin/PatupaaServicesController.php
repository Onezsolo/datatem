<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatupaaCatalogItem;
use App\Services\Patupaa\PatupaaCatalogSync;
use App\Services\Patupaa\PatupaaClient;
use App\Services\Patupaa\PatupaaException;
use App\Services\Patupaa\PatupaaSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return response()->json([
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

    public function sync(PatupaaCatalogSync $sync): JsonResponse
    {
        try {
            return response()->json(['summary' => $sync->sync()]);
        } catch (PatupaaException $exception) {
            return response()->json([
                'error' => [
                    'code' => $exception->errorCode(),
                    'message' => $exception->getMessage(),
                ],
            ], 422);
        }
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
}
