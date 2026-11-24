<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
        'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
    ],

    'github' => [
        'api_url' => env('GITHUB_API_URL', 'https://api.github.com'),
        'token' => env('GITHUB_TOKEN', ''),
    ],

    'patupaa' => [
        'base_url' => env('PATUPAA_API_BASE_URL', 'https://patupaa.com/api/v1'),
        'api_key' => env('PATUPAA_API_KEY', ''),
        'api_secret' => env('PATUPAA_API_SECRET', ''),
        'timeout' => (int) env('PATUPAA_API_TIMEOUT', 25),
    ],

    'sms' => [
        'endpoint' => trim((string) env('SMS_ENDPOINT', 'https://api.smsonlinegh.com/v5/message/sms/send')),
        'api_key' => trim((string) env('SMS_API_KEY', '')),
        'sender_id' => trim((string) env('SMS_SENDER_ID', (string) env('APP_NAME', 'Datatem'))),
    ],

    'legacy_data' => [
        // Data bundle API. .env: LEGACY_DATA_BASE_URL, LEGACY_DATA_API_KEY (use your provider’s URL)
        'base_url' => rtrim((string) env('LEGACY_DATA_BASE_URL', ''), '/'),
        'api_key' => trim((string) env('LEGACY_DATA_API_KEY', '')),
        'bundles_path' => env('LEGACY_DATA_BUNDLES_PATH', 'bundles.php'),
        'order_status_path' => env('LEGACY_DATA_ORDER_STATUS_PATH', 'order_status.php'),
        'excluded_bundle_ids' => array_filter(array_map('intval', explode(',', (string) env('LEGACY_DATA_EXCLUDED_BUNDLE_IDS', '')))),
    ],

];
