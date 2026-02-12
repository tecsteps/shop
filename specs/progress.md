# Shop Implementation Progress

## Phase 1: Foundation
- [ ] Environment and config
- [ ] Core migrations
- [ ] Core models with factories/seeders
- [ ] Enums
- [ ] BelongsToStore trait + StoreScope
- [ ] ResolveStore middleware
- [ ] Authentication (admin + customer)
- [ ] Authorization policies
- [ ] Playwright verification

## Phase 2: Catalog
- [ ] Product migrations
- [ ] Product models with relationships
- [ ] ProductService, VariantMatrixService
- [ ] InventoryService
- [ ] HandleGenerator
- [ ] Media upload

## Phase 3: Themes, Pages, Navigation, Storefront Layout
- [ ] Theme/Page/Navigation migrations and models
- [ ] Storefront Blade layout
- [ ] Storefront Livewire components
- [ ] NavigationService

## Phase 4: Cart, Checkout, Discounts, Shipping, Taxes
- [ ] Cart/Checkout migrations and models
- [ ] CartService
- [ ] DiscountService
- [ ] ShippingCalculator
- [ ] TaxCalculator
- [ ] PricingEngine
- [ ] CheckoutService
- [ ] REST API endpoints
- [ ] Cart/Checkout UI

## Phase 5: Payments, Orders, Fulfillment
- [ ] Customer/Order migrations and models
- [ ] MockPaymentProvider
- [ ] OrderService
- [ ] RefundService
- [ ] FulfillmentService
- [ ] Order events

## Phase 6: Customer Accounts
- [ ] Customer auth
- [ ] Account pages (Dashboard, Orders, Addresses)

## Phase 7: Admin Panel
- [ ] Admin layout
- [ ] Dashboard
- [ ] Product management
- [ ] Order management
- [ ] All other admin sections

## Phase 8: Search
- [ ] FTS5 tables
- [ ] SearchService
- [ ] Search UI

## Phase 9: Analytics
- [ ] Analytics tables
- [ ] AnalyticsService
- [ ] Analytics dashboard

## Phase 10: Apps and Webhooks
- [ ] App/Webhook tables
- [ ] WebhookService
- [ ] Admin apps/developers pages

## Phase 11: Polish
- [ ] Error pages
- [ ] SEO
- [ ] Dark mode
- [ ] Responsive design

## Phase 12: Full Test Suite
- [ ] Pest tests for all models/services
- [ ] Playwright E2E tests
- [ ] All acceptance criteria verified
