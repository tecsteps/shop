# Phase 6: Customer Accounts - Dev Report

## Summary

Phase 6 implements customer account pages and cart merge on login. All work follows the Gherkin specs and implementation roadmap Steps 6.1-6.2.

## What Was Implemented

### Step 6.1: Customer Auth Enhancements

- **Cart merge on login** (`app/Livewire/Storefront/Account/Auth/Login.php`): Updated the Login component to capture the guest cart ID before session regeneration, then merge or assign it to the customer after successful authentication. Uses `CartService::mergeOnLogin` with MAX behavior for overlapping variants.

### Step 6.2: Customer Account Pages (Livewire)

All pages use the storefront layout and require `auth.customer` middleware.

| Component | Route | Description |
|-----------|-------|-------------|
| `Storefront\Account\Dashboard` | `GET /account` | Welcome message, profile edit (name, marketing opt-in), quick links (orders, addresses, logout), recent orders table (last 5) |
| `Storefront\Account\Orders\Index` | `GET /account/orders` | Paginated order history with status badges, date, total, view link |
| `Storefront\Account\Orders\Show` | `GET /account/orders/{orderNumber}` | Order detail with line items, price breakdown, shipping address, payment info, fulfillment tracking. Scoped to customer (404 for other customers' orders) |
| `Storefront\Account\Addresses\Index` | `GET /account/addresses` | Full CRUD for addresses via modal form, set default, delete with confirmation. Scoped to customer (403 for other customers' addresses) |

### Routes Added

All registered in `routes/web.php` under the existing `/account` prefix with `storefront` + `auth.customer` middleware:

- `GET /account` - Dashboard (replaced placeholder)
- `GET /account/orders` - Order history
- `GET /account/orders/{orderNumber}` - Order detail
- `GET /account/addresses` - Address book

### Dashboard Replacement

The Phase 1 placeholder (`resources/views/storefront/account/dashboard.blade.php`) route was replaced with the real `Dashboard` Livewire component. The old view file remains but is no longer referenced.

## Tests Created

- `tests/Feature/Customers/CustomerAccountTest.php` (6 tests)
  - renders dashboard, lists orders, shows order detail, prevents accessing other's orders, redirects unauth, updates profile
- `tests/Feature/Customers/AddressManagementTest.php` (7 tests)
  - lists addresses, creates, updates, deletes, sets default, validates required fields, prevents managing other's addresses

## Test Results

```
Tests: 491 passed (917 assertions)
Duration: 9.53s
```

All existing tests continue to pass. No regressions.

## Files Modified

- `routes/web.php` - Added account sub-routes, replaced dashboard placeholder route
- `app/Livewire/Storefront/Account/Auth/Login.php` - Added cart merge on login

## Files Created

- `app/Livewire/Storefront/Account/Dashboard.php`
- `app/Livewire/Storefront/Account/Orders/Index.php`
- `app/Livewire/Storefront/Account/Orders/Show.php`
- `app/Livewire/Storefront/Account/Addresses/Index.php`
- `resources/views/livewire/storefront/account/dashboard.blade.php`
- `resources/views/livewire/storefront/account/orders/index.blade.php`
- `resources/views/livewire/storefront/account/orders/show.blade.php`
- `resources/views/livewire/storefront/account/addresses/index.blade.php`
- `tests/Feature/Customers/CustomerAccountTest.php`
- `tests/Feature/Customers/AddressManagementTest.php`
