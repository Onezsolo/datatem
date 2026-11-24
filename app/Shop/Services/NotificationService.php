<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Mail\OrderPaidMail;
use App\Shop\Mail\OrderStatusMail;
use App\Shop\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function orderPaid(Order $order): void
    {
        if (! $order->customer_email) {
            Log::info('Order paid notification skipped: no customer email.', ['order' => $order->order_number]);

            return;
        }

        Mail::to($order->customer_email)->send(new OrderPaidMail($order));
    }

    public function orderStatus(Order $order): void
    {
        if (! $order->customer_email) {
            Log::info('Order status notification skipped: no customer email.', [
                'order' => $order->order_number,
                'status' => $order->status,
            ]);

            return;
        }

        if (! in_array($order->status, [
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ], true)) {
            return;
        }

        Mail::to($order->customer_email)->send(new OrderStatusMail($order));
    }
}
