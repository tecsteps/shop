<?php

return [
    'defaults' => [
        'checks' => ['deterministic'],
        'fail_on' => 'error',
        'format' => 'pretty',
        'timeout_seconds' => (int) env('CODE_QUALITY_TIMEOUT', 600),
        'check_timeout_seconds' => (int) env('CODE_QUALITY_CHECK_TIMEOUT', 180),
    ],

    'ai' => [
        'enabled' => (bool) env('CODE_QUALITY_AI_ENABLED', false),
        'binary' => env('CODE_QUALITY_CODEX_BINARY', 'codex'),
        'model' => env('CODE_QUALITY_AI_MODEL', 'gpt-5.4-mini'),
        'concurrency' => (int) env('CODE_QUALITY_AI_CONCURRENCY', 3),
        'timeout_seconds' => (int) env('CODE_QUALITY_AI_TIMEOUT', 180),
        'reasoning_effort' => env('CODE_QUALITY_AI_REASONING', 'low'),
        'verbosity' => env('CODE_QUALITY_AI_VERBOSITY', 'low'),
        'max_prompt_file_bytes' => (int) env('CODE_QUALITY_AI_MAX_PROMPT_FILE_BYTES', 120_000),
    ],

    'paths' => [
        'exclude' => [
            'vendor/**',
            'node_modules/**',
            '.git/**',
            'storage/**',
            'bootstrap/cache/**',
            'public/build/**',
            'worker/dist/**',
            'coverage/**',
            'test-results/**',
            '.env',
            '.env.*',
        ],
    ],

    'domain' => [
        'multi_tenant' => (bool) env('CODE_QUALITY_MULTI_TENANT', true),
        'tenant_keys' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CODE_QUALITY_TENANT_KEYS', 'store_id')),
        ))),
        'ai_free_path_keywords' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CODE_QUALITY_AI_FREE_PATH_KEYWORDS', '')),
        ))),
    ],

    'frontend' => [
        'route_helper_names' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('CODE_QUALITY_FRONTEND_ROUTE_HELPERS', 'route,action,wayfinder')),
        ))),
    ],

    'allowlists' => [
        'global_models' => [
            'User',
            'Organization',
            'Store',
            'App',
        ],
        // These rows are tenant-bound through a required parent foreign key;
        // every query enters through the parent relationship/service boundary.
        'tenant_via_parent_models' => [
            'CartLine', 'CustomerAddress', 'Fulfillment', 'FulfillmentLine', 'NavigationItem',
            'OauthClient', 'OauthToken', 'OrderLine', 'Payment', 'ProductMedia', 'ProductOption',
            'ProductOptionValue', 'Refund', 'RefundLine', 'ShippingRate', 'ThemeFile',
            'ThemeSettings', 'WebhookDelivery',
        ],
        // Storefront endpoints are intentionally anonymous or authorize through
        // an opaque session/HMAC helper; platform creation is Sanctum-ability gated.
        'authorization_methods' => [
            'app/Http/Controllers/Api/Admin/PlatformController.php:store',
            'app/Http/Controllers/Api/Storefront/AnalyticsController.php:store',
            'app/Http/Controllers/Api/Storefront/CartController.php:store',
            'app/Http/Controllers/Api/Storefront/CartController.php:show',
            'app/Http/Controllers/Api/Storefront/CheckoutController.php:store',
            'app/Http/Controllers/Api/Storefront/CheckoutController.php:show',
            'app/Http/Controllers/Api/Storefront/OrderController.php:show',
            'app/Http/Controllers/Api/Storefront/SearchController.php:index',
        ],
        'raw_sql' => [],
        'dangerously_set_inner_html' => [],
        'hardcoded_model_strings' => [],
    ],
];
