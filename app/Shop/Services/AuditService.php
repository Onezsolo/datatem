<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Models\Admin;
use App\Shop\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(?Admin $admin, string $action, array $meta = [], ?Model $subject = null, ?Request $request = null): AdminAuditLog
    {
        $request ??= app()->runningInConsole() ? null : request();

        return AdminAuditLog::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'meta' => $meta,
        ]);
    }
}
