<?php

declare(strict_types=1);

namespace App\Services\Patupaa;

use App\Models\PatupaaCatalogItem;

final class PatupaaCatalogSync
{
    public function __construct(private readonly PatupaaClient $client)
    {
    }

    /**
     * @return array<string, array{synced: int, disabled: int}>
     */
    public function sync(): array
    {
        if (! $this->client->configured()) {
            throw new PatupaaException('PROVIDER_NOT_CONFIGURED', 'Service provider is not configured.');
        }

        $seen = [];
        $summary = [];

        $upsert = function (
            string $type,
            string $remoteId,
            string $name,
            float $price,
            array $attributes = [],
            array $meta = [],
            bool $remoteEnabled = true,
        ) use (&$seen, &$summary): void {
            PatupaaCatalogItem::updateOrCreate(
                ['remote_type' => $type, 'remote_id' => $remoteId],
                array_merge([
                    'plan_code' => $attributes['plan_code'] ?? null,
                    'name' => $name,
                    'network' => $attributes['network'] ?? null,
                    'data_value' => $attributes['data_value'] ?? null,
                    'validity' => $attributes['validity'] ?? null,
                    'price' => round($price, 2),
                    'meta' => $meta,
                    'remote_enabled' => $remoteEnabled,
                    'synced_at' => now(),
                ], [])
            );

            $seen[$type][] = $remoteId;
            $summary[$type] ??= ['synced' => 0, 'disabled' => 0];
            $summary[$type]['synced']++;
        };

        foreach ($this->client->get('/data-bundles') as $bundle) {
            if (! is_array($bundle) || ! isset($bundle['id'])) {
                continue;
            }

            $upsert(
                PatupaaCatalogItem::TYPE_DATA,
                (string) $bundle['id'],
                (string) ($bundle['name'] ?? 'Data bundle'),
                (float) ($bundle['api_price'] ?? $bundle['price'] ?? 0),
                [
                    'plan_code' => $bundle['plan_code'] ?? $bundle['code'] ?? null,
                    'network' => $bundle['network'] ?? null,
                    'data_value' => $bundle['data_value'] ?? null,
                    'validity' => $bundle['validity'] ?? null,
                ],
                [
                    'code' => $bundle['code'] ?? null,
                    'api_price_source' => $bundle['api_price_source'] ?? null,
                    'list_price' => $bundle['price'] ?? null,
                ]
            );
        }

        foreach (['airtime', 'digital'] as $productType) {
            foreach ($this->client->get('/products', ['type' => $productType]) as $product) {
                if (! is_array($product) || ! isset($product['id'])) {
                    continue;
                }

                $upsert(
                    (string) ($product['type'] ?? $productType),
                    (string) $product['id'],
                    (string) ($product['name'] ?? ucfirst($productType)),
                    (float) ($product['api_price'] ?? $product['price'] ?? 0),
                    [
                        'network' => $product['network'] ?? null,
                    ],
                    [
                        'min_amount' => $product['min_amount'] ?? null,
                        'max_amount' => $product['max_amount'] ?? null,
                        'gift_card' => $product['gift_card'] ?? null,
                    ]
                );
            }
        }

        foreach ($this->client->get('/waec-products') as $waec) {
            if (! is_array($waec) || ! isset($waec['id'])) {
                continue;
            }

            $available = (int) ($waec['available'] ?? 1);

            $upsert(
                PatupaaCatalogItem::TYPE_WAEC,
                (string) $waec['id'],
                (string) ($waec['name'] ?? 'WAEC PIN'),
                (float) ($waec['api_price'] ?? $waec['price'] ?? 0),
                [],
                ['available' => $available],
                $available > 0
            );
        }

        foreach ($this->client->get('/utility-services') as $service) {
            if (! is_array($service) || ! isset($service['key'])) {
                continue;
            }

            $upsert(
                PatupaaCatalogItem::TYPE_UTILITY,
                (string) $service['key'],
                (string) ($service['name'] ?? $service['key']),
                (float) ($service['fee'] ?? 0),
                [],
                [
                    'description' => $service['description'] ?? null,
                    'customer_label' => $service['customer_label'] ?? null,
                    'fee' => $service['fee'] ?? null,
                    'min_amount' => $service['min_amount'] ?? null,
                    'max_amount' => $service['max_amount'] ?? null,
                ]
            );
        }

        foreach (array_keys(PatupaaSettings::TYPES) as $type) {
            if ($type === 'afa') {
                continue;
            }

            $query = PatupaaCatalogItem::query()->where('remote_type', $type);
            $ids = $seen[$type] ?? [];

            if ($ids !== []) {
                $query->whereNotIn('remote_id', $ids);
            }

            $summary[$type] ??= ['synced' => 0, 'disabled' => 0];
            $summary[$type]['disabled'] = (int) $query->update(['remote_enabled' => false]);
        }

        return $summary;
    }
}
