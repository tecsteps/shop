<?php

use App\Enums\StoreDomainType;

return [
    'binding' => 'current_store',
    'view_share' => 'currentStore',
    'admin_session_key' => 'current_store_id',
    'admin_path_prefix' => 'admin',
    'cache_prefix' => 'store-domains',
    'cache_ttl' => (int) env('STORE_CACHE_TTL', 300),
    'store_cache_ttl' => (int) env('STORE_CACHE_TTL', 300),
    'storefront_domain_type' => StoreDomainType::Storefront->value,
];
