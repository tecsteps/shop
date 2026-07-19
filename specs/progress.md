# Shop Implementation Progress

> Living document tracking the implementation of the shop system per specs/*.md.
> Updated after every iteration.

## Status Legend
- `[ ]` pending
- `[~]` in progress
- `[x]` done (implemented + tested)

---

## Iteration Log

| # | Date | Phase | Summary | Commit |
|---|------|-------|---------|--------|
| 0 | 2026-07-19 | Setup | Project scaffolding: sanctum + pest-plugin-browser deps, .env.testing, Herd site links (acme-fashion.test, acme-electronics.test), starter-kit cleanup, Fortify removed | c002d312 |
| 1 | 2026-07-19 | Phase 1 | Foundation: all 46-table migrations (+users rewrite), 28 enums, core models (Organization/Store/StoreDomain/StoreSettings/StoreUser/User/Customer), BelongsToStore+StoreScope, ResolveStore/CheckStoreRole/CustomerAuthenticate middleware, CustomerUserProvider, 11 policies, 9 gates, 7 rate limiters, route wiring (web/admin/api). 15 tests green. | 342ff904 |

---

| 2 | 2026-07-19 | Phase 2 | Catalog: 7 models + factories, ProductService (state machine, SKU uniqueness), VariantMatrixService, InventoryService, HandleGenerator, SanitizeHtml, ProcessMediaUpload (GD). 78 tests green. | pending |

| 3 | 2026-07-19 | Phase 3 | Themes/pages/navigation models + NavigationService + ThemeSettingsService + Money helper; storefront layout (dark mode, a11y) + components; Home/Collections/Products/Pages Livewire; real-env smoke OK (200s + 404). 105 tests green. | pending |

| 4 | 2026-07-19 | Phase 4 | Cart/Checkout/Discount/Shipping/Tax engine: 7 models, 7 VOs, PricingEngine, DiscountService, ShippingCalculator, TaxCalculator(+providers), CartService, CheckoutService state machine, storefront cart/checkout REST API, cart drawer + cart page + checkout stepper UI, expiry/cleanup jobs. 219 tests green. Notable: intdiv tax math & merge-sum per spec 09 precedence. | pending |

| 5 | 2026-07-19 | Phase 5 | Payments/orders/fulfillment/customers: MockPaymentProvider, PaymentService, OrderService (atomic creation, numbering, idempotency, digital auto-fulfill), RefundService, FulfillmentService (guard), 9 events + listeners, /pay + order-status APIs, checkout payment step + confirmation UI. 294 tests green. | b55fd22e |

| 6 | 2026-07-19 | Phase 6 | Admin auth (login/logout/forgot/reset, rate-limited, session regen, current_store_id) + customer auth (login/register/forgot/reset, store-scoped token repository, cart merge on login) + account pages (dashboard/orders/addresses) + email verification routes. 342 tests green. | pending |

## Phase Checklist

### Phase 1 — Foundation
- [x] Config: database pragmas, session, cache, queue, auth (customer guard), logging (json + audit), cors
- [x] All 46-table migrations (spec 01, dependency order)
- [x] Core models: Organization, Store, StoreDomain, StoreUser, StoreSettings, User
- [x] Enums (all, spec 05 §21)
- [x] ResolveStore / CheckStoreRole / CustomerAuthenticate middleware
- [x] BelongsToStore trait + StoreScope
- [x] CustomerUserProvider
- [x] Policies + ChecksStoreRole trait + Gates
- [x] Rate limiters
- [x] Tests: Tenancy (TenantResolutionTest, StoreIsolationTest)

### Phase 2 — Catalog
- [x] Models: Product, ProductOption, ProductOptionValue, ProductVariant, InventoryItem, Collection, ProductMedia
- [x] ProductService, VariantMatrixService, InventoryService, HandleGenerator
- [x] ProcessMediaUpload job
- [x] SanitizeHtml action
- [x] Tests: ProductCrudTest, VariantTest, InventoryTest, CollectionTest, MediaUploadTest, HandleGeneratorTest

### Phase 3 — Themes / Pages / Navigation / Storefront layout
- [x] Models: Theme, ThemeFile, ThemeSettings, Page, NavigationMenu, NavigationItem
- [x] NavigationService, ThemeSettings service
- [x] Storefront layout + components (product-card, price, badge, etc.)
- [x] Storefront Livewire: Home, Collections Index/Show, Products Show, Pages Show

### Phase 4 — Cart / Checkout / Discounts / Shipping / Taxes
- [x] Models: Cart, CartLine, Checkout, ShippingZone, ShippingRate, TaxSettings, Discount
- [x] CartService, DiscountService, ShippingCalculator, TaxCalculator, PricingEngine, CheckoutService
- [x] Value objects: PricingResult, TaxLine, Address, etc.
- [x] Jobs: ExpireAbandonedCheckouts, CleanupAbandonedCarts
- [x] Storefront cart/checkout Livewire UI + REST API endpoints
- [x] Tests: PricingEngineTest, DiscountCalculatorTest, TaxCalculatorTest, ShippingCalculatorTest, CartVersionTest, CartServiceTest, CartApiTest, CheckoutFlowTest, CheckoutStateTest, PricingIntegrationTest, DiscountTest, ShippingTest, TaxTest

### Phase 5 — Payments / Orders / Fulfillment
- [x] Models: Customer, CustomerAddress, Order, OrderLine, Payment, Refund, Fulfillment, FulfillmentLine
- [x] MockPaymentProvider, PaymentService, OrderService, RefundService, FulfillmentService, CustomerService
- [x] Events: OrderCreated, OrderPaid, OrderFulfilled, OrderCancelled, OrderRefunded, etc.
- [x] Jobs: CancelUnpaidBankTransferOrders
- [x] Tests: OrderCreationTest, RefundTest, FulfillmentTest, MockPaymentProviderTest, PaymentServiceTest, BankTransferConfirmationTest

### Phase 6 — Customer Accounts + Auth UI
- [x] Admin auth: Login, Logout, ForgotPassword, ResetPassword (Livewire)
- [x] Customer auth: Login, Register, ForgotPassword, ResetPassword (Livewire)
- [x] Account pages: Dashboard, Orders Index/Show, Addresses Index
- [x] Tests: AdminAuthTest, CustomerAuthTest, SanctumTokenTest, CustomerAccountTest, AddressManagementTest

### Phase 7 — Admin Panel
- [ ] Admin layout (sidebar, topbar, breadcrumbs, toasts)
- [ ] Dashboard (KPIs, chart, recent orders)
- [ ] Products (index, form with variants builder, media upload)
- [ ] Orders (index, show with fulfillment/refund modals, confirm payment)
- [ ] Collections, Customers, Discounts, Settings (general/domains/shipping/taxes), Themes, Pages, Navigation, Inventory
- [ ] Tests: DashboardTest, ProductManagementTest, OrderManagementTest, DiscountManagementTest, SettingsTest

### Phase 8 — Search
- [ ] FTS5 migration (products_fts), SearchService, ProductObserver
- [ ] SearchSettings model, admin Search Settings page
- [ ] Storefront Search Modal + Index
- [ ] Tests: SearchTest, AutocompleteTest

### Phase 9 — Analytics
- [ ] AnalyticsEvent, AnalyticsDaily models, AnalyticsService, AggregateAnalytics job
- [ ] Storefront event tracking + API endpoint
- [ ] Admin Analytics page
- [ ] Tests: EventIngestionTest, AggregationTest

### Phase 10 — Apps / Webhooks / Developers / Admin REST API
- [ ] Models: App, AppInstallation, OauthClient, OauthToken, WebhookSubscription, WebhookDelivery
- [ ] WebhookService, DeliverWebhook job, DispatchWebhooks listener
- [ ] Admin Apps + Developers pages (Sanctum token management)
- [ ] Admin REST API (/api/admin/v1): products, collections, orders, customers, discounts, platform
- [ ] Tests: WebhookDeliveryTest, WebhookSignatureTest, SanctumTokenTest, AdminProductApiTest, AdminOrderApiTest, StorefrontCartApiTest, StorefrontCheckoutApiTest

### Phase 11 — Seeders
- [ ] Exact demo data per spec 07 (2 stores, 5 users, 20+ products, collections, discounts, shipping, tax, pages, navigation, orders, customers)

### Phase 12 — Polish
- [ ] Error pages 404/503, dark mode, accessibility, structured logging
- [ ] Pint clean, full test suite green, fresh migrate+seed verified

### Browser E2E
- [ ] Pest browser tests per spec 08 (18 files, 143 tests)
- [ ] Playwright MCP verification of all acceptance criteria

### Final
- [ ] Review meeting: showcase all customer + admin features
