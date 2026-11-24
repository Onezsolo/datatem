<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Order;
use Illuminate\Contracts\View\View;

class OrderController
{
    public function show(Order $order): View
    {
        $order->load(['items', 'payments']);

        return view('shop.order', compact('order'));
    }
}
