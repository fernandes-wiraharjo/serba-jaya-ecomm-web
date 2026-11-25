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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // A path is expanded from APP_URL. Register the same absolute URL on the Google OAuth client.
        'redirect' => env('GOOGLE_REDIRECT_URI') ?: '/auth/google/callback',
        'client_ids' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('GOOGLE_CLIENT_IDS', '')),
        ))),
        // Android deep link opened after the browser login. The app receives {scheme}://callback?token=...
        'mobile_scheme' => env('GOOGLE_MOBILE_INTENT_SCHEME'),
        'mobile_package' => env('GOOGLE_MOBILE_INTENT_PACKAGE'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        // Socialite requires these keys. This API only verifies identity tokens, so they stay empty.
        'client_secret' => null,
        'redirect' => null,
        'audiences' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('APPLE_AUDIENCES', '')),
        ))),
    ],

];
