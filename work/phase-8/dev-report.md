# Phase 8: Search - Dev Report

## Summary

Implemented full-text search using SQLite FTS5 with a contentless index, including a SearchService for querying and syncing, a ProductObserver for automatic index maintenance, and updated Livewire storefront components for search-as-you-type autocomplete and full results pages.

## Steps Completed

### 8.1 - Migrations

- `create_search_settings_table` - Per-store search configuration with `synonyms_json` and `stop_words_json`. Uses `store_id` as primary key (one-to-one with stores).
- `create_search_queries_table` - Search query log with `query`, `filters_json`, `results_count`, and `created_at`. Three indexes for FK lookup, time-range queries, and autocomplete lookups.
- `create_products_fts_table` - Contentless FTS5 virtual table (`content=''`) with columns: title, description, vendor, product_type, tags. Uses contentless mode because the column naming differs from the products table (`description` vs `description_html`), and content sync is managed via the SearchService/ProductObserver.

### 8.2 - SearchService and ProductObserver

**`App\Services\SearchService`**:
- `search()` - Two-phase approach: first queries FTS5 for matching rowids, then queries products table with store scoping, filters, and sorting. Eager loads `variants` and `media` for the product card template. Logs query via SearchQuery model.
- `autocomplete()` - Prefix matching using FTS5 prefix queries (`"term" *`). Returns product id, title, and handle. Rejects prefixes shorter than 2 characters.
- `syncProduct()` - Upserts into FTS5 (remove old entry then insert new). Strips HTML tags from description_html, joins tags array to space-separated string.
- `removeProduct()` - Looks up product by id and removes from FTS5 using the 'delete' command with exact content values (required by contentless FTS5).

**`App\Observers\ProductObserver`**:
- Calls `syncProduct()` on `created` and `updated` events
- Calls `removeProduct()` on `deleting` event
- Registered in `AppServiceProvider::boot()`

### 8.3 - Search UI

**`Storefront\Search\Modal`** - Updated with `wire:model.live.debounce.300ms` for search-as-you-type. Renders autocomplete suggestions (max 5) with links to product pages. Shows "Search for ..." link to full results page when query is 2+ characters.

**`Storefront\Search\Index`** - Full results page with:
- Live search input with debounce
- Sort options: relevance, price low-to-high, price high-to-low, newest
- Vendor filter dropdown (populated from matching products)
- Price range filter (min/max inputs)
- Active filter indicators with clear buttons
- Product grid using existing `product-card` component
- Pagination
- Empty state for no results and no query

### Models

- `SearchSettings` - `store_id` as primary key, casts `synonyms_json` and `stop_words_json` as arrays, belongs to Store
- `SearchQuery` - Uses `BelongsToStore` trait, casts `filters_json` as array

### Store Model Updates

Added `searchSettings()` (HasOne) and `searchQueries()` (HasMany) relationships.

## Technical Decisions

1. **Contentless FTS5 (`content=''`)** - Chosen over `content='products'` because the FTS5 column "description" does not match the products table column "description_html". Contentless FTS also avoids issues with in-memory test databases and JOIN queries against FTS5 virtual tables.

2. **Two-phase search** - First get matching rowids from FTS5, then query the products table with those IDs. This avoids JOIN issues with contentless FTS5 tables and allows standard Eloquent query builder features (eager loading, pagination, scoping).

3. **Observer-based sync** - Products are automatically indexed when created/updated/deleted via the ProductObserver. The observer is registered in AppServiceProvider to keep the FTS index in sync without manual intervention.

## Files Created

- `database/migrations/2026_03_20_130812_create_search_settings_table.php`
- `database/migrations/2026_03_20_130815_create_search_queries_table.php`
- `database/migrations/2026_03_20_130818_create_products_fts_table.php`
- `app/Models/SearchSettings.php`
- `app/Models/SearchQuery.php`
- `app/Services/SearchService.php`
- `app/Observers/ProductObserver.php`
- `tests/Feature/Search/SearchTest.php` (5 tests)
- `tests/Feature/Search/AutocompleteTest.php` (3 tests)

## Files Modified

- `app/Models/Store.php` - Added searchSettings() and searchQueries() relationships
- `app/Providers/AppServiceProvider.php` - Registered ProductObserver
- `app/Livewire/Storefront/Search/Modal.php` - Added autocomplete
- `app/Livewire/Storefront/Search/Index.php` - Full search with filters/sort/pagination
- `resources/views/livewire/storefront/search/modal.blade.php` - Autocomplete UI
- `resources/views/livewire/storefront/search/index.blade.php` - Results page UI

## Test Results

- 8 new tests (5 search + 3 autocomplete), all passing
- Full suite: 528 tests, 1001 assertions, all passing
- Pint: no formatting issues
