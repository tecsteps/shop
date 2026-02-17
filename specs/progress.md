# Shop Implementation Progress

## Overview

Building a complete e-commerce platform with multi-tenant support, product catalog, cart, checkout, mock payments, order management, customer accounts, and admin panel.

**Total features/requirements:** 143 acceptance tests across 18 suites
**Total spec phases:** 12 (Foundation through Full Test Suite)

## Wave Plan

| Wave | Phase(s) | Teammate(s) | Status |
|------|----------|-------------|--------|
| 1 | Foundation: Migrations, Models, Enums, Middleware, Auth, Seeders | foundation | Pending |
| 2 | Catalog + Storefront Layout | catalog, storefront | Pending |
| 3 | Cart, Checkout, Discounts, Shipping, Taxes | checkout | Pending |
| 4 | Payments/Orders/Fulfillment + Customer Accounts | orders, accounts | Pending |
| 5 | Admin Panel (Core + Catalog) | admin-core, admin-catalog | Pending |
| 6 | Search, Analytics, Webhooks | search, analytics, webhooks | Pending |
| 7 | Quality Review | reviewer | Pending |

## Risk Areas

1. **SQLite FTS5** - Full-text search requires raw SQL virtual table creation
2. **Multi-tenant isolation** - StoreScope must be applied consistently to all tenant-scoped models
3. **Checkout state machine** - Complex multi-step flow with inventory reservation
4. **Pricing engine** - Integer arithmetic with proportional discount allocation and rounding
5. **Mock PSP** - Magic card numbers for testing success/failure scenarios
6. **Customer guard** - Separate auth guard scoped to store_id

## Decisions

1. Using `database/shop.sqlite` as the DB path (already in .env)
2. Fortify is pre-installed - will use for admin auth, custom guard for customer auth
3. All monetary amounts in cents (integer)
4. Enums stored as TEXT with CHECK constraints in SQLite
5. Session-based cart binding for storefront
6. Sync queue driver (no Redis)

## Current Status

**Wave 1: Foundation** - Starting
