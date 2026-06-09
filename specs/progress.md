# Implementation Progress

Tracking implementation of the shop system per `specs/09-IMPLEMENTATION-ROADMAP.md`.

| Phase | Scope | Status | Tests |
|-------|-------|--------|-------|
| 1 | Foundation: config, tenancy migrations/models, enums, ResolveStore, BelongsToStore, admin+customer auth, policies, Pest helpers | DONE (commit 5b743cde) | 30 passed, 3 todos |
| 2 | Catalog: products, options, variants, inventory, collections, media | DONE | full suite 79 passed, 5 todos (4 deferred to P5, 1 to P4) |
| 3 | Themes, pages, navigation, storefront layout + components | DONE (commit 5aee8dc4) | full suite 98 passed, 5 todos; Storefront/* rendering tests added |
| 4 | Cart, checkout, discounts, shipping, taxes + storefront cart/checkout UI | DONE | full suite 202 passed, 8 todos; Unit/* (48 new), Cart/CartServiceTest (12), Checkout/* (34), Storefront/CartUiTest (13) |
| 5 | Payments (mock PSP), orders, refunds, fulfillments, events | pending | Orders/*, Payments/* |
| 6 | Customer accounts | pending | Customers/*, finish CustomerAuthTest |
| 7 | Admin panel (all sections) | pending | Admin/* |
| API | Spec 02 REST APIs + Sanctum | pending | Api/*, Auth/SanctumTokenTest, Cart/CartApiTest |
| 8 | Search (FTS5) | pending | Search/* |
| 9 | Analytics | pending | Analytics/* |
| 10 | Apps & webhooks | pending | Webhooks/* |
| 11 | Polish, seeders (spec 07), error pages, dark mode, a11y | pending | - |
| 12 | Full suite + browser tests (spec 08) + Playwright MCP acceptance verification | pending | Browser/* |

## Log

- 2026-06-09: Project start. Fresh Livewire starter kit (Fortify). Created progress tracker, started Phase 1.
- 2026-06-09: Phase 2 done. Catalog migrations (9 tables), models, enums, ProductService/VariantMatrixService/InventoryService/MediaService, HandleGenerator, ProcessMediaUpload job (GD), Collection+Product seeders (25 products, 127 variants). Order-reference checks implemented behind Schema::hasTable('order_lines') guard; 3 order-dependent test cases todo'd for Phase 5.
- 2026-06-09: Phase 4 done. Migrations for carts, cart_lines, checkouts, shipping_zones, shipping_rates, tax_settings, discounts. Models + factories + 7 enums. Services: CartService (session key `cart_id`, version bump on every mutation, sum-quantities merge on login), DiscountService (reason-coded InvalidDiscountException, largest-remainder allocation), ShippingCalculator (specificity-ordered zone matching, flat/weight/price rates), TaxCalculator (basis points, intdiv for exclusive add and inclusive extract), PricingEngine (deterministic pipeline, snapshot to totals_json; inclusive total = discounted subtotal + shipping), CheckoutService state machine (payment_selected reserves inventory + 24h expires_at; completeCheckout guarded, throws LogicException until Phase 5). Jobs ExpireAbandonedCheckouts (15 min) + CleanupAbandonedCarts (daily) scheduled in routes/console.php. Storefront UI: CartDrawer (global, listens cart-updated/open-cart), /cart page with shipping estimate, /checkout 4-step stepper through payment method selection (pay button is the Phase 5 mount point), live header badge, real addToCart. Seeders: TaxSettingsSeeder, ShippingSeeder, DiscountSeeder (WELCOME10/FLAT5/FREESHIP/EXPIRED20/MAXED). Cart merge wired into CustomerLoginController; CustomerAuthTest merge case implemented. 4 test cases todo'd for Phase 5 (order completion paths).
