<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Http\Requests\Admin\FinanceIndexRequest;
use App\Shop\Models\Order;
use App\Shop\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class FinanceService
{
    /**
     * @return array<int, Order>
     */
    public function orders(FinanceIndexRequest $request): array
    {
        $orders = [];

        foreach ($this->ordersQuery($request)->get() as $order) {
            if ($order instanceof Order) {
                $orders[] = $order;
            }
        }

        return $orders;
    }

    public function ordersQuery(FinanceIndexRequest $request): Builder
    {
        $query = Order::query()->with(['payments'])->latest();

        return $this->applyOrderFilters($query, $request);
    }

    public function paymentsQuery(FinanceIndexRequest $request): Builder
    {
        return Payment::query()
            ->with('order')
            ->whereHas('order', fn (Builder $query) => $this->applyOrderFilters($query, $request))
            ->latest();
    }

    public function applyOrderFilters(Builder $query, FinanceIndexRequest $request): Builder
    {
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim()->toString().'%';
            $query->where(function (Builder $query) use ($term): void {
                $query->where('order_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term)
                    ->orWhereHas('payments', fn (Builder $query) => $query
                        ->where('reference', 'like', $term)
                        ->orWhere('provider_reference', 'like', $term));
            });
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse((string) $request->input('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse((string) $request->input('date_to'))->endOfDay());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        return $query;
    }

    /**
     * @return array<string, float|int>
     */
    public function summary(Builder $query): array
    {
        $active = (clone $query)->where('status', '!=', Order::STATUS_CANCELLED);
        $collected = (clone $active)->where('payment_status', 'paid');
        $refunded = (clone $query)->where('payment_status', 'refunded');
        $outstanding = (clone $active)
            ->whereIn('payment_status', ['pending', 'failed'])
            ->whereNotIn('status', [Order::STATUS_REFUNDED]);

        return [
            'orders' => (clone $query)->count(),
            'gross_sales' => (float) (clone $active)->sum('total'),
            'subtotal' => (float) (clone $active)->sum('subtotal'),
            'delivery_fees' => (float) (clone $active)->sum('delivery_fee'),
            'service_charges' => (float) (clone $active)->sum('service_charge'),
            'discounts' => (float) (clone $active)->sum('discount'),
            'collected' => (float) $collected->sum('total'),
            'refunded' => (float) $refunded->sum('total'),
            'outstanding' => (float) $outstanding->sum('total'),
            'net_revenue' => (float) $collected->sum('total'),
        ];
    }

    public function netRetained(Order $order): float
    {
        return $order->payment_status === 'paid' ? (float) $order->total : 0.0;
    }

    public function refundedAmount(Order $order): float
    {
        return $order->payment_status === 'refunded' ? (float) $order->total : 0.0;
    }

    public function csvCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[=+\-@]/', $cell) === 1 ? "'".$cell : $cell;
    }
}
