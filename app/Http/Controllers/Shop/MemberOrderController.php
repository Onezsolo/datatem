<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class MemberOrderController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $phone = trim((string) ($user->phone ?? ''));
        $email = trim((string) ($user->email ?? ''));

        $orders = Order::query()
            ->when($phone !== '' || $email !== '', function ($query) use ($phone, $email) {
                $query->where(function ($query) use ($phone, $email) {
                    if ($phone !== '') {
                        $query->where('customer_phone', $phone);
                    }

                    if ($email !== '') {
                        $method = $phone !== '' ? 'orWhere' : 'where';
                        $query->{$method}('customer_email', $email);
                    }
                });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => (float) $order->total,
                'currency' => $order->currency,
                'item_count' => (int) $order->items()->sum('quantity'),
                'placed_at' => $order->created_at?->toIso8601String(),
                'view_url' => URL::temporarySignedRoute('shop.orders.show', now()->addDay(), [
                    'order' => $order->order_number,
                ]),
            ]);

        return response()->json(['orders' => $orders]);
    }
}
