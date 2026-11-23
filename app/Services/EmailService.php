<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send emails using SMTP configuration from admin settings.
 * Uses the same dynamic_smtp config as the test-email endpoint.
 */
final class EmailService
{
    public static function isConfigured(): bool
    {
        $host = SettingsService::get('smtp_host', '');
        return trim((string) $host) !== '';
    }

    /**
     * Send an HTML email. Returns true on success, false on failure (logs error).
     */
    public static function send(string $toEmail, string $subject, string $bodyHtml, ?string $toName = null): bool
    {
        if (! self::isConfigured()) {
            Log::debug('Email not sent: SMTP not configured.');

            return false;
        }

        $all = SettingsService::all();
        $host = trim((string) ($all['smtp_host'] ?? ''));
        $port = (int) ($all['smtp_port'] ?? 587);
        $encryption = $all['smtp_encryption'] ?? 'tls';
        $encryption = in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null;
        $username = $all['smtp_username'] ?? '';
        $password = $all['smtp_password'] ?? '';
        $fromAddress = trim((string) ($all['smtp_from_address'] ?? ''));
        $fromName = trim((string) ($all['smtp_from_name'] ?? config('app.name', 'R2BMarket')));

        if ($fromAddress === '') {
            $fromAddress = $username ?: 'noreply@' . (parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost');
        }

        try {
            config([
                'mail.mailers.dynamic_smtp' => [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 15,
                ],
                'mail.from' => ['address' => $fromAddress, 'name' => $fromName],
            ]);

            $html = view('emails.layout', [
                'subject' => $subject,
                'bodyHtml' => $bodyHtml,
                'appName' => config('app.name', 'R2BMarket'),
            ])->render();

            Mail::mailer('dynamic_smtp')->html($html, function ($message) use ($toEmail, $toName, $subject) {
                $message->to($toEmail, $toName ?? $toEmail)
                    ->subject($subject);
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('Email send failed', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
