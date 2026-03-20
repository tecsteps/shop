# Phase 9: Analytics - Dev Report

## Summary

Phase 9 implements the analytics subsystem: raw event ingestion, daily metric aggregation, and a real admin analytics dashboard replacing the placeholder page. The implementation covers spec sections 14.1 (Event Ingestion), 14.2 (Aggregation), and 17 (Analytics Page).

## Architecture Decisions

### 1. AnalyticsEvent model with manual timestamps
The `analytics_events` table uses a single `created_at` column instead of Laravel's default `created_at`/`updated_at` pair. Analytics events are immutable once written, so `updated_at` would be dead weight. The model sets `$timestamps = false` and manually assigns `created_at` during creation.

### 2. Composite primary key on analytics_daily
The `analytics_daily` table uses a composite PK of `(store_id, date)` per spec. Laravel's Eloquent does not natively support composite primary keys, so the model overrides `setKeysForSaveQuery()` to make save/update operations work. The model also sets `$incrementing = false` and defines `$primaryKey` as an array.

### 3. AnalyticsService as a thin write/read layer
The `AnalyticsService` is intentionally thin -- `track()` inserts a row; `getDailyMetrics()` reads pre-aggregated data. The service uses `withoutGlobalScopes()` to bypass the tenant scope when writing, because the store is passed explicitly as a parameter rather than relying on the container-bound current store.

### 4. AggregateAnalytics job with date injection
The job accepts an optional `$date` parameter (defaults to yesterday). This makes it testable without time manipulation and supports backfilling. It queries raw events for the target date, groups by store, and upserts into `analytics_daily` using `upsert()` for idempotent reruns.

### 5. Admin Analytics page reads from analytics_daily only
The admin page queries `analytics_daily` exclusively rather than raw events. This keeps the admin dashboard fast regardless of event volume. KPIs are computed via SQL aggregation (SUM) over the pre-aggregated daily rows.

### 6. Bar chart rendered server-side
Instead of using a JS charting library, the daily revenue chart is rendered as server-side HTML bars using Tailwind width percentages. This avoids adding a JS dependency and works with Livewire's reactive model.

## Files Created

| File | Purpose |
|---|---|
| `database/migrations/..._create_analytics_events_table.php` | Raw event stream table with indexes per spec |
| `database/migrations/..._create_analytics_daily_table.php` | Pre-aggregated daily metrics with composite PK |
| `app/Models/AnalyticsEvent.php` | Eloquent model with BelongsToStore, JSON cast |
| `app/Models/AnalyticsDaily.php` | Eloquent model with composite PK support |
| `database/factories/AnalyticsEventFactory.php` | Factory with states for each event type |
| `app/Services/AnalyticsService.php` | track() and getDailyMetrics() methods |
| `app/Jobs/AggregateAnalytics.php` | Daily aggregation job |
| `tests/Feature/Analytics/EventIngestionTest.php` | 5 tests for event tracking |
| `tests/Feature/Analytics/AggregationTest.php` | 6 tests for aggregation and retrieval |
| `tests/Feature/Admin/AnalyticsTest.php` | 4 tests for admin analytics page |

## Files Modified

| File | Change |
|---|---|
| `app/Models/Store.php` | Added `analyticsEvents()` and `analyticsDaily()` relationships |
| `app/Livewire/Admin/Analytics/Index.php` | Replaced placeholder with real analytics component |
| `resources/views/livewire/admin/analytics/index.blade.php` | Replaced placeholder with KPIs, chart, funnel |
| `routes/console.php` | Registered AggregateAnalytics job at 01:00 daily |

## Test Mapping to Gherkin

| Gherkin Scenario | Test |
|---|---|
| Track a page view event | EventIngestionTest: it tracks a page view event |
| Track an event with customer association | EventIngestionTest: it tracks an event with customer association |
| Track event stores properties as JSON | EventIngestionTest: it stores event properties as JSON |
| Events are scoped to a store | EventIngestionTest: it scopes events to the correct store |
| Track multiple event types | EventIngestionTest: it tracks all supported event types |
| Aggregate page view visits count | AggregationTest: it aggregates visits from unique sessions |
| Aggregate add-to-cart count | AggregationTest: it aggregates add_to_cart events |
| Aggregate checkout started count | AggregationTest: it aggregates checkout_started events |
| Aggregate orders and revenue | AggregationTest: it aggregates orders revenue and aov |
| Aggregation handles zero orders | AggregationTest: it handles zero orders gracefully |
| Retrieve daily metrics for date range | AggregationTest: it retrieves daily metrics for a date range |
| Analytics page shows KPI cards | AnalyticsTest: it displays KPI tiles from analytics_daily |
| Analytics page shows sales chart | AnalyticsTest: it displays sales chart data |
| Date range filtering | AnalyticsTest: it filters by date range |

## Deviations from Spec

1. **No batch API endpoint**: The spec describes `POST /api/storefront/v1/analytics/events` for batch event ingestion. This was explicitly out of scope per the task definition, which focuses on the service layer and admin page.

2. **No channel/device filters**: The spec's admin analytics page includes channel and device filter dropdowns. These were omitted because the `analytics_daily` table does not store channel/device dimensions, and the event properties schema does not standardize these fields yet. Adding these filters would require schema changes.

3. **No CSV export**: The spec mentions an export button. This was deferred as it requires a background job and file storage that is outside the Phase 9 scope.

4. **No top products/referrers tables on analytics page**: The spec shows these sections, but they require order line data (already on the Dashboard) or referrer tracking (not yet in the event schema). A funnel summary was added instead.

5. **Server-side bar chart instead of JS chart**: The spec mentions a "line/bar chart." A pure HTML/CSS bar chart was implemented to avoid adding a JavaScript charting dependency.

## Test Results

```
Tests:    543 passed (1042 assertions)
Duration: 12.04s
```

All 543 tests pass, including 15 new analytics-specific tests and 528 pre-existing tests (zero regressions).

## Self-Assessment

**Strengths**: Clean separation of concerns (service/job/component), idempotent aggregation via upsert, composite PK handled properly, comprehensive test coverage for both the service layer and admin UI.

**Weaknesses**: The admin analytics page is simpler than the spec envisions -- it lacks channel/device filters, CSV export, and the top products/referrers tables. The bar chart is functional but basic compared to a proper JS charting library. The `AnalyticsDaily` model's composite PK workaround is a known Eloquent limitation that could cause issues if someone tries to use `find()` on it.
