<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebTemplatePush extends Model
{
    protected $fillable = [
        'web_template_id',
        'pushed_by',
        'owner',
        'repository',
        'branch',
        'repo_url',
        'status',
        'message',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(WebTemplate::class, 'web_template_id');
    }
}
