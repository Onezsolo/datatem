<?php

declare(strict_types=1);

return [

    'name' => env('APP_NAME', 'Datatem'),

    'currency' => 'GHS',

    'support_email' => env('STORE_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'orders@datatem.local')),

    'support_phone' => env('STORE_SUPPORT_PHONE', ''),

    'payment_pending_minutes' => (int) env('ORDER_PAYMENT_TTL', 60),

    'low_stock_threshold' => 5,

];
