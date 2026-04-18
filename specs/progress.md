# Shop Build Progress

Started: 2026-04-18
Branch: `2026-04-16-claude-code-opus-4-7-xhigh`
Mode: Agent Team (lead + parallel teammates)

## Phase Status

| Phase | Title | Status | Owner | Notes |
|-------|-------|--------|-------|-------|
| 1 | Foundation (config, tenancy, auth, policies) | done | lead | Tenancy + Auth tests green |
| 2 | Catalog (products, variants, inventory, collections, media) | done | catalog-engineer | Migrations, models, services (Product, VariantMatrix, Inventory), HandleGenerator, ProcessMediaUpload stub; 47 catalog tests green |
| 3 | Themes, Pages, Navigation, Storefront layout | done | storefront-engineer | Theme/Page/Navigation migrations+models+factories, enums, NavigationService + ThemeSettingsService, storefront blade components + Livewire pages (home, collections index/show, product, cart placeholder, search, pages), 404/503 error views, DemoSeeder updated. 11 storefront tests green, 73 total tests green |
| 4 | Cart, Checkout, Discounts, Shipping, Taxes | done | commerce-engineer | carts/cart_lines/checkouts/shipping_zones/shipping_rates/tax_settings/discounts migrations + models + factories, enums (CartStatus, CheckoutStatus, DiscountType, DiscountValueType, DiscountStatus, ShippingRateType, TaxMode), CartService (+session binding, mergeOnLogin, version bump), DiscountService (validate/calculate, case-insensitive), ShippingCalculator (zone matching, flat/weight/price rate types), TaxCalculator (basis points, inclusive/exclusive), PricingEngine (full pipeline with totals snapshot), CheckoutService state machine, ExpireAbandonedCheckouts + CleanupAbandonedCarts jobs scheduled, CartDrawer + Cart\Show + Checkout\Show + Checkout\Confirmation Livewire, /checkout routes, Storefront cart API (carts + lines CRUD with expected_version -> 409), InvalidDiscountException + CartVersionMismatchException exposed. Livewire persistent ResolveStore middleware so cart drawer works cross-request. 65 Phase 4 tests green, 149/149 total tests green. |
| 5 | Payments, Orders, Fulfillment | pending | commerce-engineer | |
| 6 | Customer Accounts | pending | storefront-engineer | |
| 7 | Admin Panel | pending | admin-engineer | |
| 8 | Search (FTS5) | done | catalog-engineer | SearchService + ProductObserver + FTS5 virtual table; 11 search tests green; Search\Index + Search\Modal rewired to SearchService |
| 9 | Analytics | pending | admin-engineer | |
| 10 | Apps and Webhooks | pending | admin-engineer | |
| 11 | Polish (a11y, dark mode, error pages, seed data) | pending | lead | |
| 12 | Full Test Suite Run + Playwright review | pending | qa-engineer | |

## Commit Log

- Phase 1 foundation: migrations, models, enums, ResolveStore middleware, StoreScope/BelongsToStore trait, Livewire auth (admin + customer), Sanctum, policies, tenancy + auth tests passing.
- Phase 2 catalog: products/options/variants/inventory/collections/media migrations + models + factories, enums (ProductStatus, VariantStatus, CollectionStatus, CollectionType, MediaType, MediaStatus, InventoryPolicy), services (ProductService, VariantMatrixService, InventoryService), HandleGenerator, ProcessMediaUpload stub, InsufficientInventoryException + InvalidProductTransitionException, DemoSeeder catalog data. 47 catalog tests passing, 64/64 total tests green.
- Phase 3 storefront: themes/theme_files/theme_settings/pages/navigation_menus/navigation_items migrations + models + factories, enums (ThemeStatus, PageStatus, NavigationItemType), NavigationService (cached buildTree/resolveUrl), ThemeSettingsService singleton, storefront Blade components (product-card, price, badge, quantity-selector, address-form, order-summary, breadcrumbs, pagination), Livewire pages (Home enhanced, Collections Index/Show, Products Show with variant + add-to-cart dispatch stub, Cart Show placeholder, Search Index/Modal, Pages Show), 404/503 error views, DemoSeeder theming data. 73/73 tests green.
- Phase 8 search: search_settings + search_queries + products_fts (FTS5 virtual) migrations, App\Services\SearchService (search/autocomplete/syncProduct/removeProduct; query logging; order preservation via CASE), App\Observers\ProductObserver wired via #[ObservedBy] on Product, storefront Search\Index + Search\Modal Livewire rewired to SearchService, 11 search tests green, 84/84 total tests green, /search?q=cotton verified via HTTP.
- Phase 4 commerce: carts/cart_lines/checkouts/shipping_zones/shipping_rates/tax_settings/discounts migrations + models + factories, enums (CartStatus, CheckoutStatus, DiscountType, DiscountValueType, DiscountStatus, ShippingRateType, TaxMode), App\Exceptions\CartVersionMismatchException + InvalidDiscountException + CheckoutStateException rendered as JSON responses, CartService (create/addLine/updateLineQuantity/removeLine/getOrCreateForSession/mergeOnLogin with version bumps and optimistic concurrency), DiscountService (case-insensitive validate; percent/fixed/free_shipping calculate and proportional allocation), ShippingCalculator (zone matching by country/region with specificity, flat/weight/price rate types), TaxCalculator (basis points, prices_include_tax extraction via intdiv, region override), PricingEngine (subtotal -> discount -> shipping -> tax pipeline with snapshot in checkouts.totals_json), CheckoutService state machine (setAddress / setShippingMethod / selectPaymentMethod reserves inventory / expireCheckout releases), App\Jobs\ExpireAbandonedCheckouts scheduled every 15 min, App\Jobs\CleanupAbandonedCarts daily, Storefront CartDrawer + Cart\Show + Checkout\Show + Checkout\Confirmation Livewire, /checkout and /checkout/confirmation/{number} routes, Storefront cart API (POST/GET/POST-line/PUT-line/DELETE-line with expected_version -> 409 CartVersionMismatch), Livewire persistent ResolveStore middleware registered, 65 Phase 4 tests green, 149/149 total tests green, browser verified add-to-cart -> drawer open -> cart page -> checkout page.
