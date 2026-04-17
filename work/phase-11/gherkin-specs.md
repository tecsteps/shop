# Phase 11: Polish - Gherkin Specifications

## 1. Accessibility

### 1.1 Skip-to-Content Links

```gherkin
Feature: Skip-to-content navigation
  As a keyboard/screen-reader user
  I want a skip link on every page
  So that I can bypass repetitive navigation

  Scenario: Storefront has a skip-to-content link
    Given the storefront layout is rendered
    Then a hidden link "Skip to main content" targeting "#main-content" exists
    And the link becomes visible on keyboard focus

  Scenario: Admin panel has a skip-to-content link
    Given the admin layout is rendered
    Then a hidden link "Skip to main content" targeting "#main-content" exists
```

### 1.2 ARIA Labels

```gherkin
Feature: ARIA labels on interactive elements
  As a screen-reader user
  I want interactive elements to have ARIA labels
  So that I can understand their purpose

  Scenario: Storefront navigation has proper ARIA labels
    Given the storefront layout is rendered
    Then the desktop navigation has aria-label "Main navigation"
    And the mobile navigation has aria-label "Mobile navigation"
    And the search button has aria-label "Search"
    And the account link has aria-label "Account"
    And the cart link has aria-label "Cart"
    And the hamburger button has aria-label "Open menu"
    And the mobile close button has aria-label "Close menu"
    And the announcement dismiss button has aria-label "Dismiss announcement"
```

### 1.3 Focus Management in Modals

```gherkin
Feature: Focus trapping in modals
  As a keyboard user
  I want focus trapped in open modals
  So that I cannot tab behind the overlay

  Scenario: Mobile navigation traps focus
    Given the mobile nav drawer is open
    Then focus is trapped inside the drawer (x-trap directive)
    And pressing Escape closes the drawer

  Scenario: Cart drawer traps focus
    Given the cart drawer is open
    Then focus is trapped inside the drawer
```

### 1.4 Heading Hierarchy

```gherkin
Feature: Proper heading hierarchy
  As a screen-reader user
  I want headings in correct order (h1 > h2 > h3)
  So that I can navigate the page structure

  Scenario: Error pages have correct heading hierarchy
    Given I visit a non-existent page
    Then the 404 page has an h1 heading

  Scenario: 503 page has correct heading hierarchy
    Given the application is in maintenance mode
    Then the 503 page has an h1 heading
```

### 1.5 Alt Text on Images

```gherkin
Feature: Alt text on images
  As a screen-reader user
  I want images to have alt text
  So that I understand image content

  Scenario: Store logo has alt text
    Given the storefront layout shows a logo image
    Then the image has alt text matching the store name
```

## 2. Responsive Testing

```gherkin
Feature: Responsive layout
  As a mobile user
  I want pages to render correctly at all breakpoints

  Scenario: Mobile navigation works
    Given I view the storefront on a mobile viewport
    Then the hamburger menu button is visible
    And the desktop navigation is hidden
    And clicking the hamburger opens the slide-out nav

  Scenario: Admin sidebar collapses on mobile
    Given I view the admin panel on a mobile viewport
    Then the sidebar is collapsed by default
    And a toggle button is visible to open it
```

## 3. Dark Mode

```gherkin
Feature: Dark mode support
  As a user who prefers dark mode
  I want consistent dark mode styling

  Scenario: Storefront supports dark mode
    Given the storefront layout is rendered
    Then all major elements have dark: prefix classes
    And the body has dark:bg-zinc-900

  Scenario: Admin supports dark mode
    Given the admin layout is rendered
    Then the body has dark:bg-zinc-800
    And the sidebar has dark:bg-zinc-900
```

## 4. Error Pages

```gherkin
Feature: Styled error pages
  As a visitor
  I want error pages to match the storefront theme

  Scenario: 404 page has navigation back to homepage
    Given I visit a non-existent page
    Then I see a styled 404 page
    And there is a "Go to home page" link to "/"
    And the page has dark mode support

  Scenario: 503 page shows maintenance message
    Given the application is in maintenance mode
    Then I see a styled 503 page with the app name
    And the page has dark mode support
    And there is a "Go to home page" link to "/"
```

## 5. Structured Logging

```gherkin
Feature: Structured logging for key operations
  As a platform operator
  I want JSON-formatted logs for key operations
  So that I can monitor and debug the system

  Scenario: Order creation is logged
    Given a checkout is completed
    When an order is created
    Then a structured log entry records the order_number, store_id, customer_email, total_amount, and payment_method

  Scenario: Payment processing is logged
    Given a bank transfer payment is confirmed
    When the payment status changes
    Then a structured log entry records the order_number, payment_id, old_status, and new_status

  Scenario: Webhook delivery is logged
    Given a webhook subscription exists
    When a webhook is dispatched
    Then a structured log entry records the event_type, subscription_id, and delivery_id
```

## 6. Comprehensive Seed Data

### 6.1 Stores and Users

```gherkin
Feature: Demo store seed data
  As a developer running E2E tests
  I want complete seed data matching the E2E plan

  Scenario: Store 1 "Acme Fashion" exists with correct config
    Given the database is freshly seeded
    Then a store "Acme Fashion" with handle "acme-fashion" exists
    And it has domains "acme-fashion.test" (primary) and "shop.test" (secondary)
    And its currency is EUR

  Scenario: Store 2 "Acme Electronics" exists for tenant isolation
    Given the database is freshly seeded
    Then a store "Acme Electronics" with handle "acme-electronics" exists
    And it has domain "acme-electronics.test"

  Scenario: Admin user exists with correct credentials
    Given the database is freshly seeded
    Then user "admin@acme.test" exists with name "Admin User"
    And the user has role "owner" on store "Acme Fashion"

  Scenario: Additional staff users exist
    Given the database is freshly seeded
    Then user "staff@acme.test" exists with role "staff" on "Acme Fashion"
    And user "support@acme.test" exists with role "support" on "Acme Fashion"
    And user "manager@acme.test" exists with role "admin" on "Acme Fashion"
    And user "admin2@acme.test" exists with role "owner" on "Acme Electronics"
```

### 6.2 Products

```gherkin
Feature: Seeded products match E2E requirements
  Scenario: 20 products seeded for Acme Fashion
    Given the database is freshly seeded
    Then 20 products exist for store "Acme Fashion"

  Scenario: Product #1 "Classic Cotton T-Shirt" is correct
    Then product "classic-cotton-t-shirt" is active with price 2499
    And it has options Size (S/M/L/XL) and Color (White/Black/Navy)
    And it has 12 variants, all with inventory 15 each

  Scenario: Product #2 "Premium Slim Fit Jeans" has sale pricing
    Then product "premium-slim-fit-jeans" has price 7999 and compare_at 9999

  Scenario: Product #15 is a draft
    Then product "unreleased-winter-jacket" has status "draft"

  Scenario: Product #17 is sold out (deny policy)
    Then product "limited-edition-sneakers" is active with 0 inventory and "deny" policy

  Scenario: Product #18 is backorderable (continue policy)
    Then product "backorder-denim-jacket" is active with 0 inventory and "continue" policy

  Scenario: 5 products seeded for Acme Electronics
    Then 5 products exist for store "Acme Electronics"
```

### 6.3 Collections

```gherkin
Feature: Seeded collections
  Scenario: Acme Fashion has 4 collections
    Given the database is freshly seeded
    Then collections "new-arrivals", "t-shirts", "pants-jeans", "sale" exist for "Acme Fashion"

  Scenario: Collection-product assignments are correct
    Then collection "t-shirts" contains products #1, #6, #7, #8
    And collection "new-arrivals" contains products #1, #2, #3, #5, #10, #14, #20
```

### 6.4 Discounts

```gherkin
Feature: Seeded discounts
  Scenario: All 5 discount codes exist
    Given the database is freshly seeded
    Then discount "WELCOME10" is active, 10% off, min 20 EUR
    And discount "FLAT5" is active, 5 EUR fixed
    And discount "FREESHIP" is active, free shipping
    And discount "EXPIRED20" is expired
    And discount "MAXED" is active but usage_count equals usage_limit
```

### 6.5 Customers

```gherkin
Feature: Seeded customers
  Scenario: Primary customer exists
    Given the database is freshly seeded
    Then customer "customer@acme.test" exists with name "John Doe"
    And the customer has 2 addresses (Home default, Work)

  Scenario: 10 customers exist for Acme Fashion
    Then 10 customers exist for store "Acme Fashion"
    And 2 customers exist for store "Acme Electronics"
```

### 6.6 Orders

```gherkin
Feature: Seeded orders
  Scenario: Order #1001 awaiting fulfillment
    Given the database is freshly seeded
    Then order "#1001" exists for customer "customer@acme.test"
    And it has status paid/paid/unfulfilled
    And it has 1 line: Classic Cotton T-Shirt S/White qty 2

  Scenario: Order #1004 is cancelled with refund
    Then order "#1004" has status cancelled/refunded/unfulfilled
    And it has a refund of 2998

  Scenario: Order #1005 is pending bank transfer
    Then order "#1005" has status pending/pending/unfulfilled
    And its payment method is bank_transfer

  Scenario: 15 orders exist for Acme Fashion
    Then 15 orders exist for store "Acme Fashion"
    And 3 orders exist for store "Acme Electronics"
```

### 6.7 Shipping, Tax, Pages, Navigation, Themes, Analytics, Search

```gherkin
Feature: Remaining seed data
  Scenario: Shipping zones
    Then "Domestic" zone exists with "Standard Shipping" at 499 and "Express Shipping" at 999
    And "EU" zone exists with "EU Standard" at 899
    And "Rest of World" zone exists with "International" at 1499

  Scenario: Tax settings
    Then tax mode is "manual" with rate 1900 (19%) for Acme Fashion

  Scenario: Pages
    Then 5 pages exist for Acme Fashion: about, faq, shipping-returns, privacy-policy, terms

  Scenario: Navigation
    Then main menu has 5 items: Home, New Arrivals, T-Shirts, Pants & Jeans, Sale
    And footer menu has 5 items: About Us, FAQ, Shipping & Returns, Privacy Policy, Terms of Service

  Scenario: Theme
    Then a published "Default Theme" exists for Acme Fashion

  Scenario: Analytics daily data
    Then 31 days of analytics_daily data exist for Acme Fashion

  Scenario: Analytics events
    Then 200+ analytics events exist for Acme Fashion (last 7 days)

  Scenario: Search settings
    Then search synonyms and stop words are configured for both stores
```

---

## Traceability Table

| Gherkin Scenario | Spec Reference | Implementation File(s) |
|---|---|---|
| 1.1 Skip-to-content (storefront) | Phase 11 Accessibility | `resources/views/layouts/storefront.blade.php` (already done) |
| 1.1 Skip-to-content (admin) | Phase 11 Accessibility | `resources/views/layouts/admin.blade.php` |
| 1.2 ARIA labels | Phase 11 Accessibility | `resources/views/layouts/storefront.blade.php` (already done) |
| 1.3 Focus trapping | Phase 11 Accessibility | `resources/views/layouts/storefront.blade.php` (already done via x-trap) |
| 1.4 Heading hierarchy | Phase 11 Accessibility | Error pages, storefront pages |
| 1.5 Alt text | Phase 11 Accessibility | `resources/views/layouts/storefront.blade.php` (already done) |
| 2.x Responsive | Phase 11 Responsive | Layouts (already responsive) |
| 3.x Dark mode | Phase 11 Dark Mode | Layouts (already has dark: classes) |
| 4.x Error pages | Phase 11 Error Pages | `resources/views/storefront/errors/404.blade.php`, `503.blade.php` |
| 5.x Structured logging | Phase 11 Structured Logging | `app/Services/OrderService.php`, `PaymentService.php`, `WebhookService.php` |
| 6.1 Stores/Users | specs/07 sections 3.1-3.6 | `database/seeders/DatabaseSeeder.php`, `StoreSeeder.php`, etc. |
| 6.2 Products | specs/07 section 3.10 | `database/seeders/ProductSeeder.php` |
| 6.3 Collections | specs/07 section 3.9 | `database/seeders/ProductSeeder.php` (was combined) |
| 6.4 Discounts | specs/07 section 3.11 | `database/seeders/DiscountSeeder.php` |
| 6.5 Customers | specs/07 section 3.12 | `database/seeders/CustomerSeeder.php` |
| 6.6 Orders | specs/07 section 3.13 | `database/seeders/OrderSeeder.php` (new) |
| 6.7 Remaining data | specs/07 sections 3.7-3.18 | Various seeders |

## Self-Assessment

- **Coverage**: All 6 areas from the roadmap (Accessibility, Responsive, Dark Mode, Error Pages, Structured Logging, Comprehensive Seed Data) are covered.
- **Seed data**: The spec-heavy portion is seeder data covering all 18 seeder classes from `specs/07-SEEDERS-AND-TEST-DATA.md`. Every entity from the E2E dependency table is traced.
- **Existing state**: Storefront layout already has skip-to-content, ARIA labels, dark mode, mobile nav with x-trap. Admin uses Flux UI which handles accessibility. Error pages exist but need minor fixes (h1 tag, 503 homepage link). Structured JSON logging channel exists in config. The main gap is the comprehensive seed data.
- **Risk**: The OrderSeeder is new and complex with 15 orders, each requiring specific variants, payments, fulfillments, and refunds. Careful matching to spec is critical.
