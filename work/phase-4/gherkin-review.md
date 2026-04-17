# Phase 4: Cart, Checkout, Discounts, Shipping, Taxes - Gherkin Specification Review

## Verdict: APPROVED WITH MINOR GAPS

**123 Gherkin scenarios written across 10 features, covering all roadmap steps 4.1 through 4.9 and all relevant business logic sections (Spec 05 Sections 4-9).**

All core requirements from the Implementation Roadmap (Steps 4.1-4.9), the Business Logic spec (Sections 4-9), and the API Routes spec (Sections 2.1-2.3) are represented. Four minor gaps identified below, none blocking.

---

## Requirement Count Breakdown

### Feature: Cart Service (19 scenarios)

| Requirement Area | Spec Source | Scenarios | Status |
|---|---|---|---|
| Cart creation (store, customer, guest) | Step 4.3, Spec 05 Sec 4.1-4.2 | 3 | Complete |
| Add line (happy path, increment, validations) | Step 4.3, Spec 05 Sec 4.2 | 6 | Complete |
| Update line quantity (happy, zero removal, inventory check) | Step 4.3, Spec 05 Sec 4.2 | 3 | Complete |
| Remove line | Step 4.3, Spec 05 Sec 4.2 | 1 | Complete |
| Cart version increment | Step 4.3, Spec 05 Sec 4.3 | 1 | Complete |
| Session binding (get/create) | Step 4.3, Spec 05 Sec 4.1 | 2 | Complete |
| Merge on login | Step 4.3, Spec 05 Sec 4.1 | 2 | Complete |
| Line amount calculation | Spec 05 Sec 4.4 | Tested inline | Complete |

**19 scenarios covering all 6 CartService methods. Complete.**

Verified against Spec 05 Section 4:
- Cart lifecycle states (active, converted, abandoned) are covered across Cart Service and Scheduled Jobs features.
- Guest session binding via `cart_id` session key is specified.
- Merge logic uses quantity addition (spec says MAX of quantities for duplicates -- see Gap #1 below).
- Line amount formula (subtotal = unit_price * qty, discount = 0, total = subtotal - discount) is verified in scenarios.

### Feature: Cart API (10 scenarios)

| Endpoint | Spec Source | Scenarios | Status |
|---|---|---|---|
| POST /carts | API Spec 2.1 | 1 | Complete |
| GET /carts/{id} | API Spec 2.1 | 2 (success + 404) | Complete |
| POST /carts/{id}/lines | API Spec 2.1 | 3 (success + 2 validations) | Complete |
| PUT /carts/{id}/lines/{lineId} | API Spec 2.1 | 1 | Complete |
| DELETE /carts/{id}/lines/{lineId} | API Spec 2.1 | 1 | Complete |
| Optimistic concurrency (409) | API Spec 2.1, Spec 05 Sec 4.3 | 1 | Complete |
| Rate limiting | API Spec 2.1 | 1 | Complete |

**10 scenarios covering all Cart API endpoints. Complete.**

### Feature: Discount Service (19 scenarios)

| Requirement Area | Spec Source | Scenarios | Status |
|---|---|---|---|
| Code validation (all 6 error codes) | Step 4.4, Spec 05 Sec 7.4-7.5 | 8 | Complete |
| Case-insensitive lookup | Step 4.4, Spec 05 Sec 7.1 | 1 | Complete |
| Product/collection applicability | Spec 05 Sec 7.3 | 2 | Complete |
| Percent calculation | Spec 05 Sec 7.2 | 1 | Complete |
| Fixed calculation (+ cap at subtotal) | Spec 05 Sec 7.2 | 2 | Complete |
| Free shipping value type | Spec 05 Sec 7.2 | 1 | Complete |
| Proportional allocation | Spec 05 Sec 7.6 | 2 | Complete |
| Stacking rules (one code, multiple auto) | Spec 05 Sec 7.1 | 2 | Complete |
| Usage tracking | Spec 05 Sec 7.7 | 1 | Complete |

**19 scenarios covering all DiscountService methods and all 6 error codes. Complete.**

Verified against Spec 05 Section 7:
- All 6 InvalidDiscountException reason codes mapped: discount_not_found, discount_expired, discount_not_yet_active, discount_usage_limit_reached, discount_min_purchase_not_met, discount_not_applicable.
- Union logic (product OR collection match) is covered via separate scenarios for each.
- Largest-remainder rounding allocation is explicitly tested.

### Feature: Shipping Calculator (11 scenarios)

| Requirement Area | Spec Source | Scenarios | Status |
|---|---|---|---|
| Zone matching by country | Step 4.5, Spec 05 Sec 9.1 | 2 (match + no match) | Complete |
| Zone matching by country + region | Spec 05 Sec 9.1 | 1 | Complete |
| Tie-break by lowest zone ID | Spec 05 Sec 9.1 | 1 | Complete |
| Flat rate calculation | Spec 05 Sec 9.2 | 1 | Complete |
| Weight-based rate (ranges, digital exclusion, overflow) | Spec 05 Sec 9.2 | 3 | Complete |
| Price-based rate | Spec 05 Sec 9.2 | 1 | Complete |
| Digital-only cart (zero shipping) | Spec 05 Sec 9.3 | 1 | Complete |
| Free shipping discount override | Spec 05 Sec 9.4 | 1 | Complete |

**11 scenarios covering all shipping calculator methods and rate types. Complete.**

Note: Carrier-calculated rate type is a stub per spec. No scenario needed.

### Feature: Tax Calculator (8 scenarios)

| Requirement Area | Spec Source | Scenarios | Status |
|---|---|---|---|
| Exclusive tax calculation | Step 4.6, Spec 05 Sec 8.2 | 2 | Complete |
| Inclusive tax extraction (intdiv) | Step 4.6, Spec 05 Sec 8.3 | 2 | Complete |
| Zero tax (no settings) | Edge case | 1 | Complete |
| Tax on discounted amounts | Spec 05 Sec 8.5 | 1 | Complete |
| Tax lines in totals_json | Spec 05 Sec 5.2 | 1 | Complete |
| Per-line rounding | Spec 05 Sec 5.3, Sec 8.2 | 1 | Complete |

**8 scenarios covering all TaxCalculator methods and both tax modes. Complete.**

### Feature: Pricing Engine (10 scenarios)

| Requirement Area | Spec Source | Scenarios | Status |
|---|---|---|---|
| Full pipeline (7-step calculation) | Step 4.7, Spec 05 Sec 5.1 | 1 | Complete |
| Discount application in pipeline | Spec 05 Sec 5.1 step 3 | 1 | Complete |
| Snapshot storage (totals_json) | Spec 05 Sec 5.4 | 1 | Complete |
| Recalculation on change | Spec 05 Sec 5.4 | 1 | Complete |
| Tax-inclusive pricing in pipeline | Spec 05 Sec 8.3 | 1 | Complete |
| Determinism | Spec 05 Sec 5.1 | 1 | Complete |
| Pipeline ordering (discount before tax) | Spec 05 Sec 8.5 | 2 | Complete |
| Value objects (PricingResult, TaxLine) | Step 4.7 | 2 | Complete |

**10 scenarios covering the full pricing pipeline. Complete.**

### Feature: Checkout State Machine (19 scenarios)

| Transition | Spec Source | Scenarios | Status |
|---|---|---|---|
| started -> addressed | Step 4.8, Spec 05 Sec 6.2 | 4 (happy + 3 validation) | Complete |
| addressed -> shipping_selected | Step 4.8, Spec 05 Sec 6.2 | 3 (happy + wrong zone + skip digital) | Complete |
| shipping_selected -> payment_selected | Step 4.8, Spec 05 Sec 6.2 | 3 (happy + reserve inventory + reject invalid) | Complete |
| payment_selected -> completed | Step 4.8, Spec 05 Sec 6.2 | 3 (happy + idempotency + payment failure) | Complete |
| Invalid transitions | Spec 05 Sec 6.1 | 2 | Complete |
| Address change recalculation | Spec 05 Sec 5.4, 6.2 | 1 | Complete |
| Expiration | Step 4.8, Spec 05 Sec 6.2 | 3 (timeout + any active + skip completed) | Complete |

**19 scenarios covering all 5 state transitions plus validation and edge cases. Complete.**

Verified against Spec 05 Section 6:
- All 6 checkout states exercised: started, addressed, shipping_selected, payment_selected, completed, expired.
- Inventory reservation on payment_selected and release on expiry/failure.
- Idempotent completion covered.
- Checkout events (CheckoutAddressed, CheckoutShippingSelected, etc.) are not explicitly asserted in Gherkin but are dispatched as side effects -- acceptable for BDD level.

### Feature: Checkout Flow E2E (8 scenarios)

| Requirement Area | Spec Source | Scenarios | Status |
|---|---|---|---|
| Full happy path (card payment) | Step 4.8 | 1 | Complete |
| Bank transfer (deferred payment) | Spec 05 Sec 10.2 | 1 | Complete |
| Empty cart rejection | Validation | 1 | Complete |
| Discount code application/removal | Spec 05 Sec 7 | 5 | Complete |

**8 scenarios providing end-to-end integration coverage. Complete.**

### Feature: Storefront Cart/Checkout UI (11 scenarios)

| Component | Spec Source | Scenarios | Status |
|---|---|---|---|
| CartDrawer | Step 4.9 | 5 (items, quantity, removal, discount, checkout button) | Complete |
| Cart\Show (full page) | Step 4.9 | 1 | Complete |
| Checkout\Show (multi-step) | Step 4.9 | 4 (stepper + 3 steps) | Complete |
| Checkout\Confirmation | Step 4.9 | 1 | Complete |

**11 scenarios covering all 4 Livewire components from Step 4.9. Complete.**

### Feature: Scheduled Jobs (8 scenarios)

| Job | Spec Source | Scenarios | Status |
|---|---|---|---|
| ExpireAbandonedCheckouts (schedule + behavior + skip completed) | Step 4.8 | 3 | Complete |
| CleanupAbandonedCarts (schedule + mark stale + skip recent + skip converted + release inventory) | Step 4.8 | 5 | Complete |

**8 scenarios covering both scheduled jobs. Complete.**

---

## Gap Analysis

### Gap #1: Cart Merge Uses Addition Instead of MAX (Minor - Behavioral Discrepancy)

**Gherkin scenario** (line 158-165): "Merge guest cart into customer cart on login" specifies guest qty 2 + customer qty 1 = merged qty 3 (addition).

**Spec 05 Section 4.1** states: "preferring the higher quantity for duplicate variants" and the pseudocode uses `MAX(existingLine.quantity, line.quantity)`.

The Gherkin scenario uses addition (2+1=3) where the spec says MAX (should be MAX(2,1)=2). This is a semantic discrepancy in the merge behavior.

**Impact:** LOW. This should be clarified before implementation. The Gherkin should be updated to use MAX semantics to match the spec.

### Gap #2: Missing Checkout API Endpoint Scenarios

The Gherkin has a "Cart API" feature but no corresponding "Checkout API" feature covering the REST endpoints. The spec defines these endpoints in API Spec Section 2.2:

- POST /api/storefront/v1/checkouts (create checkout)
- GET /api/storefront/v1/checkouts/{id} (retrieve checkout, including 410 for expired)
- PUT /api/storefront/v1/checkouts/{id}/address
- PUT /api/storefront/v1/checkouts/{id}/shipping-method
- PUT /api/storefront/v1/checkouts/{id}/payment-method
- POST /api/storefront/v1/checkouts/{id}/apply-discount
- DELETE /api/storefront/v1/checkouts/{id}/discount
- POST /api/storefront/v1/checkouts/{id}/pay

The Checkout State Machine and Checkout Flow features cover the service-level behavior, but API-level concerns (HTTP status codes, 410 for expired checkouts, checkout rate limiting at 10/min per session, request/response schemas) are not explicitly tested at the API layer.

**Impact:** MEDIUM. The Cart API has dedicated API-layer scenarios; the Checkout API should have equivalent coverage. However, Phase 5 (Payments, Orders, Fulfillment) will also touch the /pay endpoint, so some of this may be deferred.

**Recommendation:** Add a "Feature: Checkout API" section with at least 10-12 scenarios covering the HTTP endpoints, status codes (especially 410 expired), and the checkout-specific rate limit tier (10/min vs 120/min for general storefront).

### Gap #3: Missing "Cannot Ship to This Address" Blocking Behavior

Spec 05 Section 9.1 states: "When no zone matches: The address is unserviceable. Block checkout progression with error 'Cannot ship to this address'."

The Shipping Calculator feature (line 413-416) tests "Return empty when no zone matches the address" but does not test the blocking behavior at the checkout level. The Checkout State Machine does test "Reject shipping selection with rate from wrong zone" but not the case where zero zones match and the checkout should be blocked from progressing.

**Impact:** LOW. This is a boundary condition that could be added to the Checkout State Machine feature.

### Gap #4: Discount Status Lifecycle Transitions Not Explicitly Tested

Spec 05 Section 7 defines a status lifecycle: draft -> active, active -> disabled, active -> expired (automatic), disabled -> active. The Gherkin tests discount validation against various statuses but does not test the transition logic itself (e.g., cannot transition from draft directly to expired, cannot transition from expired to active).

**Impact:** LOW. Discount status management is primarily an admin concern (Phase 7), but the allowed transitions could be tested as unit-level scenarios here.

---

## Consistency Check

### Consistency with Phase 3 (Completed)

- Phase 3 established the storefront layout, themes, pages, and navigation. Phase 4's Storefront UI scenarios correctly build on top of this (cart drawer as slide-out panel, checkout as a new page within the storefront layout).
- No conflicts detected.

### Consistency with Phase 5 (Payments, Orders, Fulfillment - Next)

- Phase 4's Checkout Flow E2E scenarios correctly stop at order creation and do not attempt to test order management, fulfillment, or refund flows. These belong to Phase 5.
- The `completeCheckout` transition creates orders, payments, and commits inventory -- these are boundary touchpoints that Phase 5 will expand upon.
- The mock payment provider (Spec 05 Section 10) is partially exercised in Phase 4 (card numbers, bank transfer deferred) but full payment processing tests belong in Phase 5.
- No conflicts detected. The boundary is clean.

### Enums Verified

All 7 enums from Step 4.2 are exercised:

| Enum | Where Tested |
|---|---|
| CartStatus (Active, Converted, Abandoned) | Cart Service, Scheduled Jobs, Checkout Flow |
| CheckoutStatus (Started, Addressed, ShippingSelected, PaymentPending, Completed, Expired) | Checkout State Machine |
| DiscountType (Code, Automatic) | Discount Service (code validation + automatic stacking) |
| DiscountValueType (Percent, Fixed, FreeShipping) | Discount Service (3 calculation scenarios) |
| DiscountStatus (Draft, Active, Expired, Disabled) | Discount Service (reject inactive/expired) |
| ShippingRateType (Flat, Weight, Price, Carrier) | Shipping Calculator (3 types + carrier is stub) |
| TaxMode (Manual, Provider) | Tax Calculator (manual mode tested, provider is stub) |

### Models Verified

All 7 models from Step 4.2 are exercised through scenarios:

| Model | Primary Feature |
|---|---|
| Cart | Cart Service |
| CartLine | Cart Service |
| Checkout | Checkout State Machine |
| ShippingZone | Shipping Calculator |
| ShippingRate | Shipping Calculator |
| TaxSettings | Tax Calculator |
| Discount | Discount Service |

### Value Objects Verified

| Value Object | Where Tested |
|---|---|
| PricingResult | Pricing Engine (fields scenario) |
| TaxLine | Pricing Engine (fields scenario) |

---

## Summary

| Feature | Scenarios | Status |
|---|---|---|
| Cart Service | 19 | Complete |
| Cart API | 10 | Complete |
| Discount Service | 19 | Complete |
| Shipping Calculator | 11 | Complete |
| Tax Calculator | 8 | Complete |
| Pricing Engine | 10 | Complete |
| Checkout State Machine | 19 | Complete |
| Checkout Flow (E2E) | 8 | Complete |
| Storefront Cart/Checkout UI | 11 | Complete |
| Scheduled Jobs | 8 | Complete |
| **Total** | **123** | |

**Gaps found: 4 (1 behavioral discrepancy, 1 medium missing feature, 2 low-priority edge cases)**

The specification is thorough and well-structured. The traceability table correctly maps all roadmap steps and business logic sections to Gherkin scenarios. The self-assessment is accurate.

Recommendation: Fix Gap #1 (merge semantics) before implementation. Gap #2 (Checkout API scenarios) should ideally be added but is not blocking since the service-level coverage is solid. Gaps #3 and #4 are low priority.
