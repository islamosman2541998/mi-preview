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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    // phase 2
    // stock photos for the admin image picker (either or both)
    'pixabay' => [
        'key' => env('PIXABAY_API_KEY'),
    ],

    'pexels' => [
        'key' => env('PEXELS_API_KEY'), // new Pexels keys are paused (2026); kept for when they reopen
    ],

    // optional fallback for logo background removal (the browser model is the default)
    'remove_bg' => [
        'key' => env('REMOVE_BG_API_KEY'),
        'size' => env('REMOVE_BG_SIZE', 'preview'), // "preview" is free (≤ 0.25 MP) and enough for a logo
    ],

    // Business profile import through Meta Graph API (Facebook Pages + Instagram Business Discovery).
    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'page_id' => env('META_PAGE_ID'),
        'page_access_token' => env('META_PAGE_ACCESS_TOKEN'),
        'instagram_account_id' => env('META_INSTAGRAM_ACCOUNT_ID'),
        'version' => env('META_GRAPH_VERSION', 'v27.0'),
        'base_url' => env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'),
        'max_posts' => (int) env('META_MAX_POSTS', 36),
        'media_hosts' => array_values(array_filter(array_map('trim', explode(',', env('META_MEDIA_HOSTS', 'fbcdn.net,fbsbx.com,cdninstagram.com'))))),
    ],

    // AI content generation (phase 3). AI_PROVIDER picks the default (claude | gemini);
    // the admin can choose per run among the providers that have a key.
    // Claude via the official Anthropic PHP SDK:
    'ai' => [
        'provider' => env('AI_PROVIDER', 'claude'),
        'key' => env('AI_API_KEY'),                          // an Anthropic API key (sk-ant-…)
        'model' => env('AI_MODEL', 'claude-sonnet-5-5'),
        'effort' => env('AI_EFFORT', 'medium'),             // low | medium | high — copywriting rarely needs more
        'max_tokens' => (int) env('AI_MAX_TOKENS', 64000),  // streamed, so a large cap is safe
        'base_url' => env('AI_BASE_URL'),                   // tests point this at a local fake server
        // USD per million tokens (input, output) — for the cost shown on each run
        'prices' => [
            'claude-opus-5-5' => [4.0, 20.0],
            'claude-sonnet-5-5' => [2.0, 10.0],
            'claude-haiku-5-5' => [0.10, 0.50],
        ],
    ],

    // Google Gemini (REST). The free tier costs nothing but Google may use the content
    // to improve its products; a paid key does not.
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'), // stable; 3.8-flash ("New") was hanging under load
        'thinking' => env('GEMINI_THINKING', 'low'),          // minimal* | low | medium | high (*not on 3.8-flash)
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 65536), // includes thinking tokens
        'free_tier' => (bool) env('GEMINI_FREE_TIER', true),   // show the cost as free
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'prices' => [
            'gemini-3.8-flash' => [0.75, 3.75],
            'gemini-3.6-flash' => [0.75, 3.75],
            'gemini-3.5-flash-lite' => [0.30, 2.50],
        ],
    ],

];
