<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Models\Admin;
use App\Shop\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        Order::STATUS_PENDING => [Order::STATUS_CANCELLED],
        Order::STATUS_PAID => [Order::STATUS_PROCESSING],
        Order::STATUS_PROCESSING => [Order::STATUS_SHIPPED],
        Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED],
        Order::STATUS_DELIVERED => [],
        Order::STATUS_CANCELLED => [],
        Order::STATUS_REFUNDED => [],
    ];

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(Order $order): array
    {
        return self::TRANSITIONS[$order->status] ?? [];
    }

    public function transition(Order $order, string $status, ?Admin $admin = null, ?string $note = null): Order
    {
        if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot move from '.$order->status.' to '.$status.'.',
            ]);
        }

        $previous = $order->status;

        DB::transaction(function () use ($order, $status, $admin, $note, $previous): void {
            $order->refresh();

            if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'The order status changed. Refresh and try again.']);
            }

            if ($status === Order::STATUS_CANCELLED) {
                app(InventoryService::class)->release($order);
                $order->cancel($note);
            }

            if ($status === Order::STATUS_PROCESSING) {
                $order->markAsProcessing();
            }

            if ($status === Order::STATUS_SHIPPED) {
                $order->markAsShipped();
            }

            if ($status === Order::STATUS_DELIVERED) {
                $order->markAsDelivered();
            }

            if ($note !== null && $note !== '') {
                $order->notes()->create([
                    'admin_id' => $admin?->id,
                    'note' => $note,
                ]);
            }

            app(AuditService::class)->record(
                $admin,
                'order.status_changed',
                ['from' => $previous, 'to' => $status, 'note' => $note],
                $order
            );
        });

        $order->refresh();
        app(NotificationService::class)->orderStatus($order);

        return $order;
    }

    public function expirePending(int $minutes): int
    {
        $orders = Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            $this->transition($order, Order::STATUS_CANCELLED, null, 'Payment window expired.');
            $count++;
        }

        return $count;
    }
}
