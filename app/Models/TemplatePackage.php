<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplatePackage extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'payload',
        'applied_count',
        'last_applied_at',
        'last_applied_domain',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'applied_count' => 'integer',
            'last_applied_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $payload = $this->payload ?? [];

        return [
            'theme' => $payload['theme'] ?? 'navy-gold',
            'features' => collect($payload['features'] ?? [])->filter()->count(),
            'services' => collect($payload['services'] ?? [])->filter()->count(),
            'providers' => count($payload['providers'] ?? []),
        ];
    }
}
