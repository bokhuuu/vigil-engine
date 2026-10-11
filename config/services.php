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

    'google_drive' => [
        'credentials' => env('GOOGLE_DRIVE_CREDENTIALS'),
        'act_folder_id' => env('GOOGLE_DRIVE_ACT_FOLDER_ID'),
        'oauth_client' => env('GOOGLE_DRIVE_OAUTH_CLIENT'),
        'oauth_token' => env('GOOGLE_DRIVE_OAUTH_TOKEN'),
        'business_folder_id' => env('GOOGLE_DRIVE_BUSINESS_FOLDER_ID'),
    ],

    'business' => [
        'own_email' => env('BUSINESS_SEED_EMAIL'),
        'internal_emails' => array_map('trim', explode(',', env('BUSINESS_INTERNAL_EMAILS', ''))),
    ],
];
