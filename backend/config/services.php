<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'enagalaruz_bot'),
        // setWebhook da beriladi, Telegram har so'rovda sarlavhada qaytaradi
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'login_ttl_minutes' => 10,
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'matching_model' => env('ANTHROPIC_MATCHING_MODEL', 'claude-haiku-5-5'),
        'enabled' => (bool) env('AI_MATCHING_ENABLED', true),
        // Bitta foydalanuvchi uchun kuniga AI so'rovlar soni (undan keyin qoidalar bilan hisoblanadi).
        'daily_limit' => (int) env('AI_DAILY_LIMIT', 30),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
