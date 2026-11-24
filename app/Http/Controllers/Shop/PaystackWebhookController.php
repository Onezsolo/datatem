<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaystackWebhookController
{
    public function __invoke(Request $request, PaystackService $paystack): JsonResponse
    {
        if (! $paystack->signatureValid($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        }

        try {
            $paystack->processWebhook($payload);

            return response()->json(['status' => 'ok']);
        } catch (Throwable $exception) {
            Log::error('Paystack webhook processing failed.', [
                'event' => $payload['event'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook processing failed.'], 422);
        }
    }
}
