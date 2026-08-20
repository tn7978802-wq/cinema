<?php

$cloudinaryUrl = env('CLOUDINARY_URL');
$parsedCloudinaryUrl = $cloudinaryUrl ? parse_url($cloudinaryUrl) : false;

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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL', 'http://localhost:8000') . '/auth/google/callback'),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME', $parsedCloudinaryUrl['host'] ?? null),
        'api_key' => env('CLOUDINARY_API_KEY', $parsedCloudinaryUrl['user'] ?? null),
        'api_secret' => env('CLOUDINARY_API_SECRET', $parsedCloudinaryUrl['pass'] ?? null),
        'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),
        'folder' => env('CLOUDINARY_FOLDER', 'cinebook'),
        'url' => $cloudinaryUrl,
    ],

    'vnpay' => [
        'vnp_url' => env('VNP_URL'),
        'vnp_tmn_code' => env('VNP_TMN_CODE'),
        'vnp_hash_secret' => env('VNP_HASH_SECRET'),
        'vnp_return_url' => env('VNP_RETURN_URL'),
    ],

];
