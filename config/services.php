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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
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
    'agent' => [
        'response_delay_seconds' => env('AGENT_RESPONSE_DELAY_SECONDS', 1),
        'wait_timeout' => env('AGENT_WAIT_TIMEOUT', 30),
    ],
    'agent_uploads' => [
        'upload_max_kb' => env('AGENT_UPLOAD_MAX_KB', 20 * 1024),
        'post_max_kb' => env('AGENT_POST_MAX_KB', 30 * 1024),
        'stale_processing_seconds' => env('AGENT_UPLOAD_STALE_PROCESSING_SECONDS', 900),
        'disk' => env('AGENT_UPLOAD_DISK', 'local'),
        'processed_directory' => env('AGENT_UPLOAD_PROCESSED_DIRECTORY', 'agent-uploads/processed'),
    ],

];
