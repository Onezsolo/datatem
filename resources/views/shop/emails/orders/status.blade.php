<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order status update</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f4;font-family:Arial,sans-serif;color:#0b0b0b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e4e8;border-radius:12px;padding:24px;">
        <p style="margin:0 0 8px;color:#A92D38;font-size:12px;font-weight:700;">{{ \App\Support\Brand::name() }}</p>
        <h1 style="margin:0 0 12px;color:#0A2038;font-size:22px;">Order update</h1>
        <p style="margin:0 0 12px;">Hello {{ $order->customer_name }}, order {{ $order->order_number }} is now <strong>{{ $order->status }}</strong>.</p>
        <p style="margin:0 0 12px;"><strong>Total:</strong> {{ $order->currency }} {{ number_format((float) $order->total, 2) }}</p>
        <p style="margin:0;color:#5a6270;font-size:13px;">If you have questions, contact {{ config('store.support_email') }}.</p>
    </div>
</body>
</html>
