<?php

declare(strict_types=1);

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use App\Models\PatupaaCatalogItem;
use App\Models\ServiceOrder;
use App\Services\Patupaa\PatupaaClient;
use App\Services\Patupaa\PatupaaException;
use App\Services\Patupaa\PatupaaOrderService;
use App\Services\Patupaa\PatupaaSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function hub(): View
    {
        $counts = PatupaaCatalogItem::query()
            ->available()
            ->selectRaw('remote_type, count(*) as total')
            ->groupBy('remote_type')
            ->pluck('total', 'remote_type');

        return view('services.hub', [
            'counts' => $counts,
            'toggles' => PatupaaSettings::toggles(),
            'afaPrice' => PatupaaSettings::afaPrice(),
        ]);
    }

    public function data(): View
    {
        return view('services.data', [
            'items' => $this->items(PatupaaCatalogItem::TYPE_DATA),
            'wallet' => (float) (auth()->user()?->balance ?? 0),
        ]);
    }

    public function waec(): View
    {
        return view('services.waec', [
            'items' => $this->items(PatupaaCatalogItem::TYPE_WAEC),
            'wallet' => (float) (auth()->user()?->balance ?? 0),
        ]);
    }

    public function utility(): View
    {
        return view('services.utility', [
            'items' => $this->items(PatupaaCatalogItem::TYPE_UTILITY),
            'wallet' => (float) (auth()->user()?->balance ?? 0),
        ]);
    }

    public function subscriptions(): View
    {
        return view('services.subscriptions', [
            'items' => $this->items(PatupaaCatalogItem::TYPE_DIGITAL),
            'wallet' => (float) (auth()->user()?->balance ?? 0),
        ]);
    }

    public function afa(): View
    {
        if (! PatupaaSettings::enabled('afa') || PatupaaSettings::afaPrice() <= 0) {
            return view('services.afa', ['available' => false, 'price' => PatupaaSettings::afaPrice()]);
        }

        return view('services.afa', ['available' => true, 'price' => PatupaaSettings::afaPrice()]);
    }

    public function storeData(Request $request, PatupaaCatalogItem $item, PatupaaOrderService $orders): RedirectResponse
    {
        $this->ensureItem($item, PatupaaCatalogItem::TYPE_DATA);

        $data = $request->validate([
            'beneficiary' => ['required', 'string', 'regex:/^(?:\+233|0)[235]\d{8}$/'],
        ]);

        $payload = [
            'type' => 'data_bundle',
            'plan_code' => $item->plan_code ?: $item->remote_id,
            'beneficiary' => $this->normalisePhone($data['beneficiary']),
            'quantity' => 1,
        ];

        return $this->place(fn () => $orders->purchase($request->user(), $item, $payload, (float) $item->price));
    }

    public function storeWaec(Request $request, PatupaaCatalogItem $item, PatupaaOrderService $orders): RedirectResponse
    {
        $this->ensureItem($item, PatupaaCatalogItem::TYPE_WAEC);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $payload = [
            'type' => 'waec',
            'product_id' => (int) $item->remote_id,
            'quantity' => (int) $data['quantity'],
        ];

        $amount = round((float) $item->price * (int) $data['quantity'], 2);

        return $this->place(fn () => $orders->purchase($request->user(), $item, $payload, $amount));
    }

    public function storeUtility(Request $request, PatupaaCatalogItem $item, PatupaaOrderService $orders): RedirectResponse
    {
        $this->ensureItem($item, PatupaaCatalogItem::TYPE_UTILITY);

        $data = $request->validate([
            'beneficiary' => ['required', 'string', 'min:4', 'max:60'],
            'amount' => ['required', 'numeric', 'min:1', 'max:5000'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'type' => 'utility',
            'service_key' => $item->remote_id,
            'beneficiary' => $data['beneficiary'],
            'amount' => number_format((float) $data['amount'], 2, '.', ''),
            'customer_name' => $data['customer_name'] ?? null,
            'address' => $data['address'] ?? null,
        ];

        return $this->place(fn () => $orders->purchase($request->user(), $item, $payload, (float) $data['amount']));
    }

    public function storeSubscriptions(Request $request, PatupaaCatalogItem $item, PatupaaOrderService $orders): RedirectResponse
    {
        $this->ensureItem($item, PatupaaCatalogItem::TYPE_DIGITAL);

        $gift = is_array($item->meta['gift_card'] ?? null) ? $item->meta['gift_card'] : null;
        $regionCode = $gift['region_code'] ?? null;

        $rules = [
            'recipient_email' => ['required', 'email', 'max:255'],
        ];

        if ($regionCode !== null) {
            $rules['region_confirmed'] = ['accepted'];
            $rules['terms_accepted'] = ['accepted'];
        }

        $data = $request->validate($rules);

        $payload = [
            'type' => 'digital',
            'product_id' => (int) $item->remote_id,
            'quantity' => 1,
            'recipient_email' => $data['recipient_email'],
            'beneficiary' => $data['recipient_email'],
        ];

        if ($regionCode !== null) {
            $payload['account_region'] = (string) $regionCode;
            $payload['region_confirmed'] = true;
            $payload['terms_accepted'] = true;
        }

        return $this->place(fn () => $orders->purchase($request->user(), $item, $payload, (float) $item->price));
    }

    public function storeAfa(Request $request, PatupaaOrderService $orders): RedirectResponse
    {
        if (! PatupaaSettings::enabled('afa') || PatupaaSettings::afaPrice() <= 0) {
            return redirect()->route('services.index')->with('warning', 'AFA registration is currently unavailable.');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(?:\+233|0)[235]\d{8}$/'],
            'ghana_card' => ['required', 'string', 'max:20'],
            'town' => ['required', 'string', 'max:120'],
        ]);

        $payload = [
            'full_name' => $data['full_name'],
            'phone' => $this->normalisePhone($data['phone']),
            'ghana_card' => strtoupper(trim($data['ghana_card'])),
            'town' => $data['town'],
        ];

        return $this->place(fn () => $orders->purchaseAfa($request->user(), $payload, PatupaaSettings::afaPrice()));
    }

    public function lookupUtility(Request $request, PatupaaClient $client): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:patupaa_catalog_items,id'],
            'customer_number' => ['required', 'string', 'min:4', 'max:60'],
        ]);

        $item = PatupaaCatalogItem::query()->available()->ofType(PatupaaCatalogItem::TYPE_UTILITY)->findOrFail($data['item_id']);

        if (! PatupaaSettings::enabled(PatupaaCatalogItem::TYPE_UTILITY)) {
            return response()->json(['error' => 'Utility service is unavailable.'], 422);
        }

        try {
            $result = $client->post('/utilities/lookup', [
                'service_key' => $item->remote_id,
                'customer_number' => $data['customer_number'],
            ]);

            return response()->json(['lookup' => $result]);
        } catch (PatupaaException $exception) {
            return response()->json(['error' => $this->publicMessage($exception->getMessage())], 422);
        }
    }

    public function orders(): View
    {
        $orders = ServiceOrder::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->paginate(20);

        return view('services.orders', ['orders' => $orders]);
    }

    public function orderShow(string $reference): View
    {
        $order = ServiceOrder::query()
            ->where('user_id', auth()->id())
            ->where('reference', $reference)
            ->firstOrFail();

        return view('services.order', ['order' => $order]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PatupaaCatalogItem>
     */
    private function items(string $type)
    {
        if (! PatupaaSettings::enabled($type)) {
            abort(404);
        }

        return PatupaaCatalogItem::query()
            ->available()
            ->ofType($type)
            ->orderBy('network')
            ->orderBy('price')
            ->orderBy('name')
            ->get();
    }

    private function ensureItem(PatupaaCatalogItem $item, string $type): void
    {
        abort_unless($item->remote_type === $type, 404);
        abort_unless(PatupaaSettings::enabled($type), 404);
        abort_unless($item->isAvailable(), 404);
    }

    private function normalisePhone(string $phone): string
    {
        return preg_replace('/[\s\-]+/', '', trim($phone)) ?? '';
    }

    private function publicMessage(string $message): string
    {
        return str_ireplace(['patupaa', 'patupa'], 'provider', $message);
    }

    private function place(callable $callback): RedirectResponse
    {
        try {
            /** @var ServiceOrder $order */
            $order = $callback();

            return redirect()
                ->route('services.orders.show', $order->reference)
                ->with('status', 'Order '.$order->reference.' submitted.');
        } catch (PatupaaException $exception) {
            return back()->withInput()->with('warning', $this->publicMessage($exception->getMessage()));
        }
    }
}
