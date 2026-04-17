# Phase 4: Cart, Checkout, Discounts, Shipping, Taxes - Dev Report

## Summary

All steps (4.1-4.9) implemented. 433 tests pass, 0 failures.

## Migrations (Step 4.1)

7 migration files created:
- `create_carts_table` - FK: store_id, customer_id (nullable). CHECK trigger for status.
- `create_cart_lines_table` - FK: cart_id, variant_id. Unique (cart_id, variant_id).
- `create_checkouts_table` - FK: store_id, cart_id, customer_id. CHECK trigger for status.
- `create_shipping_zones_table` - FK: store_id. JSON columns for countries/regions.
- `create_shipping_rates_table` - FK: zone_id. CHECK trigger for type.
- `create_tax_settings_table` - PK: store_id (non-auto-incrementing). CHECK trigger for mode.
- `create_discounts_table` - FK: store_id. CHECK triggers for type, value_type, status.

All CHECK constraints implemented as SQLite BEFORE INSERT/UPDATE triggers, matching existing codebase pattern.

## Enums (Step 4.2)

8 enums in `app/Enums/`:
- CartStatus (Active, Converted, Abandoned)
- CheckoutStatus (Started, Addressed, ShippingSelected, PaymentSelected, Completed, Expired)
- DiscountType (Code, Automatic)
- DiscountValueType (Fixed, Percent, FreeShipping)
- DiscountStatus (Draft, Active, Expired, Disabled)
- ShippingRateType (Flat, Weight, Price, Carrier)
- TaxMode (Manual, Provider)
- PaymentMethod (CreditCard, Paypal, BankTransfer)

## Models, Factories, Seeders (Steps 4.2-4.3)

7 models with BelongsToStore trait where applicable:
- Cart, CartLine, Checkout, ShippingZone, ShippingRate, TaxSettings, Discount

7 factories with named states:
- CartFactory (forCustomer, converted, abandoned)
- CartLineFactory
- CheckoutFactory (addressed, shippingSelected, paymentSelected)
- ShippingZoneFactory (international)
- ShippingRateFactory (weightBased, priceBased)
- TaxSettingsFactory (inclusive, zeroRate)
- DiscountFactory (fixed, freeShipping, expired, disabled, automatic)

3 seeders: ShippingZoneSeeder, TaxSettingsSeeder, DiscountSeeder

Store model updated with new relationships: carts(), checkouts(), shippingZones(), taxSettings(), discounts().

## Services (Steps 4.4-4.8)

### CartService (Step 4.4)
- create(), addLine(), updateLineQuantity(), removeLine(), getOrCreateForSession(), mergeOnLogin()
- Merge uses MAX(existingQty, guestQty), not addition
- All mutations wrapped in DB::transaction and increment cart_version
- Validates: variant exists, belongs to store, product active, variant active, inventory policy

### DiscountService (Step 4.5)
- validate() - case-insensitive lookup, checks status/dates/usage/min purchase/product restrictions
- calculate() - percent, fixed (capped at subtotal), free shipping
- Proportional allocation with largest-remainder rounding

### ShippingCalculator (Step 4.6)
- getAvailableRates(), calculate(), getMatchingZone()
- Zone matching: specificity 2 (country+region) > specificity 1 (country), tie-break by lowest zone ID
- Rate types: flat, weight-based (excludes digital items), price-based

### TaxCalculator (Step 4.6)
- calculate(), extractInclusive(), addExclusive()
- Exclusive: tax = round(net * rate / 10000)
- Inclusive: net = intdiv(gross * 10000, 10000 + rate), tax = gross - net

### PricingEngine (Step 4.7)
- Pipeline: line subtotals -> discount -> discounted subtotal -> shipping -> tax -> total
- Tax-inclusive: total = discountedSubtotal + shipping (tax extracted, not added)
- Tax-exclusive: total = discountedSubtotal + shipping + taxAmount
- Invalid discount codes silently ignored (caught exception)

### CheckoutService (Step 4.8)
- State machine: started -> addressed -> shipping_selected -> payment_selected -> completed/expired
- createFromCart(), setAddress(), setShippingMethod(), selectPaymentMethod(), completeCheckout(), expireCheckout()
- applyDiscount(), removeDiscount()
- Inventory: reserved at payment_selected, committed at completed (except bank_transfer)
- Idempotent completeCheckout (returns existing if already completed)
- Validates shipping rate belongs to matching zone

## Value Objects

4 value objects in `app/ValueObjects/`:
- PricingResult (subtotal, discount, shipping, taxLines, taxTotal, total, currency)
- TaxLine (name, rate, amount)
- DiscountResult (totalDiscount, lineAllocations, isFreeShipping)
- TaxResult (taxAmount, taxLines)

## Exceptions

3 custom exceptions in `app/Exceptions/`:
- InvalidCartException
- InvalidDiscountException (with reason property)
- InvalidCheckoutTransitionException (with from/to properties)

## Scheduled Jobs (Step 4.8)

- ExpireAbandonedCheckouts - runs every 15 minutes, expires checkouts not updated in 24 hours
- CleanupAbandonedCarts - runs daily, marks active carts not updated in 14 days as abandoned

## Livewire Components (Step 4.8)

Updated:
- Products/Show - real addToCart() using CartService
- CartDrawer - updateQuantity(), removeLine(), getCart()
- Cart/Show - full cart page with line management

Created:
- Checkout/Show - multi-step checkout (address -> shipping -> payment -> complete)
- Checkout/Confirmation - order confirmation with totals breakdown

Routes added: /checkout, /checkout/confirmation/{checkout}

## Tests (Step 4.9)

13 test files, all passing:

| File | Location | Tests |
|------|----------|-------|
| CartServiceTest | tests/Feature/Cart/ | 12 |
| CartApiTest | tests/Feature/Cart/ | 8 |
| CartVersionTest | tests/Feature/Cart/ | 5 |
| CheckoutStateTest | tests/Feature/Checkout/ | 9 |
| CheckoutFlowTest | tests/Feature/Checkout/ | 5 |
| PricingIntegrationTest | tests/Feature/Checkout/ | 5 |
| PricingEngineTest | tests/Feature/Checkout/ | 7 |
| DiscountCalculatorTest | tests/Feature/Checkout/ | 13 |
| ShippingCalculatorTest | tests/Feature/Checkout/ | 9 |
| TaxCalculatorTest | tests/Unit/ | 7 |
| DiscountTest | tests/Feature/Checkout/ | 6 |
| ShippingTest | tests/Feature/Checkout/ | 5 |
| TaxTest | tests/Feature/Checkout/ | 4 |

Note: CartVersionTest, DiscountCalculatorTest, ShippingCalculatorTest, and PricingEngineTest were moved from tests/Unit/ to tests/Feature/ because they require database access via createStoreContext(). TaxCalculatorTest remains in tests/Unit/ as it operates on pure value objects without database.

## Design Decisions

1. **SQLite triggers for CHECK constraints** - matches existing codebase pattern from Phase 1/2
2. **TaxSettings PK = store_id** - one tax settings row per store, non-auto-incrementing
3. **Cart merge MAX behavior** - per spec: MAX(existingQty, guestQty), not SUM
4. **withoutGlobalScopes()** - used in DiscountService and CheckoutService for cross-store-scope queries
5. **Discount code stored on checkout** - pricing engine validates on each recalculation, silently ignores invalid codes
6. **All monetary values as INTEGER cents** - consistent with existing codebase

## Pint

All modified PHP files formatted with `vendor/bin/pint --dirty --format agent`.
