<?php

return [
    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'live_enabled' => filter_var(env('GROQ_LIVE_ENABLED', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        'demo' => [
            'enabled' => filter_var(env('GROQ_DEMO_ENABLED', false), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'mode' => env('GROQ_DEMO_MODE', ''),
            'environments' => array_filter(array_map('trim', explode(',', (string) env('GROQ_DEMO_ENVIRONMENTS', '')))),
            'database' => env('GROQ_DEMO_DATABASE', ''),
            'user_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('GROQ_DEMO_USER_IDS', ''))))),
            'global_limit' => env('GROQ_DEMO_GLOBAL_LIMIT', 0),
            'user_limit' => env('GROQ_DEMO_USER_LIMIT', 0),
        ],
    ],

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

];
