# Phase 9: Analytics - Gherkin Specifications

## Feature: Analytics Event Ingestion

### Scenario: Track a page view event
```gherkin
Given a store exists
When the AnalyticsService tracks a "page_view" event with session "sess-1" and no customer
Then an analytics_events row is created with type "page_view", session_id "sess-1", and customer_id NULL
```

### Scenario: Track an event with customer association
```gherkin
Given a store exists
And a customer exists in that store
When the AnalyticsService tracks a "product_view" event with the customer's ID
Then the analytics_events row has customer_id set to that customer's ID
```

### Scenario: Track event stores properties as JSON
```gherkin
Given a store exists
When the AnalyticsService tracks a "product_view" event with properties {"product_id": 42}
Then the analytics_events row has properties_json containing {"product_id": 42}
```

### Scenario: Events are scoped to a store
```gherkin
Given store A and store B each exist
When store A tracks a "page_view" event
Then the event belongs to store A
And store B has no analytics events
```

### Scenario: Track multiple event types
```gherkin
Given a store exists
When the AnalyticsService tracks events of types "page_view", "add_to_cart", "checkout_started", "checkout_completed"
Then four analytics_events rows are created with the respective types
```

---

## Feature: Analytics Daily Aggregation

### Scenario: Aggregate page view visits count
```gherkin
Given a store has 5 page_view events yesterday from 3 unique sessions
When the AggregateAnalytics job runs
Then the analytics_daily row for yesterday has visits_count = 3
```

### Scenario: Aggregate add-to-cart count
```gherkin
Given a store has 4 add_to_cart events yesterday
When the AggregateAnalytics job runs
Then the analytics_daily row for yesterday has add_to_cart_count = 4
```

### Scenario: Aggregate checkout started count
```gherkin
Given a store has 2 checkout_started events yesterday
When the AggregateAnalytics job runs
Then the analytics_daily row for yesterday has checkout_started_count = 2
```

### Scenario: Aggregate orders and revenue from checkout_completed events
```gherkin
Given a store has 2 checkout_completed events yesterday with properties {"order_total": 5000} and {"order_total": 3000}
When the AggregateAnalytics job runs
Then the analytics_daily row for yesterday has orders_count = 2, revenue_amount = 8000, and aov_amount = 4000
```

### Scenario: Aggregation handles zero orders gracefully
```gherkin
Given a store has page_view events yesterday but no checkout_completed events
When the AggregateAnalytics job runs
Then the analytics_daily row for yesterday has orders_count = 0, revenue_amount = 0, and aov_amount = 0
```

---

## Feature: Analytics Daily Metrics Retrieval

### Scenario: Retrieve daily metrics for a date range
```gherkin
Given a store has analytics_daily rows for 2026-03-01 through 2026-03-07
When getDailyMetrics is called for 2026-03-01 to 2026-03-07
Then a collection of 7 daily metric records is returned
```

---

## Feature: Admin Analytics Dashboard

### Scenario: Analytics page shows KPI cards from aggregated data
```gherkin
Given a store has analytics_daily data with revenue, orders, visits, and conversion metrics
When an admin visits /admin/analytics
Then the page displays Total Sales, Orders, AOV, and Conversion Rate KPI tiles
```

### Scenario: Analytics page shows sales chart
```gherkin
Given a store has analytics_daily data for the last 7 days
When an admin visits /admin/analytics and selects "Last 7 days"
Then the page renders a sales chart with daily revenue data
```

### Scenario: Date range filtering updates analytics data
```gherkin
Given a store has analytics_daily data for multiple date ranges
When the admin changes the date range selector to "Today"
Then the KPI tiles and chart refresh to show only today's data
```

---

## Traceability Table

| Gherkin Scenario | Spec Reference | Test File | Test Name |
|---|---|---|---|
| Track a page view event | Spec 05 s14.1 | EventIngestionTest.php | it tracks a page view event |
| Track an event with customer association | Spec 05 s14.1 | EventIngestionTest.php | it tracks an event with customer association |
| Track event stores properties as JSON | Spec 05 s14.1 | EventIngestionTest.php | it stores event properties as JSON |
| Events are scoped to a store | Spec 05 s14.1 | EventIngestionTest.php | it scopes events to the correct store |
| Track multiple event types | Spec 05 s14.1 | EventIngestionTest.php | it tracks all supported event types |
| Aggregate page view visits count | Spec 05 s14.2 | AggregationTest.php | it aggregates visits from unique sessions |
| Aggregate add-to-cart count | Spec 05 s14.2 | AggregationTest.php | it aggregates add_to_cart events |
| Aggregate checkout started count | Spec 05 s14.2 | AggregationTest.php | it aggregates checkout_started events |
| Aggregate orders and revenue | Spec 05 s14.2 | AggregationTest.php | it aggregates orders revenue and aov |
| Aggregation handles zero orders | Spec 05 s14.2 | AggregationTest.php | it handles zero orders gracefully |
| Retrieve daily metrics for date range | Spec 05 s14.2 | AggregationTest.php | it retrieves daily metrics for a date range |
| Analytics page shows KPI cards | Spec 03 s17 | Admin/AnalyticsTest.php | it displays KPI tiles from analytics_daily |
| Analytics page shows sales chart | Spec 03 s17 | Admin/AnalyticsTest.php | it displays sales chart data |
| Date range filtering | Spec 03 s17 | Admin/AnalyticsTest.php | it filters by date range |

## Self-Assessment

**Coverage**: 14 Gherkin scenarios cover the three core features of Phase 9 -- event ingestion (5 scenarios), daily aggregation (6 scenarios including retrieval), and the admin analytics dashboard (3 scenarios). This maps directly to the spec sections 14.1, 14.2, and 17.

**Gaps acknowledged**: The spec mentions an event batch API endpoint (POST /api/storefront/v1/analytics/events), client_event_id deduplication, rate limiting, channel/device filters, CSV export, and top products/referrers tables on the admin page. These are explicitly out of scope for Phase 9 steps 9.1-9.2 per the team lead's task definition, which focuses on the service layer, aggregation job, and updating the admin analytics page with real data from analytics_daily. The batch API endpoint and advanced filtering would be natural follow-ups.

**Confidence**: HIGH that the traceability table accurately maps each scenario to its test implementation. The test file names match the task requirements exactly.
