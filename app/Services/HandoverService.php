<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class HandoverService
{
    private const KEY = 'deployment_handover';

    /**
     * @return array{completed: bool, completed_at: string|null, completed_by: string|null, note: string|null}
     */
    public static function details(): array
    {
        $raw = SettingsService::get(self::KEY);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        if (! is_array($decoded) || empty($decoded['handed_over_at'])) {
            return ['completed' => false, 'completed_at' => null, 'completed_by' => null, 'note' => null];
        }

        return [
            'completed' => true,
            'completed_at' => (string) $decoded['handed_over_at'],
            'completed_by' => isset($decoded['handed_over_by']) ? (string) $decoded['handed_over_by'] : null,
            'note' => isset($decoded['note']) ? (string) $decoded['note'] : null,
        ];
    }

    public static function isCompleted(): bool
    {
        return self::details()['completed'];
    }

    public static function complete(User $user, ?string $note = null): void
    {
        SettingsService::setMany([
            self::KEY => json_encode([
                'handed_over_at' => now()->toIso8601String(),
                'handed_over_by' => $user->email,
                'note' => $note,
            ]),
        ]);
    }
}
