<?php

declare(strict_types=1);

namespace App\Services\Patupaa;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class PatupaaClient
{
    private ?\App\Models\ServiceProvider $provider = null;

    public static function forProvider(\App\Models\ServiceProvider $provider): self
    {
        $client = new self();
        $client->provider = $provider;

        return $client;
    }

    public function configured(): bool
    {
        return $this->apiKey() !== '' && $this->apiSecret() !== '';
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int|string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request()->get($path, $query)->json('data', []);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int|string, mixed>
     */
    public function post(string $path, array $payload, ?string $idempotencyKey = null): array
    {
        $request = $this->request();

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $request = $request->withHeader('Idempotency-Key', $idempotencyKey);
            $payload['idempotency_key'] = $idempotencyKey;
        }

        return $request->post($path, $payload)->json('data', []);
    }

    private function request(): PendingRequest
    {
        $request = Http::baseUrl(rtrim($this->baseUrl(), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.patupaa.timeout', 25))
            ->withHeaders([
                'X-API-Key' => $this->apiKey(),
                'X-API-Secret' => $this->apiSecret(),
            ]);

        return $request->throw(function ($response) {
            $code = (string) ($response->json('error.code') ?? 'PATUPAA_ERROR');
            $message = (string) ($response->json('error.message') ?? $response->body() ?? 'PATUPAA request failed.');

            throw new PatupaaException($code, $message);
        });
    }

    private function baseUrl(): string
    {
        return $this->provider?->base_url ?: (string) config('services.patupaa.base_url');
    }

    private function apiKey(): string
    {
        return trim((string) ($this->provider?->api_key ?: config('services.patupaa.api_key')));
    }

    private function apiSecret(): string
    {
        return trim((string) ($this->provider?->api_secret ?: config('services.patupaa.api_secret')));
    }
}
