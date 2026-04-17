# Phase 5: Gherkin Specification Review

## Review Summary

**Reviewer:** Gherkin Reviewer Agent
**Date:** 2026-03-20
**Document reviewed:** `work/phase-5/gherkin-specs.md`
**Sources checked:** `specs/09-IMPLEMENTATION-ROADMAP.md` (Steps 5.1-5.6), `specs/05-BUSINESS-LOGIC.md` (Sections 10, 11, 6.2), `specs/01-DATABASE-SCHEMA.md`

---

## Requirement Counts

### Roadmap Requirements (Steps 5.1-5.6)

| Step | Requirement Area | Requirement Count | Scenarios Written | Covered? |
|------|-----------------|-------------------|-------------------|----------|
| 5.1 | Migrations: 8 tables (customers, customer_addresses, orders, order_lines, payments, refunds, fulfillments, fulfillment_lines) | 8 | 8 (one scenario per table, fulfillments + fulfillment_lines combined in one feature) | Yes |
| 5.2 | Models: 8 models with relationships | 8 models | 30 scenarios across all models | Yes |
| 5.2 | Enums: 7 enums | 7 | 7 Scenario Outlines (28 example rows total) | Yes |
| 5.3 | MockPaymentProvider charge: 3 payment methods, 3 magic cards | 7 behaviors | 7 charge scenarios + 2 refund scenarios + 1 binding scenario | Yes |
| 5.3b | Admin confirm bank transfer + auto-cancel job | 2 features | 5 confirm scenarios + 6 cancel job scenarios | Yes |
| 5.4 | OrderService: createFromCheckout, generateOrderNumber, cancel | 3 methods | 14 + 5 + 4 = 23 scenarios | Yes |
| 5.5 | RefundService: create | 1 method | 10 scenarios | Yes |
| 5.6 | FulfillmentService: create, markAsShipped, markAsDelivered + auto-fulfill + events | 3 methods + 2 features | 11 + 3 + 2 + 3 + 5 = 24 scenarios | Yes |

**Total scenarios (approximate):** ~111 scenarios (including Scenario Outline example rows as individual test cases: ~139)

### Business Logic Coverage (spec 05)

| BL Section | Topic | Covered in Gherkin? |
|------------|-------|---------------------|
| 10.1 | Mock Payment Provider Architecture | Yes - interface binding, no external calls |
| 10.2 | Supported Payment Methods (3 methods) | Yes - all 3 covered |
| 10.3 | Magic Card Numbers (3 cards + default) | Yes - 3 magic cards covered; default "any other valid number" NOT explicitly covered |
| 10.4 | Mock Payment Flow (sequence diagram) | Yes - covered implicitly through order creation scenarios |
| 10.5 | Payment State Machine | Yes - status transitions covered across multiple features |
| 10.6 | Bank Transfer Instructions (mock bank details) | **NO** - not covered (see gaps) |
| 10.7 | Admin Confirm Payment (bank transfer) | Yes - 5 scenarios |
| 10.8 | Auto-Cancel Unpaid Bank Transfers | Yes - 6 scenarios |
| 11.1 | Order Creation from Checkout | Yes - 14 scenarios |
| 11.2 | Order Numbering | Yes - 5 scenarios |
| 11.3 | Order Statuses (3 dimensions) | Yes - covered implicitly throughout |
| 11.4 | Refund Processing | Yes - 10 scenarios |
| 11.5 | Fulfillment + Guard | Yes - 11 create + 6 guard scenarios |
| 11.6 | Fulfillment Guard Inventory Behavior | Yes - covered across order creation and bank transfer confirmation scenarios |
| 11.7 | Auto-Fulfillment for Digital Products | Yes - 3 scenarios in dedicated feature + duplicated in order creation and bank transfer features |
| 6.2 | completeCheckout (idempotency, atomicity) | Yes - dedicated scenarios |

---

## Identified Gaps

### Gap 1: Missing `delivered_at` column in fulfillments migration scenario (MEDIUM)

The DB schema (`01-DATABASE-SCHEMA.md`) does NOT include a `delivered_at` column on the `fulfillments` table. However, the business logic spec (BL 11.5) and the roadmap (Step 5.6 `markAsDelivered`) both reference "sets `delivered_at`". The gherkin migration scenario for the fulfillments table correctly mirrors the DB schema (no `delivered_at`), but the `markAsDelivered` feature scenario does not mention setting `delivered_at`. This is an underlying spec discrepancy. The gherkin should either:
- Add `delivered_at` to the migration scenario (if the DB schema is updated), OR
- Note this as a known spec conflict to resolve during implementation.

### Gap 2: Bank Transfer Instructions (BL 10.6) not covered (LOW)

BL Section 10.6 specifies mock bank transfer instructions (Mock Bank AG, IBAN, BIC, reference = order number). No gherkin scenario covers the display or availability of these instructions. This is arguably a storefront UI concern (Phase 7 admin or Phase 4 checkout), but since the mock values are defined in Phase 5's payment domain, a scenario verifying the mock data structure would be beneficial.

### Gap 3: Default card number behavior not explicitly tested (LOW)

BL 10.3 specifies that "any other valid-looking number" results in success. The gherkin only tests the 3 magic card numbers. A scenario for an arbitrary card number succeeding would improve coverage.

### Gap 4: `FulfillmentCreated` and `FulfillmentShipped` events missing (MEDIUM)

The events feature (Step 5.x) covers 5 events: OrderCreated, OrderPaid, OrderFulfilled, OrderCancelled, OrderRefunded. However, BL 11.5 also dispatches:
- `FulfillmentCreated` event (step 6 of createFulfillment procedure)
- `FulfillmentShipped` event (on markAsShipped, BL 11.5 admin button actions)

These two events are defined in the business logic but are NOT listed in the roadmap's Step 5.6 events table (which only lists Order-level events). The gherkin follows the roadmap, but the business logic spec adds these fulfillment-level events. The gherkin should either add scenarios for these events or explicitly note they are deferred.

### Gap 5: Encrypted payment response storage not tested (LOW)

BL 11.1 states "The mock payment response is encrypted before storage" (stored in `payments.raw_json_encrypted`). The gherkin includes the column in the migration but has no scenario verifying that the payment response is actually encrypted when stored. This is a data handling concern that could be tested.

### Gap 6: `OrderFulfilled` event dispatch trigger unclear (LOW)

The events feature has a scenario for `OrderFulfilled` event but does not specify the exact trigger point. BL 11.5 says the `FulfillmentShipped` event handler dispatches `order.fulfilled` webhook "if fully fulfilled", but `OrderFulfilled` as a domain event should be dispatched when `order.fulfillment_status` transitions to `fulfilled`. The fulfillment create scenarios do verify the status transition but do not explicitly assert the `OrderFulfilled` event dispatch at that point.

---

## Consistency Check

### With Phase 4 (Cart, Checkout, Discounts, Shipping, Taxes)

- **Checkout completion:** Phase 5 gherkin correctly picks up where Phase 4 ends (checkout at `payment_selected` status). The `completeCheckout` transition is fully specified.
- **Cart conversion:** Phase 5 correctly marks the cart as `converted` upon order creation, matching Phase 4's cart lifecycle.
- **Discount usage increment:** Phase 5 covers the usage_count increment on order creation, which connects to Phase 4's discount validation.
- **Inventory flow:** The reserve (Phase 4) -> commit/release (Phase 5) lifecycle is consistently represented.
- **No overlaps or contradictions found.**

### With Phase 6 (Customer Accounts)

- **Customer/CustomerAddress models:** Phase 5 creates these models and migrations. Phase 6 will add authentication (customer guard, login/register pages) and account management. No overlap - Phase 5 defines the data layer, Phase 6 defines the user-facing features.
- **Guest-to-customer linking (BL 12.4):** The `linkGuestToCustomer` procedure from BL 12.4 is NOT covered in Phase 5 gherkin, which is correct since this is a Phase 6 concern. Phase 5 correctly handles guest orders with `customer_id = null`.
- **No boundary issues found.**

---

## Structural Quality

| Criterion | Assessment |
|-----------|------------|
| Gherkin syntax correctness | Good - proper Feature/Scenario/Given/When/Then structure throughout |
| Use of Backgrounds | Good - used appropriately for shared preconditions |
| Scenario Outlines for enums | Good - avoids repetitive scenarios |
| Traceability matrix | Present and maps scenarios to roadmap steps and spec references |
| Test file alignment | Self-assessment references 6 test files with specific scenario counts |
| Readability | Good - clear, descriptive scenario names |
| Duplication | Minor - auto-fulfillment for digital products is specified in 3 places (order creation, bank transfer confirmation, dedicated feature). This is acceptable since each context has different preconditions. |

---

## Self-Assessment

| Metric | Value |
|--------|-------|
| Total roadmap requirements identified | 38 distinct requirements across Steps 5.1-5.6 |
| Total gherkin scenarios (excluding outline expansions) | ~111 |
| Total gherkin scenarios (including outline example rows) | ~139 |
| Requirements with full scenario coverage | 35/38 (92%) |
| Gaps identified | 6 (2 MEDIUM, 4 LOW) |
| Blocking gaps | 0 |
| Phase boundary consistency | Clean - no overlaps or contradictions with Phase 4 or Phase 6 |

**Overall verdict:** The gherkin specs provide strong coverage of Phase 5 requirements. The two MEDIUM gaps (missing `delivered_at`/spec conflict, and missing `FulfillmentCreated`/`FulfillmentShipped` events) should be addressed before implementation but are not blocking. The LOW gaps are edge cases or UI-adjacent concerns that can be handled during implementation.

**Recommendation:** Approve with the following changes:
1. Add scenarios for `FulfillmentCreated` and `FulfillmentShipped` events to the events feature (or document why they are deferred).
2. Resolve the `delivered_at` column discrepancy between DB schema and business logic specs, and update the gherkin migration and markAsDelivered scenarios accordingly.
3. Optionally add a scenario for default/unknown card number success behavior.
