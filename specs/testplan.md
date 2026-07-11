# Shop Release Test Plan

## Automated Gates

| Area | Command | Required result |
| --- | --- | --- |
| PHP style | `vendor/bin/pint --dirty --format agent` | No remaining formatting changes |
| Application suite | `php artisan test --compact` | All unit and feature tests pass |
| Browser suite | `php artisan test --compact tests/Browser` | All Playwright-backed Pest Browser tests pass |
| Fresh install | `php artisan migrate:fresh --seed --no-interaction` | All migrations and deterministic seeders complete |
| Blade | `php artisan view:cache` | All templates compile |
| Frontend | `npm run build` | Vite production build succeeds |
| Routes | `php artisan route:list --except-vendor` | Storefront, customer, admin, and v1 API routes are registered |
| Scheduler | `php artisan schedule:list` | Checkout expiry, analytics rollup, and webhook retry jobs are registered |

## Functional Coverage

- Tenant hostname resolution, store scoping, suspended stores, role policies, customer guard isolation, Sanctum abilities, validation, throttling, and webhook signatures.
- Product variants, inventory reservation/commit/release, collections, media, search, themes, pages, navigation, analytics, apps, and webhooks.
- Cart mutation, discounts, tax, shipping, checkout transitions, successful and declined mock cards, PayPal, bank transfer, order creation, refunds, and fulfillments.
- Admin dashboard and catalog, inventory, order, customer, discount, content, settings, analytics, apps, and developer workflows.
- Deterministic two-tenant seed data, double-run idempotency, pending bank-transfer reservations, and expected demo credentials.

## Browser Acceptance Journeys

Run against the Herd-linked `acme-fashion.test` and `acme-electronics.test` hosts at desktop and 375 × 812 mobile viewports.

1. Storefront home, collection, product, search, CMS page, sold-out/backorder states, cart quantity/removal, and discount presentation.
2. Checkout address validation, domestic shipping, successful `4242 4242 4242 4242` card, declined `4000 0000 0000 0002` card, and bank-transfer confirmation instructions.
3. Customer sign-in, dashboard, order history/detail, addresses, logout, and guest access control.
4. Admin sign-in/access control, dashboard metrics, product search/filters, order detail, bank-transfer confirmation, fulfillment/refund controls, and settings/content pages.
5. Cross-host tenant isolation: electronics content must render without fashion catalog or customer state.
6. Accessibility and quality: landmarks/headings, labels, keyboard focus, skip link, mobile menu, no horizontal overflow, and no browser JavaScript errors.

Any defect restarts the affected journey after a focused regression test and fix. The complete automated suite is rerun after all browser journeys pass.
