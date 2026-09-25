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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'accounts_api' => env('STRIPE_ACCOUNTS_API'),
        'currency' => 'eur',
    ],

    'apify' => [
        'token' => env('APIFY_API_TOKEN'),
        'base_url' => env('APIFY_BASE_URL', 'https://api.apify.com/v2'),
        'profile_actor' => env('APIFY_LINKEDIN_PROFILE_ACTOR', 'supreme_coder~linkedin-profile-scraper'),
        'posts_actor' => env('APIFY_LINKEDIN_POSTS_ACTOR', 'harvestapi~linkedin-profile-posts'),
        'max_posts' => (int) env('APIFY_LINKEDIN_MAX_POSTS', 30),
        'scrape_comments' => filter_var(env('APIFY_LINKEDIN_SCRAPE_COMMENTS', false), FILTER_VALIDATE_BOOLEAN),
        'scrape_reactions' => filter_var(env('APIFY_LINKEDIN_SCRAPE_REACTIONS', false), FILTER_VALIDATE_BOOLEAN),
        'max_comments' => (int) env('APIFY_LINKEDIN_MAX_COMMENTS', 5),
        'max_reactions' => (int) env('APIFY_LINKEDIN_MAX_REACTIONS', 5),
        'poll_interval_ms' => (int) env('APIFY_POLL_INTERVAL_MS', 2000),
        'poll_timeout_seconds' => (int) env('APIFY_POLL_TIMEOUT_SECONDS', 300),
        'refresh_cooldown_minutes' => (int) env('APIFY_LINKEDIN_REFRESH_COOLDOWN_MINUTES', 60),
    ],

];
