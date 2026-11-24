<?php

declare(strict_types=1);

namespace App\Services\Patupaa;

use App\Models\PatupaaCatalogItem;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PatupaaOrderService
{
    public function __construct(private readonly PatupaaClient $client)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function purchase(User $user, PatupaaCatalogItem $item, array $payload, float $amount): ServiceOrder
    {
        if (! PatupaaSettings::enabled($item->remote_type)) {
            throw ValidationException::withMessages(['service' => 'This service is currently unavailable.']);
        }

        if (! $item->isAvailable()) {
            throw ValidationException::withMessages(['service' => 'This item is currently unavailable.']);
        }

        if ($item->remote_type === PatupaaCatalogItem::TYPE_UTILITY) {
            $fee = (float) ($item->meta['fee'] ?? 0);
            $amount += $fee;
            $min = $item->meta['min_amount'] !== null ? (float) $item->meta['min_amount'] : null;
            $max = $item->meta['max_amount'] !== null ? (float) $item->meta['max_amount'] : null;
            $bill = (float) ($payload['amount'] ?? 0);

            if ($min !== null && $bill < $min) {
                throw ValidationException::withMessages(['amount' => 'Minimum amount is GHS '.number_format($min, 2).'.']);
            }

            if ($max !== null && $bill > $max) {
                throw ValidationException::withMessages(['amount' => 'Maximum amount is GHS '.number_format($max, 2).'.']);
            }
        }

        if (! $this->client->configured()) {
            throw new PatupaaException('PATUPAA_NOT_CONFIGURED', 'PATUPAA API credentials are not configured.');
        }

        $reference = 'SVC-'.Str::upper(Str::random(10));

        $order = DB::transaction(function () use ($user, $item, $payload, $amount, $reference): ServiceOrder {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $balance = (float) $locked->balance;

            if (round($balance, 2) < round($amount, 2)) {
                throw ValidationException::withMessages([
                    'wallet' => 'Your wallet balance is GHS '.number_format($balance, 2).'. Top up before purchasing.',
                ]);
            }

            $locked->update(['balance' => round($balance - $amount, 2)]);

            return ServiceOrder::create([
                'user_id' => $locked->id,
                'reference' => $reference,
                'remote_type' => $item->remote_type,
                'catalog_item_id' => $item->id,
                'plan_code' => $item->plan_code,
                'beneficiary' => $payload['beneficiary'] ?? null,
                'customer_name' => $payload['customer_name'] ?? null,
                'address' => $payload['address'] ?? null,
                'amount' => round($amount, 2),
                'status' => 'processing',
                'payment_status' => 'paid',
                'wallet_debited' => true,
                'meta' => ['payload' => $payload, 'item_name' => $item->name],
            ]);
        });

        try {
            $remote = $this->client->post('/orders', $payload, $reference);

            $order->update([
                'remote_reference' => $remote['reference'] ?? null,
                'status' => (string) ($remote['order_status'] ?? 'processing'),
                'meta' => array_merge($order->meta ?? [], ['remote' => $remote]),
            ]);

            return $order->refresh();
        } catch (\Throwable $exception) {
            $this->refund($order, $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function purchaseAfa(User $user, array $payload, float $amount): ServiceOrder
    {
        if (! PatupaaSettings::enabled('afa')) {
            throw ValidationException::withMessages(['service' => 'AFA registration is currently unavailable.']);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages(['service' => 'AFA registration price is not configured.']);
        }

        if (! $this->client->configured()) {
            throw new PatupaaException('PATUPAA_NOT_CONFIGURED', 'PATUPAA API credentials are not configured.');
        }

        $reference = 'AFA-'.Str::upper(Str::random(10));

        $order = DB::transaction(function () use ($user, $payload, $amount, $reference): ServiceOrder {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $balance = (float) $locked->balance;

            if (round($balance, 2) < round($amount, 2)) {
                throw ValidationException::withMessages([
                    'wallet' => 'Your wallet balance is GHS '.number_format($balance, 2).'. Top up before purchasing.',
                ]);
            }

            $locked->update(['balance' => round($balance - $amount, 2)]);

            return ServiceOrder::create([
                'user_id' => $locked->id,
                'reference' => $reference,
                'remote_type' => 'afa',
                'plan_code' => null,
                'beneficiary' => $payload['phone'] ?? null,
                'customer_name' => $payload['full_name'] ?? null,
                'address' => $payload['town'] ?? null,
                'amount' => round($amount, 2),
                'status' => 'processing',
                'payment_status' => 'paid',
                'wallet_debited' => true,
                'meta' => ['payload' => $payload],
            ]);
        });

        try {
            $remote = $this->client->post('/afa', $payload, $reference);

            $order->update([
                'remote_reference' => $remote['registration_reference'] ?? $remote['order_reference'] ?? null,
                'status' => (string) ($remote['status'] ?? 'processing'),
                'meta' => array_merge($order->meta ?? [], ['remote' => $remote]),
            ]);

            return $order->refresh();
        } catch (\Throwable $exception) {
            $this->refund($order, $exception->getMessage());

            throw $exception;
        }
    }

    private function refund(ServiceOrder $order, string $message): void
    {
        DB::transaction(function () use ($order, $message): void {
            $fresh = ServiceOrder::query()->lockForUpdate()->find($order->id);

            if ($fresh === null || ! $fresh->wallet_debited) {
                return;
            }

            $user = User::query()->lockForUpdate()->find($fresh->user_id);

            if ($user !== null) {
                $user->update(['balance' => round((float) $user->balance + (float) $fresh->amount, 2)]);
            }

            $fresh->update([
                'status' => 'failed',
                'payment_status' => 'refunded',
                'wallet_debited' => false,
                'error_message' => Str::limit($message, 900),
            ]);
        });
    }
}
