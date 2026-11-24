<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'source_path',
        'source_git_url',
        'source_branch',
        'version',
        'is_active',
        'created_by',
        'last_pushed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_pushed_at' => 'datetime',
        ];
    }

    public function pushes(): HasMany
    {
        return $this->hasMany(WebTemplatePush::class);
    }

    public function sourceReference(): string
    {
        return $this->source_git_url ?: (string) $this->source_path;
    }
}
