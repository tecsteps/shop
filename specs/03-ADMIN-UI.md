# 03 - Admin Panel UI/UX Specification

This document defines every view, component, and interaction for the admin panel. The admin is entirely server-rendered using Laravel 12 + Livewire v4 + Flux UI Free components + Tailwind CSS v4. There is no SPA or client-side router.

**Flux UI Free components available:** avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, profile, radio, select, separator, switch, text, textarea, tooltip

Anything not covered by Flux UI Free uses plain Tailwind CSS v4 markup with Livewire directives.

---

## 1. Admin Layout Shell

The admin layout is a persistent shell that wraps every admin page. It consists of a left sidebar, a top bar, and a main content area.

### 1.1 Route Prefix

All admin routes live under `/admin` and are protected by authentication middleware plus store-scoping middleware. The store is resolved from the session or a store-selector in the top bar.

### 1.2 Sidebar Navigation

**Livewire component:** `App\Livewire\Admin\Layout\Sidebar`

**Blade view:** `resources/views/livewire/admin/layout/sidebar.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `collapsed` | `bool` | Whether the sidebar is collapsed (mobile state) |
| `currentRoute` | `string` | The current route name for active highlighting |

| Action | Behavior |
|--------|----------|
| `toggle()` | Toggles the `collapsed` state |

#### Wireframe

```
+-------------------------------+
|  [BRAND LOGO]  Platform Name  |
|-------------------------------|
|  [separator]                  |
|                               |
|  [chart-bar]    Dashboard     |
|                               |
|  PRODUCTS                     |
|  [cube]         Products      |
|  [rect-stack]   Collections   |
|  [archive-box]  Inventory     |
|                               |
|  [separator]                  |
|  ORDERS                       |
|  [shopping-bag] Orders        |
|                               |
|  [separator]                  |
|  CUSTOMERS                    |
|  [users]        Customers     |
|                               |
|  [separator]                  |
|  DISCOUNTS                    |
|  [tag]          Discounts     |
|                               |
|  [separator]                  |
|  CONTENT                      |
|  [doc-text]     Pages         |
|  [bars-3]       Navigation    |
|  [paint-brush]  Themes        |
|                               |
|  [separator]                  |
|  [chart-pie]    Analytics     |
|  [cog-6-tooth]  Settings      |
|  [squares-2x2]  Apps          |
|  [code-bracket] Developers    |
+-------------------------------+
```

#### Navigation Structure

```
Dashboard                        -> admin.dashboard
Products (group label)
  Products                       -> admin.products.index
  Collections                    -> admin.collections.index
  Inventory                      -> admin.inventory.index
Orders
  Orders                         -> admin.orders.index
Customers
  Customers                      -> admin.customers.index
Discounts
  Discounts                      -> admin.discounts.index
Content (group label)
  Pages                          -> admin.pages.index
  Navigation                     -> admin.navigation.index
  Themes                         -> admin.themes.index
Analytics                        -> admin.analytics.index
Settings                         -> admin.settings.index
Apps                             -> admin.apps.index
Developers                       -> admin.developers.index
```

Each nav item displays an icon (via Flux icon component) and a text label. The active item receives a highlighted background and bold text. All navigation links use Livewire SPA-style page transitions. Groups are visually separated with Flux separator components.

#### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Platform logo and name | `brand` |
| Navigation icons | `icon` |
| Group dividers | `separator` |

#### Responsive Behavior

- **Desktop (lg and above):** Sidebar is always visible, fixed left, 256px wide. Main content has a left margin of 256px.
- **Mobile (below lg):** Sidebar is hidden off-screen. A hamburger button (icon: bars-3) appears in the top bar. When user clicks it, the sidebar slides in as an overlay with a semi-transparent backdrop. Clicking the backdrop or a nav item closes it.

### 1.3 Top Bar

**Livewire component:** `App\Livewire\Admin\Layout\TopBar`

**Blade view:** `resources/views/livewire/admin/layout/top-bar.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `currentStoreName` | `string` | Name of the active store |
| `stores` | `Collection` | List of stores the user has access to |
| `unreadNotificationCount` | `int` | Count of unread notifications |

| Action | Behavior |
|--------|----------|
| `switchStore(string $storeId)` | Switches active store in session, redirects to dashboard |

#### Wireframe

```
+--------------------------------------------------------------------+
| [hamburger*]  [Store Name v]                  [bell(3)]  [avatar v] |
+--------------------------------------------------------------------+
  * hamburger visible on mobile only

  Store Name dropdown:
    - Store A
    - Store B
    - Store C

  Avatar dropdown:
    - Settings (links to /admin/settings)
    - [separator]
    - Log out (POST /logout with CSRF)
```

#### Interaction Flows

- When user clicks the store name, a dropdown appears listing all stores. Clicking a store calls `switchStore()` which updates the session and redirects to the dashboard.
- Notification bell shows an unread count badge (positioned absolutely over the icon) when `unreadNotificationCount > 0`.
- Profile dropdown is triggered by a Flux profile component showing the user's avatar and name.

#### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Store selector | `dropdown` |
| Hamburger (mobile) | `button` (icon: bars-3) |
| Notification bell | `button` (icon: bell, variant: ghost) |
| Notification count | `badge` |
| User profile trigger | `profile` |
| Profile menu | `dropdown` |
| Menu divider | `separator` |

### 1.4 Main Content Area

The main content area sits to the right of the sidebar and below the top bar. Every page renders within this area.

**Breadcrumbs:** Every page includes a Flux breadcrumbs component at the top of the content area. The first item is always "Home" linking to `/admin`. Subsequent items reflect the page hierarchy. Example for product edit: Home > Products > "Blue T-Shirt".

**Layout wrapper:** The content area has consistent padding (larger on desktop, smaller on mobile) and a max-width container centered horizontally.

### 1.5 Toast Notifications

A global toast notification system lives in the layout shell. It uses Alpine.js to listen for Livewire dispatched events.

| Property | Value |
|----------|-------|
| Event name | `toast` |
| Payload | `{ type: 'success' / 'error' / 'info', message: string }` |
| Position | Top-right corner |
| Auto-dismiss | 5 seconds |
| Stacking | Supports multiple toasts |
| Styling | Rounded card, shadow, colored left border (green=success, red=error, blue=info) |

Components dispatch toasts after successful actions (e.g., "Product saved successfully.") or on errors ("Something went wrong. Please try again.").

### 1.6 Dark Mode

The admin supports dark mode via Tailwind's `dark:` variant. The mode follows the user's system preference by default. A toggle can be added in the profile dropdown later. All components and custom markup must include dark mode variants.

Dark mode preference is persisted in `localStorage` under the key `theme` with values `light` or `dark`. On page load, the stored preference is applied before first paint to avoid flash of incorrect theme. If no preference is stored, the system preference (`prefers-color-scheme`) is used as default.

---

## 2. Dashboard

**Route:** `GET /admin`

**Livewire component:** `App\Livewire\Admin\Dashboard`

**Blade view:** `resources/views/livewire/admin/dashboard.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `dateRange` | `string` | Active date range preset: `today`, `last_7_days`, `last_30_days`, `custom` |
| `customStartDate` | `?string` | Custom range start (Y-m-d) |
| `customEndDate` | `?string` | Custom range end (Y-m-d) |
| `totalSales` | `int` | Total sales amount in minor units |
| `ordersCount` | `int` | Number of orders in range |
| `averageOrderValue` | `int` | Average order value in minor units |
| `visitorsCount` | `int` | Unique visitors in range |
| `salesChange` | `float` | Percentage change vs previous period |
| `ordersChange` | `float` | Percentage change vs previous period |
| `aovChange` | `float` | Percentage change vs previous period |
| `visitorsChange` | `float` | Percentage change vs previous period |
| `ordersChartData` | `array` | Array of `{date, count}` for last 30 days |
| `topProducts` | `array` | Top 5 products: `{title, units_sold, revenue}` |
| `funnelData` | `array` | Funnel steps: `{visits, add_to_cart, checkout_started, checkout_completed}` |

| Action | Behavior |
|--------|----------|
| `updatedDateRange()` | Re-fetches all KPI data for the selected range |
| `loadKpis()` | Queries aggregated order/analytics data |
| `loadChart()` | Queries daily order counts for chart |
| `loadTopProducts()` | Queries top 5 products by revenue |
| `loadFunnel()` | Queries conversion funnel data |

| Computed Property | Purpose |
|-------------------|---------|
| `formattedTotalSales` | Formatted currency string |
| `formattedAov` | Formatted currency string |

### Wireframe

```
+------------------------------------------------------------------+
| Dashboard                           [Today v] (date range picker) |
+------------------------------------------------------------------+
|                                                                    |
|  +-------------+  +-------------+  +-------------+  +----------+  |
|  | Total Sales |  | Orders      |  | Avg Order   |  | Visitors |  |
|  | $12,345     |  | 156         |  | $79.13      |  | 2,340    |  |
|  | [+12.3%] ^  |  | [+8.1%] ^  |  | [-2.1%] v  |  | [+5%] ^  |  |
|  +-------------+  +-------------+  +-------------+  +----------+  |
|                                                                    |
|  +--------------------------------------------------------------+  |
|  | Orders over time                                              |  |
|  |  [line/bar chart - daily order counts for selected range]     |  |
|  +--------------------------------------------------------------+  |
|                                                                    |
|  +------------------------------+  +----------------------------+  |
|  | Top products                 |  | Conversion funnel          |  |
|  | Product Title | Sold | Rev   |  | Visits       [========] N |  |
|  | Blue Shirt    | 45   | $900  |  | Add to Cart  [======]   N |  |
|  | Red Hat       | 32   | $640  |  | Checkout     [====]     N |  |
|  | Sneakers      | 28   | $2800 |  | Completed    [==]       N |  |
|  | ...           |      |       |  |                            |  |
|  +------------------------------+  +----------------------------+  |
+--------------------------------------------------------------------+
```

### Layout Description

**Page heading:** "Dashboard" displayed as a Flux heading (size xl).

**Date range filter:** Positioned top-right. A Flux dropdown triggered by a button showing the current range label and a calendar icon. Options: Today, Last 7 days, Last 30 days, Custom range (reveals two date input fields when selected).

### KPI Tiles Row

A responsive 4-column grid (1 column on mobile, 2 on sm, 4 on lg).

Each tile is a card containing:
- A muted-color text label (e.g., "Total Sales")
- A large heading with the formatted number value
- A row with a Flux badge showing percentage change (green if positive, red if negative) and a directional arrow icon

Each tile shows a loading state (reduced opacity) while data refreshes.

### Orders Chart

A card below the KPI tiles containing:
- A Flux heading (size lg): "Orders over time"
- A Chart.js canvas (line or bar chart) showing daily order counts
- Chart.js initialized via Alpine.js; data passed from Livewire
- A spinner overlay shown during chart data loading

### Top Products Table

A card containing:
- A Flux heading (size lg): "Top products"
- A plain table with columns: Product Title, Units Sold, Revenue
- Maximum 5 rows
- Empty state text: "No sales data for this period."

### Conversion Funnel

A card containing:
- A Flux heading (size lg): "Conversion funnel"
- Horizontal bar visualization using Tailwind utility widths
- Each step: label on the left, proportional-width bar on the right, count displayed
- Steps: Visits, Add to Cart, Checkout Started, Checkout Completed
- Bars use decreasing widths and progressively darker colors

### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Page title | `heading` (size xl) |
| Section titles | `heading` (size lg) |
| Date range selector | `dropdown`, `button`, `icon` |
| Date inputs (custom range) | `input` (type date) |
| KPI labels | `text` |
| KPI values | `heading` (size xl) |
| Trend badges | `badge` |
| Trend arrows | `icon` |

### View States

| State | Behavior |
|-------|----------|
| Loading | KPI tiles show reduced opacity; chart shows spinner overlay |
| Empty | "No sales data for this period." in top products; funnel bars at zero width |
| Populated | All tiles, chart, table, and funnel render with data |
| Error | Toast notification with error message |

---

## 3. Products List

**Route:** `GET /admin/products`

**Livewire component:** `App\Livewire\Admin\Products\Index`

**Blade view:** `resources/views/livewire/admin/products/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search query, live-debounced at 300ms |
| `statusFilter` | `string` | Status filter: `all`, `draft`, `active`, `archived` |
| `typeFilter` | `string` | Product type filter |
| `selectedIds` | `array<int>` | Array of selected product IDs for bulk actions |
| `selectAll` | `bool` | Whether all visible rows are selected |
| `sortField` | `string` | Column to sort by (default: `updated_at`) |
| `sortDirection` | `string` | `asc` or `desc` |

| Action | Behavior |
|--------|----------|
| `updatedSearch()` | Resets pagination |
| `updatedStatusFilter()` | Resets pagination |
| `updatedTypeFilter()` | Resets pagination |
| `bulkArchive()` | Archives all selected products |
| `bulkDelete()` | Soft-deletes selected products (with confirmation) |
| `bulkSetActive()` | Sets selected products to active status |
| `sortBy(string $field)` | Toggles sort direction or changes sort field |
| `toggleSelectAll()` | Selects or deselects all visible product IDs |
| `confirmBulkDelete()` | Opens delete confirmation modal |

| Computed Property | Purpose |
|-------------------|---------|
| `products` | Paginated query result with search, filters, sorting applied. Eager loads `variants`, `media` (limit 1). Uses `withCount('variants')`. |
| `productTypes` | Distinct product types for the type filter dropdown |

### Wireframe

```
+------------------------------------------------------------------+
| Products                                    [+ Add product]       |
+------------------------------------------------------------------+
| [search icon] Search products...   [Status: All v] [Type: All v] |
+------------------------------------------------------------------+
| (shown when items selected)                                       |
| "3 products selected"  [Set Active]  [Archive]  [Delete]         |
+------------------------------------------------------------------+
| [x] | IMG | Title ^     | Status  | Inventory | Type | Vendor | Updated ^  |
|-----|-----|-------------|---------|-----------|------|--------|-----------|
| [ ] | img | Blue Shirt  | Active  | 45        | Tops | Nike   | 2h ago    |
| [ ] | img | Red Hat     | Draft   | 12        | Hats | Adidas | 1d ago    |
| [ ] | img | Sneakers    | Active  | 0         | Shoes| Nike   | 3d ago    |
| ...                                                              |
+------------------------------------------------------------------+
| < 1 2 3 ... 10 >  (pagination)                                  |
+------------------------------------------------------------------+
```

### Table Column Definitions

| Column | Sortable | Content |
|--------|----------|---------|
| Select | No | Checkbox for bulk selection |
| Image | No | 40x40 rounded thumbnail of first media item, or placeholder icon |
| Title | Yes | Product title as a link to the edit page (uses Livewire navigate) |
| Status | No | Flux badge: draft=zinc/gray, active=green, archived=red |
| Inventory | Yes | Sum of `inventory_items.quantity_on_hand` across all variants |
| Type | No | Product type text or "-" |
| Vendor | No | Vendor name text or "-" |
| Updated | Yes (default) | Relative timestamp (e.g., "2h ago") |

### Bulk Action Bar

Conditionally shown when `count($selectedIds) > 0`. Contains:
- Text showing "X products selected"
- "Set Active" button (ghost variant)
- "Archive" button (ghost variant)
- "Delete" button (danger variant) - opens confirmation modal

### Empty State

When no products exist (not filtered, genuinely empty):
- Centered container with a large muted placeholder icon (cube)
- Heading: "Add your first product"
- Text: "Start building your catalog by adding products."
- Primary button: "Add product" linking to the create page

### Delete Confirmation Modal

| Property | Value |
|----------|-------|
| Modal name | `confirm-bulk-delete` |
| Max width | md |
| Heading | "Delete products?" |
| Body text | "This will archive X product(s). Products with orders cannot be permanently deleted." |
| Cancel button | Ghost variant, closes modal |
| Confirm button | Danger variant, calls `bulkDelete` |

### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Page title | `heading` |
| Add product button | `button` (variant: primary) |
| Search field | `input` (icon: magnifying-glass) |
| Status filter | `select` |
| Type filter | `select` |
| Bulk action buttons | `button` (ghost/danger variants) |
| Selected count text | `text` |
| Row checkbox | `checkbox` |
| Status badges | `badge` |
| Cell text | `text` |
| Delete confirmation | `modal` |
| Pagination | Laravel default Tailwind pagination |

### View States

| State | Behavior |
|-------|----------|
| Loading | Table body shows reduced opacity |
| Empty (no products) | Empty state with illustration and CTA |
| Empty (filtered) | Table with "No products match your filters" message |
| Populated | Table with data rows and pagination |

---

## 4. Product Create/Edit

**Route:** `GET /admin/products/create` and `GET /admin/products/{product}/edit`

**Livewire component:** `App\Livewire\Admin\Products\Form`

**Blade view:** `resources/views/livewire/admin/products/form.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `product` | `?Product` | The product being edited, null for create |
| `title` | `string` | Product title |
| `descriptionHtml` | `string` | Product description (HTML) |
| `status` | `string` | `draft`, `active`, `archived` |
| `vendor` | `string` | Vendor name |
| `productType` | `string` | Product type string |
| `tags` | `string` | Comma-separated tags |
| `handle` | `string` | URL handle/slug |
| `publishedAt` | `?string` | Publish date (Y-m-d H:i) |
| `collectionIds` | `array<int>` | Selected collection IDs |
| `options` | `array` | Options array: `[{name: string, values: string[]}]` |
| `variants` | `array` | Variants array: `[{sku, price, compareAtPrice, quantity, requiresShipping, optionValues}]` |
| `media` | `array` | Existing media items: `[{id, url, alt_text, position}]` |
| `newMedia` | `array` | Temporary uploaded files |

| Action | Behavior |
|--------|----------|
| `mount(?Product $product)` | Loads product data into properties or sets defaults for create |
| `save()` | Validates and saves/creates the product, variants, options, and media |
| `addOption()` | Adds a new empty option `{name: '', values: []}` |
| `removeOption(int $index)` | Removes an option and regenerates variants |
| `addOptionValue(int $optionIndex)` | Adds a value to an option |
| `removeOptionValue(int $optionIndex, int $valueIndex)` | Removes a value |
| `generateVariants()` | Generates variant combinations from current options |
| `uploadMedia()` | Handles file upload via Livewire temporary upload |
| `removeMedia(int $mediaId)` | Removes a media item |
| `reorderMedia(array $order)` | Updates media positions |
| `updateMediaAlt(int $mediaId, string $alt)` | Updates alt text for a media item |
| `deleteProduct()` | Archives the product (with confirmation modal) |

| Computed Property | Purpose |
|-------------------|---------|
| `availableCollections` | All collections for the current store |
| `isEditing` | `bool` - whether editing or creating |

### Validation Rules

| Field | Rules |
|-------|-------|
| `title` | Required, string, max 255 |
| `descriptionHtml` | Nullable, string, max 65535 |
| `status` | Required, one of: draft, active, archived |
| `vendor` | Nullable, string, max 255 |
| `productType` | Nullable, string, max 255 |
| `tags` | Nullable, string |
| `handle` | Required, string, max 255, unique (excluding current product) |
| `variants.*.sku` | Nullable, string, max 255 |
| `variants.*.price` | Required, integer, min 0 |
| `variants.*.compareAtPrice` | Nullable, integer, min 0 |
| `variants.*.quantity` | Required, integer, min 0 |

### Two-Column Layout Wireframe

```
+------------------------------------------+-------------------+
|          LEFT COLUMN (2/3)               | RIGHT COLUMN (1/3)|
+------------------------------------------+-------------------+
|                                          |                   |
|  TITLE                                   |  STATUS CARD      |
|  +------------------------------------+  |  Status: [Draft v]|
|  | [Short Sleeve T-Shirt           ]  |  |                   |
|  +------------------------------------+  |  PUBLISHING CARD  |
|                                          |  Published at:    |
|  DESCRIPTION                             |  [datetime input]  |
|  +------------------------------------+  |                   |
|  | Describe your product...           |  |  ORGANIZATION     |
|  |                                    |  |  Vendor:          |
|  |                                    |  |  [Nike          ] |
|  |                                    |  |  Product type:    |
|  +------------------------------------+  |  [T-Shirts      ] |
|                                          |  Tags:            |
|  MEDIA                                   |  [summer, cotton ] |
|  +------------------------------------+  |  (comma-separated)|
|  | +--+ +--+ +--+ +--+               |  |                   |
|  | |  | |  | |  | |  |  (thumbnails) |  |  COLLECTIONS      |
|  | +--+ +--+ +--+ +--+               |  |  [x] Summer Sale  |
|  |                                    |  |  [ ] Winter       |
|  | [--- Drag & drop or click ---]    |  |  [x] New Arrivals |
|  +------------------------------------+  |                   |
|                                          |                   |
|  VARIANTS                                |                   |
|  Option name: [Size    ]                 |                   |
|  Values:      [S, M, L, XL         ]    |                   |
|  [trash]                                 |                   |
|                                          |                   |
|  [+ Add another option]                  |                   |
|                                          |                   |
|  Variant | SKU    | Price | Compare | Qty | Ship |          |
|  S / Red  | [   ] | [   ] | [     ] | [ ] | [x]  |          |
|  M / Red  | [   ] | [   ] | [     ] | [ ] | [x]  |          |
|  L / Red  | [   ] | [   ] | [     ] | [ ] | [x]  |          |
|                                          |                   |
|  SEO (collapsible)                       |                   |
|  > Search engine listing                 |                   |
|    URL handle: [short-sleeve-t-shirt]    |                   |
+------------------------------------------+-------------------+

+==================================================================+
| STICKY SAVE BAR (fixed bottom)                                    |
|                                      [Discard]  [Save]           |
+==================================================================+
```

### Left Column Sections

#### Title Input
- Flux field with label "Title"
- Flux input, placeholder "Short Sleeve T-Shirt"
- Flux error display for validation

#### Description
- Flux field with label "Description"
- Flux textarea, 8 rows, placeholder "Describe your product..."
- Future enhancement: WYSIWYG toolbar via Alpine.js; baseline is plain textarea

#### Media Section
A card with heading "Media" (size md).

**Upload area:** A dashed-border drop zone with upload icon and text "Drag and drop images or click to upload". A hidden file input (multiple, accept images) triggered by clicking the zone.

**Upload progress:** A progress bar shown during file upload.

**Media grid:** A 4-column grid (responsive: 2 on small, 3 on sm, 4 on lg) of thumbnails:
- Each item: square aspect ratio, object-cover, rounded
- On hover overlay shows: delete button (X icon, top-right), alt text edit button (pencil icon, bottom-left)
- Drag-to-reorder supported via Livewire sortable or Alpine.js Sortable

#### Variants Section
A card with heading "Variants" (size md).

**Options builder:** For each option, a row containing:
- "Option name" input field (placeholder: "Size")
- "Values" input field (placeholder: "S, M, L, XL" - comma-separated)
- Trash button to remove the option

Below options: "Add another option" ghost button with plus icon.

When options change, `generateVariants()` is called automatically via lifecycle hook.

**Variants table:** Shown when variants exist. Scrollable table with inline-editable fields:

| Column | Input Type | Description |
|--------|-----------|-------------|
| Variant | Read-only text | Combination name, e.g. "S / Red" |
| SKU | Text input (small) | Stock keeping unit |
| Price | Number input (small) | Price in major units |
| Compare at price | Number input (small) | Original price for sale display |
| Quantity | Number input (small) | On-hand inventory |
| Requires shipping | Checkbox | Whether variant needs shipping |

#### SEO Section
A collapsible section (collapsed by default, expands with animation):
- Toggle button: chevron icon + "Search engine listing" text
- When expanded: URL handle input field with placeholder "short-sleeve-t-shirt" and validation error display

### Right Column Sections

#### Status Card
- Flux field with label "Status"
- Flux select with options: Draft, Active, Archived

#### Publishing Card
- Flux field with label "Published at"
- Datetime-local input

#### Product Organization Card
- Vendor: Flux input, placeholder "Nike"
- Product type: Flux input, placeholder "T-Shirts"
- Tags: Flux input, placeholder "summer, cotton, sale" with description "Separate tags with commas"

#### Collections Card
- Flux field with label "Collections"
- For each available collection: a row with checkbox and collection title text
- Checkbox values bound to `collectionIds`

### Sticky Save Bar

Fixed at the bottom of the viewport. Appears when changes are detected.
- Left-aligned to account for sidebar width on desktop
- "Discard" button (ghost variant) - links back to products index
- "Save" button (primary variant) - calls `save()`, shows "Saving..." text during loading, disabled while loading

### Delete Confirmation Modal (Edit Only)

| Property | Value |
|----------|-------|
| Modal name | `confirm-delete-product` |
| Max width | md |
| Heading | "Delete this product?" |
| Body text | "This product will be archived. Products with existing orders cannot be permanently removed." |
| Cancel button | Ghost variant, closes modal |
| Confirm button | Danger variant, calls `deleteProduct` |

### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Page title | `heading` |
| Section titles | `heading` (size md) |
| All form labels | `field`, label via Flux |
| Text inputs | `input` |
| Textarea | `textarea` |
| Status/type selects | `select` |
| Checkboxes | `checkbox` |
| Validation errors | `field` error display |
| Tags description | `field` description |
| Save/discard buttons | `button` |
| Delete confirmation | `modal` |
| Section dividers | `separator` |
| Variant icons | `icon` |
| Status text | `text` |

### View States

| State | Behavior |
|-------|----------|
| Loading (save) | Save button disabled, text changes to "Saving..." |
| Loading (upload) | Progress bar shown in media section |
| Create mode | Empty form, no delete button, heading says "Add product" |
| Edit mode | Pre-filled form, delete button visible, heading shows product title |
| Validation error | Inline error messages below each invalid field |

---

## 5. Collections List and Edit

### 5.1 Collections List

**Route:** `GET /admin/collections`

**Livewire component:** `App\Livewire\Admin\Collections\Index`

**Blade view:** `resources/views/livewire/admin/collections/index.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search query |
| `statusFilter` | `string` | `all`, `active`, `archived` |

| Action | Behavior |
|--------|----------|
| `updatedSearch()` | Resets pagination |
| `deleteCollection(int $id)` | Deletes a collection after confirmation |

| Computed Property | Purpose |
|-------------------|---------|
| `collections` | Paginated, filtered, searched. Includes `withCount('products')`. |

#### Layout

Same pattern as Products list:
- Heading "Collections" with "Add collection" primary button
- Search input
- Status filter dropdown

#### Table Columns

| Column | Content |
|--------|---------|
| Title | Link to edit page |
| Products count | Number of products in collection |
| Status | Flux badge |
| Updated date | Relative timestamp |

**Empty state:** "Create your first collection" with CTA button.

### 5.2 Collection Edit/Create

**Route:** `GET /admin/collections/create` and `GET /admin/collections/{collection}/edit`

**Livewire component:** `App\Livewire\Admin\Collections\Form`

**Blade view:** `resources/views/livewire/admin/collections/form.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `collection` | `?Collection` | The collection being edited |
| `title` | `string` | Collection title |
| `handle` | `string` | URL slug |
| `descriptionHtml` | `string` | Collection description |
| `status` | `string` | `active` or `archived` |
| `productSearch` | `string` | Search input for product assignment |
| `assignedProductIds` | `array<int>` | Ordered list of product IDs in this collection |

| Action | Behavior |
|--------|----------|
| `save()` | Validates and saves the collection with product assignments |
| `addProduct(int $productId)` | Adds a product to the collection |
| `removeProduct(int $productId)` | Removes a product from the collection |
| `reorderProducts(array $order)` | Updates product positions |

| Computed Property | Purpose |
|-------------------|---------|
| `searchResults` | Products matching `productSearch` that are not already assigned |
| `assignedProducts` | Ordered collection of assigned Product models |

#### Layout Wireframe

```
+------------------------------------------+-------------------+
|          LEFT COLUMN (2/3)               | RIGHT COLUMN (1/3)|
+------------------------------------------+-------------------+
|  Title: [Summer Collection         ]     | Status: [Active v] |
|  Handle: [summer-collection        ]     |                   |
|  Description:                            |                   |
|  [textarea...                      ]     |                   |
|                                          |                   |
|  PRODUCTS                                |                   |
|  Search: [Search products...       ]     |                   |
|  +----------------------------------+   |                   |
|  | Blue Shirt           [Add]       |   |                   |
|  | Red Hat              [Add]       |   |                   |
|  +----------------------------------+   |                   |
|                                          |                   |
|  Assigned products:                      |                   |
|  [drag] [img] Sneakers         [remove]  |                   |
|  [drag] [img] Blue Shirt       [remove]  |                   |
+------------------------------------------+-------------------+

+==================================================================+
| STICKY SAVE BAR                          [Discard]  [Save]       |
+==================================================================+
```

**Left column:**
- Title input
- Handle input
- Description textarea
- Products assignment section:
  - Search input with live debounce (300ms)
  - Search results dropdown (conditionally shown): list of matching products with "Add" button
  - Assigned products list: each row shows product title, thumbnail, and a remove button. Rows are sortable via drag-to-reorder.

**Right column:**
- Status: Flux select with Active / Archived
- Sticky save bar (same pattern as product form)

---

## 6. Inventory Management

**Route:** `GET /admin/inventory`

**Livewire component:** `App\Livewire\Admin\Inventory\Index`

**Blade view:** `resources/views/livewire/admin/inventory/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search by product title or SKU |
| `stockFilter` | `string` | `all`, `in_stock`, `low_stock`, `out_of_stock` |

| Computed Property | Purpose |
|-------------------|---------|
| `inventoryItems` | Paginated inventory items with variant and product eager loaded, filtered by search and stock level |

### Wireframe

```
+------------------------------------------------------------------+
| Inventory                                                         |
+------------------------------------------------------------------+
| [search] Search by product or SKU   [Stock: All v]               |
+------------------------------------------------------------------+
| Product | Variant | SKU     | On Hand | Reserved | Policy | Act  |
|---------|---------|---------|---------|----------|--------|------|
| Shirt   | S/Blue  | SH-001  | [45]    | 3        | deny   | ...  |
| Shirt   | M/Blue  | SH-002  | [0]     | 0        | deny   | ...  |
| Hat     | Red     | HT-001  | [12]    | 1        | cont   | ...  |
+------------------------------------------------------------------+
```

### Layout

- Heading "Inventory"
- Search input and stock filter dropdown
- Table columns: Product, Variant, SKU, On Hand (inline-editable number input), Reserved, Policy (shown as Flux badge: "deny" or "continue"), Actions

---

## 7. Orders List

**Route:** `GET /admin/orders`

**Livewire component:** `App\Livewire\Admin\Orders\Index`

**Blade view:** `resources/views/livewire/admin/orders/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search by order number or customer email |
| `statusFilter` | `string` | `all`, `pending`, `paid`, `fulfilled`, `cancelled`, `refunded` |
| `sortField` | `string` | Sort column (default: `placed_at`) |
| `sortDirection` | `string` | `asc` or `desc` (default: `desc`) |

| Action | Behavior |
|--------|----------|
| `updatedSearch()` | Resets pagination |
| `updatedStatusFilter()` | Resets pagination |
| `sortBy(string $field)` | Updates sort |

| Computed Property | Purpose |
|-------------------|---------|
| `orders` | Paginated query with search, status filter, sorting. Eager loads `customer`. |

### Wireframe

```
+------------------------------------------------------------------+
| Orders                                                            |
+------------------------------------------------------------------+
| [search] Search by order # or email...                           |
+------------------------------------------------------------------+
| [All] [Pending] [Paid] [Fulfilled] [Cancelled] [Refunded]       |
+------------------------------------------------------------------+
| Order #  | Date           | Customer  | Payment   | Fulfill | Total  |
|----------|----------------|-----------|-----------|---------|--------|
| #1001    | Jan 15, 2:30 PM| John Doe  | Paid      | Unful.  | $131.36|
| #1000    | Jan 14, 9:15 AM| Guest     | Pending   | Unful.  | $45.00 |
+------------------------------------------------------------------+
| < 1 2 3 >                                                        |
+------------------------------------------------------------------+
```

### Filter Tabs

A horizontal row of tab-style buttons (plain Tailwind, not Flux component). Options: All, Pending, Paid, Fulfilled, Cancelled, Refunded. The active tab gets a bottom border and bold text. Inactive tabs use muted text color.

### Table Column Definitions

| Column | Content |
|--------|---------|
| Order number | Link to order detail (e.g., "#1001"), uses Livewire navigate |
| Date | `placed_at` formatted as "M j, Y g:i A" |
| Customer | Customer name or "Guest" |
| Financial status | Flux badge: pending=zinc, paid=green, refunded=yellow, cancelled=red |
| Fulfillment status | Flux badge: unfulfilled=zinc, partial=yellow, fulfilled=green |
| Total | Formatted currency amount |

Pagination below the table.

### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Page title | `heading` |
| Search field | `input` |
| Status badges | `badge` |
| Cell text | `text` |

---

## 8. Order Detail

**Route:** `GET /admin/orders/{order}`

**Livewire component:** `App\Livewire\Admin\Orders\Show`

**Blade view:** `resources/views/livewire/admin/orders/show.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `order` | `Order` | The order model (eager loaded with lines, payments, fulfillments, customer) |
| `fulfillmentLines` | `array` | Lines/quantities selected for fulfillment |
| `trackingCompany` | `string` | Tracking company for fulfillment |
| `trackingNumber` | `string` | Tracking number |
| `trackingUrl` | `string` | Tracking URL |
| `refundAmount` | `?int` | Custom refund amount |
| `refundReason` | `string` | Refund reason text |
| `refundLines` | `array` | Lines selected for refund |

| Action | Behavior |
|--------|----------|
| `mount(Order $order)` | Loads order with all relationships |
| `confirmPayment()` | Confirms bank transfer payment received (transitions financial_status to `paid`, commits inventory, dispatches `OrderPaid`) |
| `createFulfillment()` | Creates a fulfillment record for selected lines (enforces fulfillment guard) |
| `markAsShipped(int $fulfillmentId)` | Transitions fulfillment to `shipped`, sets `shipped_at` |
| `markAsDelivered(int $fulfillmentId)` | Transitions fulfillment to `delivered`, sets `delivered_at` |
| `createRefund()` | Creates a refund for selected lines or custom amount |
| `openFulfillmentModal()` | Opens the fulfillment modal |
| `openRefundModal()` | Opens the refund modal |

### Two-Column Layout Wireframe

```
+----------------------------------------------+--------------------+
|           LEFT COLUMN (2/3)                  | RIGHT COL (1/3)    |
+----------------------------------------------+--------------------+
| #1001  [Paid]  [Unfulfilled]                 | CUSTOMER           |
| Jan 15, 2024 2:30 PM                        | John Doe           |
|                                              | john@example.com   |
| [Create fulfillment]  [Refund]               | > View customer    |
|                                              |--------------------|
| TIMELINE                                     | SHIPPING ADDRESS   |
| o  Order placed - Jan 15, 2:30 PM           | 123 Main St        |
| |                                            | Springfield, IL    |
| o  Payment received - Jan 15, 2:31 PM       | 62701              |
| |                                            | United States      |
| o  (more events...)                          |--------------------|
|                                              | BILLING ADDRESS    |
| ORDER LINES                                  | 123 Main St        |
| IMG | Product      | Qty | Unit $ | Total   | Springfield, IL    |
| img | Blue Shirt   | 2   | $60.00 | $120.00 | 62701              |
| img | Red Hat      | 1   | $20.00 | $20.00  | United States      |
|                                              |                    |
|                    Subtotal     $140.00       |                    |
|                    Discount     -$12.00       |                    |
|                    Shipping      $5.00        |                    |
|                    Tax          $18.36        |                    |
|                    Total       $151.36        |                    |
|                                              |                    |
| PAYMENT DETAILS                              |                    |
| Method: Credit Card  [Paid]                  |                    |
| Amount: 151.36 EUR                           |                    |
| Ref: mock_xxx...                             |                    |
|                                              |                    |
| (If bank_transfer + pending:)                |                    |
| [Confirm Payment] button                     |                    |
+----------------------------------------------+--------------------+
```

### Left Column Sections

#### Order Heading
- Order number as a large heading
- Financial status badge (color mapped: paid=green, default=zinc)
- Fulfillment status badge (color mapped: fulfilled=green, default=zinc)
- Placed-at date as muted text

#### Action Buttons
Shown below the heading:
- "Confirm payment" button (primary, shown only when `payment_method = bank_transfer` AND `financial_status = pending`). On click, calls `confirmPayment()` which transitions the order to `paid`, commits inventory, and dispatches `OrderPaid`.
- "Create fulfillment" button (shown when order is not fully fulfilled AND fulfillment guard passes - see below)
- "Refund" button (ghost variant, shown when order is paid or partially_refunded)

#### Fulfillment Guard

A Flux `callout` (variant: warning) is displayed when the order's `financial_status` is NOT `paid` and NOT `partially_refunded`:

> **Cannot create fulfillment.** Payment must be confirmed before items can be fulfilled. Current financial status: *{financial_status}*.

When the guard condition is active, the "Create fulfillment" button is disabled (or hidden). This prevents shipping items before payment is secured.

#### Timeline
A vertical timeline with left border line and dot markers:
- Each event: title text (bold) and timestamp text (small, muted)
- Events include: Order placed, Payment received, Fulfillment created, Shipped, Delivered, Refunded (as applicable)

#### Order Lines Table

| Column | Content |
|--------|---------|
| Image | 40x40 product thumbnail |
| Product | Title and SKU |
| Quantity | Unit count |
| Unit Price | Formatted amount |
| Total | Line total formatted |

#### Order Summary
A right-aligned summary block below the lines table:
- Subtotal, Discount (negative), Shipping, Tax, Total (bold)
- Each row is a flex container with label left and amount right

#### Payment Details
A card showing:
- Payment method as text (Credit Card / PayPal / Bank Transfer), derived from `order.payment_method` enum
- Status as Flux badge (pending=zinc, captured=green, failed=red, refunded=yellow)
- Amount formatted as currency
- Mock reference ID (provider_payment_id)
- **"Confirm payment" button** (primary variant): Shown only when `payment_method = bank_transfer` AND `financial_status = pending`. Clicking calls `confirmPayment()`.

#### Fulfillment Cards

Each existing fulfillment is rendered as a card below the timeline. Each card shows:
- Fulfillment status badge (pending=zinc, shipped=blue, delivered=green)
- Tracking info (company, number, URL link)
- Line items included in the fulfillment
- **Action buttons on each fulfillment card:**
  - "Mark as shipped" button (shown when fulfillment status is `pending`). On click, calls `markAsShipped(fulfillmentId)`. Sets `shipped_at` timestamp.
  - "Mark as delivered" button (shown when fulfillment status is `shipped`). On click, calls `markAsDelivered(fulfillmentId)`. Sets `delivered_at` timestamp. When all fulfillments are delivered, the order's `fulfillment_status` transitions to `fulfilled`.

### Right Column Sections

#### Customer Card
- Heading "Customer" (size md)
- Separator
- Customer name (or "Guest")
- Customer email (muted text)
- "View customer" link (shown only if customer exists, links to customer detail page)

#### Shipping Address Card
- Heading "Shipping address" (size md)
- Separator
- Full address rendered from shipping_address_json: line1, city/state/zip, country

#### Billing Address Card
Same pattern as shipping address, using billing_address_json.

### Fulfillment Modal

| Property | Value |
|----------|-------|
| Modal name | `create-fulfillment` |
| Max width | lg |
| Heading | "Create fulfillment" |

**Content:**
- For each unfulfilled order line: a row with checkbox, line title + unfulfilled quantity text, and a number input for quantity to fulfill (min 1, max unfulfilled quantity)
- Separator
- Tracking company input field (placeholder: "UPS, FedEx, DHL...")
- Tracking number input field
- Tracking URL input field (type: url)
- Cancel button (ghost) and "Create fulfillment" button (primary)

### Refund Modal

| Property | Value |
|----------|-------|
| Modal name | `create-refund` |
| Max width | lg |
| Heading | "Refund order" |

**Content:**
- For each order line: a row with checkbox, line title text, and a number input for quantity to refund (min 0, max original quantity)
- Separator
- "Or enter custom amount" number input field (placeholder: "0.00")
- "Reason" textarea (3 rows, placeholder: "Reason for refund...")
- Cancel button (ghost) and "Create refund" button (danger)

### Flux Component Mapping

| UI Element | Flux Component |
|-----------|----------------|
| Order number heading | `heading` |
| Status badges | `badge` |
| Action buttons | `button` |
| Section headings | `heading` (size md) |
| Section dividers | `separator` |
| Text content | `text` |
| Both modals | `modal` |
| Modal form fields | `field`, `input`, `textarea` |
| Line checkboxes | `checkbox` |
| Modal buttons | `button` |
| Icons | `icon` |
| Callout messages | `callout` |

---

## 9. Customers List and Detail

### 9.1 Customers List

**Route:** `GET /admin/customers`

**Livewire component:** `App\Livewire\Admin\Customers\Index`

**Blade view:** `resources/views/livewire/admin/customers/index.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search by name or email |

| Computed Property | Purpose |
|-------------------|---------|
| `customers` | Paginated, searched customers with `withCount('orders')` and `withSum('orders', 'total_amount')` |

#### Layout

- Heading "Customers"
- Search input
- Table columns: Name, Email, Orders count, Total spent (formatted), Created date
- Each row links to customer detail

### 9.2 Customer Detail

**Route:** `GET /admin/customers/{customer}`

**Livewire component:** `App\Livewire\Admin\Customers\Show`

**Blade view:** `resources/views/livewire/admin/customers/show.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `customer` | `Customer` | The customer model |
| `editingAddress` | `?CustomerAddress` | Address currently being edited |
| `addressLabel` | `string` | Label for address form |
| `addressJson` | `array` | Address fields being edited |

| Action | Behavior |
|--------|----------|
| `mount(Customer $customer)` | Loads customer with orders and addresses |
| `openAddressForm(?CustomerAddress $address)` | Opens address modal for create/edit |
| `saveAddress()` | Saves the address |
| `deleteAddress(int $addressId)` | Deletes an address |
| `setDefaultAddress(int $addressId)` | Sets address as default |

#### Two-Column Layout Wireframe

```
+----------------------------------------------+--------------------+
|           LEFT COLUMN (2/3)                  | RIGHT COL (1/3)    |
+----------------------------------------------+--------------------+
| CUSTOMER INFO                                | ADDRESSES          |
| Name: Jane Smith                             | Home (default)     |
| Email: jane@example.com                      | 123 Main St        |
| Created: Jan 1, 2024                         | Springfield, IL    |
| Marketing: [Opted In]                        | [Edit] [Delete]    |
|                                              |                    |
| ORDER HISTORY                                | Office             |
| Order # | Date      | Status | Total         | 456 Corp Ave       |
| #1005   | Jan 20    | Paid   | $89.00        | Chicago, IL        |
| #1001   | Jan 15    | Paid   | $131.36       | [Edit] [Delete]    |
| < 1 2 >                                     |                    |
|                                              | [+ Add address]    |
+----------------------------------------------+--------------------+
```

**Left column:**
- Customer info card: Name, email, created date, marketing opt-in status (Flux badge)
- Order history table: columns Order #, Date, Status, Total. Each order links to order detail. Paginated.

**Right column:**
- Addresses card: List of addresses, each showing label, full address text, default badge (if `is_default`), Edit and Delete buttons
- "Add address" button opens address modal

#### Address Modal

| Property | Value |
|----------|-------|
| Modal name | `address-form` |
| Max width | md |
| Heading | "Edit address" or "Add address" (dynamic) |

**Form fields:**
- Label input (placeholder: "Home, Office...")
- Address line 1 input
- Address line 2 input
- City and State/Province inputs (side by side, 2-column grid)
- ZIP/Postal code and Country select (side by side, 2-column grid)
- Cancel button (ghost) and "Save" button (primary)

---

## 10. Discounts List and Form

### 10.1 Discounts List

**Route:** `GET /admin/discounts`

**Livewire component:** `App\Livewire\Admin\Discounts\Index`

**Blade view:** `resources/views/livewire/admin/discounts/index.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search by discount code |
| `statusFilter` | `string` | `all`, `active`, `expired`, `scheduled` |

| Computed Property | Purpose |
|-------------------|---------|
| `discounts` | Paginated, filtered discounts |

#### Layout

- Heading "Discounts" with "Create discount" primary button
- Search input and status filter

#### Table Columns

| Column | Content |
|--------|---------|
| Code | Discount code or "Automatic" label |
| Type | Flux badge: "Code" or "Automatic" |
| Value | e.g., "10%" or "$5.00" or "Free shipping" |
| Usage | "3 / 100" (used / limit) or "3 / unlimited" |
| Status | Flux badge: active=green, expired=red, scheduled=yellow |
| Dates | Start and end dates |

### 10.2 Discount Form

**Route:** `GET /admin/discounts/create` and `GET /admin/discounts/{discount}/edit`

**Livewire component:** `App\Livewire\Admin\Discounts\Form`

**Blade view:** `resources/views/livewire/admin/discounts/form.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `discount` | `?Discount` | The discount being edited |
| `type` | `string` | `code` or `automatic` |
| `code` | `string` | Discount code (if type is code) |
| `valueType` | `string` | `percent`, `fixed`, `free_shipping` |
| `valueAmount` | `?int` | Discount value (percentage or fixed amount) |
| `minimumPurchaseAmount` | `?int` | Minimum cart value for eligibility |
| `specificProductIds` | `array<int>` | Specific products the discount applies to |
| `specificCollectionIds` | `array<int>` | Specific collections the discount applies to |
| `usageLimit` | `?int` | Maximum total uses |
| `onePerCustomer` | `bool` | Limit to one use per customer |
| `startsAt` | `string` | Start datetime |
| `endsAt` | `?string` | End datetime (optional) |
| `isActive` | `bool` | Whether the discount is active |
| `productSearch` | `string` | Search for products to add |
| `collectionSearch` | `string` | Search for collections to add |

| Action | Behavior |
|--------|----------|
| `save()` | Validates and saves the discount |
| `generateCode()` | Auto-generates a random discount code |
| `addProduct(int $productId)` | Adds product to specific products list |
| `removeProduct(int $productId)` | Removes product |
| `addCollection(int $collectionId)` | Adds collection to specific collections |
| `removeCollection(int $collectionId)` | Removes collection |

#### Layout Wireframe (Single Column)

```
+==================================================================+
| TYPE CARD                                                         |
|  ( ) Discount code - Customers enter a code at checkout          |
|  (o) Automatic discount - Applied automatically at checkout      |
+==================================================================+
| CODE CARD (shown when type = "code")                             |
|  Discount code: [SUMMER20             ]  [Generate]              |
+==================================================================+
| VALUE CARD                                                        |
|  ( ) Percentage                                                   |
|  (o) Fixed amount                                                 |
|  ( ) Free shipping                                                |
|                                                                   |
|  Amount: [5.00                        ] (hidden for free shipping)|
+==================================================================+
| CONDITIONS CARD                                                   |
|  Minimum purchase amount: [0.00       ]                          |
|  (Leave empty for no minimum)                                     |
|                                                                   |
|  Specific products:                                               |
|  [Search products...          ]                                   |
|  +---results dropdown---+                                         |
|  Selected: Blue Shirt [x]  Red Hat [x]                           |
|                                                                   |
|  Specific collections: (same pattern as products)                 |
+==================================================================+
| USAGE LIMITS CARD                                                 |
|  Total usage limit: [100              ] (or leave blank)         |
|  [x] Limit to one use per customer                               |
+==================================================================+
| ACTIVE DATES CARD                                                 |
|  Start date: [2024-01-15 00:00       ]                           |
|  End date:   [2024-02-15 00:00       ] (or leave blank)          |
+==================================================================+
| STATUS                                                            |
|  [switch: ON]  Active                                             |
+==================================================================+
| STICKY SAVE BAR                          [Discard]  [Save]       |
+==================================================================+
```

#### Section Details

**Type section:** Two radio buttons - "Discount code" (description: "Customers enter a code at checkout") and "Automatic discount" (description: "Applied automatically at checkout"). Selecting type is live-bound.

**Code input:** Shown only when type = "code". Text input with placeholder "SUMMER20" and a "Generate" ghost button that auto-generates a random code.

**Value section:** Three radio buttons for value type: Percentage, Fixed amount, Free shipping. When not free shipping, a number input appears for the amount (label changes based on type: "Percentage" or "Amount").

**Conditions section:**
- Minimum purchase amount: number input with description "Leave empty for no minimum"
- Specific products: search input with live debounce. Results appear in a scrollable dropdown. Selected products shown as a list with remove (X) buttons.
- Specific collections: same pattern as products.

**Usage limits section:**
- Total usage limit number input (placeholder: "Unlimited")
- "Limit to one use per customer" checkbox

**Active dates section:**
- Start date: datetime-local input
- End date: datetime-local input with description "Leave empty for no end date"

**Status:** Toggle switch with text label "Active" or "Disabled".

Sticky save bar at the bottom (same pattern as product form).

---

## 11. Settings Pages

### 11.1 General Settings

**Route:** `GET /admin/settings`

**Livewire component:** `App\Livewire\Admin\Settings\General`

**Blade view:** `resources/views/livewire/admin/settings/general.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `storeName` | `string` | Store display name |
| `storeHandle` | `string` | Store handle (read-only or with warning) |
| `defaultCurrency` | `string` | Currency code (e.g., "EUR") |
| `defaultLocale` | `string` | Locale code (e.g., "en") |
| `timezone` | `string` | Timezone string |

| Action | Behavior |
|--------|----------|
| `save()` | Validates and saves store settings |

#### Layout

Two-column layout per section: left side has section label and description, right side has form fields.

**Store details section:**
- Left: Heading "Store details" + description "Basic information about your store."
- Right: Store name input, Store handle input (disabled, with description: "The store handle cannot be changed after creation.")

Separator between sections.

**Defaults section:**
- Left: Heading "Defaults" + description "Currency, language, and timezone settings."
- Right:
  - Default currency: Select (EUR/USD/GBP and more)
  - Default locale: Select (English/German/French and more)
  - Timezone: Select (populated from PHP timezone list)

Save button at the bottom (primary variant, right-aligned).

### 11.2 Domains Settings

Domains is a tab within the Settings page (`/admin/settings`), not a separate route. The Settings page has tabs: General, Domains, Shipping, Taxes, Checkout, Notifications.

**Livewire component:** `App\Livewire\Admin\Settings\Domains`

**Blade view:** `resources/views/livewire/admin/settings/domains.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `domains` | `Collection` | Store domains |
| `newHostname` | `string` | Hostname for new domain |
| `newType` | `string` | Type for new domain: `storefront`, `admin`, `api` |

| Action | Behavior |
|--------|----------|
| `addDomain()` | Creates a new domain record |
| `removeDomain(int $domainId)` | Removes a domain |
| `setPrimary(int $domainId)` | Sets domain as primary |

#### Layout

- Heading "Domains"
- Table of existing domains:

| Column | Content |
|--------|---------|
| Hostname | Domain hostname |
| Type | Flux badge: storefront/admin/api |
| Primary | Flux badge (green) "Primary" if `is_primary` |
| TLS | Flux badge showing TLS mode |
| Actions | "Set Primary" button, "Delete" button |

- "Add domain" button opens a modal

#### Add Domain Modal

| Property | Value |
|----------|-------|
| Modal name | `add-domain` |
| Max width | md |
| Heading | "Add domain" |

**Form fields:**
- Hostname input (placeholder: "shop.example.com")
- Type select: Storefront, Admin, API
- Cancel button (ghost) and "Add domain" button (primary)

### 11.3 Shipping Settings

**Route:** `GET /admin/settings/shipping`

**Livewire component:** `App\Livewire\Admin\Settings\Shipping`

**Blade view:** `resources/views/livewire/admin/settings/shipping.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `zones` | `Collection` | Shipping zones with rates |
| `editingZone` | `?ShippingZone` | Zone being edited |
| `zoneName` | `string` | Name for zone form |
| `zoneCountries` | `array` | Selected countries for zone |
| `editingRate` | `?ShippingRate` | Rate being edited |
| `rateName` | `string` | Rate name |
| `rateType` | `string` | `flat`, `weight`, `price`, `carrier` |
| `rateConfig` | `array` | Rate configuration (depends on type) |
| `rateActive` | `bool` | Whether rate is active |
| `testAddress` | `array` | Address fields for test tool |
| `testResult` | `?array` | Result of test address lookup |

| Action | Behavior |
|--------|----------|
| `openZoneModal(?ShippingZone $zone)` | Opens zone create/edit modal |
| `saveZone()` | Saves shipping zone |
| `deleteZone(int $zoneId)` | Deletes a shipping zone |
| `openRateModal(int $zoneId, ?ShippingRate $rate)` | Opens rate create/edit modal |
| `saveRate()` | Saves shipping rate |
| `deleteRate(int $rateId)` | Deletes a rate |
| `testShippingAddress()` | Tests which zone/rate matches a given address |

#### Layout Wireframe

```
+==================================================================+
| Shipping                                         [+ Add zone]    |
+==================================================================+
|                                                                   |
| +--------------------------------------------------------------+ |
| | ZONE: Domestic                            [Edit] [Delete]    | |
| | Countries: US, CA                                             | |
| |                                                               | |
| | Name      | Type    | Config           | Active | Actions    | |
| |-----------|---------|------------------|--------|------------|  |
| | Standard  | flat    | $5.00            | [on]   | [Ed] [Del]| |
| | Express   | flat    | $15.00           | [on]   | [Ed] [Del]| |
| | [+ Add rate]                                                  | |
| +--------------------------------------------------------------+ |
|                                                                   |
| +--------------------------------------------------------------+ |
| | ZONE: Europe                              [Edit] [Delete]    | |
| | Countries: DE, FR, GB, ...                                    | |
| | (rates table...)                                              | |
| +--------------------------------------------------------------+ |
|                                                                   |
| TEST SHIPPING ADDRESS                                             |
| Country: [v]  State: [    ]  City: [    ]  ZIP: [    ]           |
| [Test]                                                            |
| Result: Matched zone: Domestic                                    |
|         Standard - $5.00 / Express - $15.00                      |
+==================================================================+
```

Each zone is displayed as a card with:
- Zone name heading and Edit/Delete buttons in the header
- Countries list as muted text
- Rates table inside the zone card with columns: Name, Type (as badge), Config summary, Active (toggle switch), Actions (Edit/Delete buttons)
- "Add rate" button at the bottom of each zone card

"Add zone" button at the top of the page.

#### Zone Modal

| Property | Value |
|----------|-------|
| Modal name | `zone-form` |
| Max width | md |
| Heading | "Edit shipping zone" or "Add shipping zone" (dynamic) |

**Form fields:**
- Zone name input (placeholder: "Domestic, Europe, International...")
- Countries: scrollable checklist of all available countries with checkboxes
- Cancel button (ghost) and "Save zone" button (primary)

#### Rate Modal

| Property | Value |
|----------|-------|
| Modal name | `rate-form` |
| Max width | md |
| Heading | "Edit shipping rate" or "Add shipping rate" (dynamic) |

**Form fields:**
- Rate name input (placeholder: "Standard, Express...")
- Rate type select: Flat rate, Weight-based, Price-based, Carrier-calculated
- Dynamic config fields based on rate type:
  - **Flat:** Price input
  - **Weight-based:** Min weight (g) input, Max weight (g) input, Price input
  - **Price-based:** Min order amount input, Max order amount input, Price input
  - **Carrier:** Info callout: "Carrier-calculated rates require a carrier integration to be configured."
- Active toggle switch with "Active" text label
- Cancel button (ghost) and "Save rate" button (primary)

#### Test Shipping Address Tool

A card section at the bottom of the page:
- Heading "Test shipping address"
- Description: "Enter an address to see which shipping zone and rates match."
- 2-column grid of fields: Country (select), State/Region (input), City (input), ZIP/Postal code (input)
- "Test" button
- Result area (shown after test):
  - If matched: "Matched zone: [zone name]" and list of available rates with prices
  - If no match: Warning callout "No shipping zone matches this address."

### 11.4 Tax Settings

**Route:** `GET /admin/settings/taxes`

**Livewire component:** `App\Livewire\Admin\Settings\Taxes`

**Blade view:** `resources/views/livewire/admin/settings/taxes.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `mode` | `string` | `manual` or `provider` |
| `pricesIncludeTax` | `bool` | Whether prices include tax |
| `provider` | `string` | Provider name (e.g., `stripe_tax`) |
| `providerApiKey` | `string` | API key for tax provider |
| `manualRates` | `array` | Array of `{zone_name, rate_percentage}` |

| Action | Behavior |
|--------|----------|
| `save()` | Saves tax settings |
| `addManualRate()` | Adds a new empty rate row |
| `removeManualRate(int $index)` | Removes a manual rate |

#### Layout Wireframe

```
+==================================================================+
| TAXES                                                             |
+==================================================================+
| MODE SELECTION                                                    |
|  ( ) Manual tax rates - Define tax rates per zone manually       |
|  (o) Tax provider - Use an automated tax calculation service     |
+==================================================================+
| MANUAL RATES TABLE (shown when mode = "manual")                  |
| Zone name       | Rate (%)    |                                   |
|-----------------|-------------|                                   |
| [EU           ] | [19.00    ] | [trash]                          |
| [US-CA        ] | [7.25     ] | [trash]                          |
| [+ Add rate]                                                      |
+==================================================================+
| PROVIDER CONFIG (shown when mode = "provider")                   |
|  Provider: [Stripe Tax v]                                        |
|  API key:  [*************]                                       |
+==================================================================+
| [separator]                                                       |
| TAX-INCLUSIVE TOGGLE                                              |
|  [switch: ON]  Prices include tax                                |
|  Description: When enabled, the listed price includes tax.       |
|  Tax is calculated backwards from the price.                      |
+==================================================================+
| [Save]                                                            |
+==================================================================+
```

**Mode selection:** Two radio buttons - "Manual tax rates" (description: "Define tax rates per zone manually") and "Tax provider" (description: "Use an automated tax calculation service"). Live-bound.

**Manual rates table:** Shown when mode = "manual". Inline-editable table with columns: Zone name (text input), Rate % (number input with step 0.01). Each row has a trash button. "Add rate" ghost button with plus icon.

**Provider config:** Shown when mode = "provider". Provider select (Stripe Tax / None) and API key password input.

**Tax-inclusive toggle:** Toggle switch with "Prices include tax" label and description text explaining the behavior.

Save button at the bottom.

---

## 12. Themes Management

### 12.1 Themes List

**Route:** `GET /admin/themes`

**Livewire component:** `App\Livewire\Admin\Themes\Index`

**Blade view:** `resources/views/livewire/admin/themes/index.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `themes` | `Collection` | All themes for the store |

| Action | Behavior |
|--------|----------|
| `publishTheme(int $themeId)` | Publishes a theme (sets as active) |
| `duplicateTheme(int $themeId)` | Creates a copy of the theme |
| `deleteTheme(int $themeId)` | Deletes a theme |

#### Layout Wireframe

```
+==================================================================+
| Themes                                                            |
+==================================================================+
|                                                                   |
| +------------------+ +------------------+ +------------------+   |
| | [preview image]  | | [preview image]  | | [preview image]  |   |
| |                  | |                  | |                  |   |
| | My Theme   v1.0  | | Dark Theme v1.2  | | Minimal    v2.0  |   |
| | [Published]      | | [Draft]          | | [Draft]          |   |
| |                  | |                  | |                  |   |
| | [Customize] [...] | | [Customize] [...] | | [Customize] [...] |   |
| +------------------+ +------------------+ +------------------+   |
|                                                                   |
+==================================================================+
```

**Theme grid:** Responsive 3-column grid (1 on mobile, 2 on md, 3 on lg).

Each theme card contains:
- **Thumbnail area:** Aspect-video container with theme preview image or placeholder
- **Info section:**
  - Theme name (heading, size md) and version text
  - Status badge (published=green, draft=zinc)
- **Actions:**
  - "Customize" primary button (links to theme editor)
  - More actions dropdown (triggered by ellipsis icon button):
    - "Preview" menu item
    - "Publish" menu item (hidden if already published)
    - "Duplicate" menu item
    - Separator
    - "Delete" menu item (styled red/danger)

The published theme card has a blue ring border highlight.

### 12.2 Theme Editor

**Route:** `GET /admin/themes/{theme}/editor`

**Livewire component:** `App\Livewire\Admin\Themes\Editor`

**Blade view:** `resources/views/livewire/admin/themes/editor.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `theme` | `Theme` | The theme being edited |
| `sections` | `array` | List of theme sections from settings schema |
| `selectedSection` | `?string` | Currently selected section key |
| `sectionSettings` | `array` | Settings values for the selected section |
| `previewUrl` | `string` | URL for the live preview iframe |

| Action | Behavior |
|--------|----------|
| `mount(Theme $theme)` | Loads theme and its sections |
| `selectSection(string $sectionKey)` | Changes the selected section and loads its settings |
| `updateSetting(string $key, mixed $value)` | Updates a setting value |
| `save()` | Saves all section settings to theme_settings |
| `publish()` | Publishes the theme |
| `refreshPreview()` | Reloads the preview iframe |

#### Layout Wireframe (Full Width, Three-Panel)

```
+==================================================================+
| [<- Back to themes]                     [Save]  [Save & publish] |
+==================================================================+
| SECTIONS   |  LIVE PREVIEW (iframe)       |  SETTINGS PANEL      |
| (264px)    |  (flexible width)            |  (320px)             |
|            |                              |                      |
| Header     |  +------------------------+ |  Header Settings     |
| Hero       |  |                        | |  [separator]         |
| Features*  |  |  Live storefront       | |                      |
| Products   |  |  preview rendered      | |  Logo text:          |
| Footer     |  |  in an iframe          | |  [My Store        ]  |
|            |  |                        | |                      |
|            |  |                        | |  Background color:   |
|            |  |                        | |  [color picker]      |
|            |  |                        | |                      |
|            |  |                        | |  Show search:        |
|            |  |                        | |  [x] checkbox        |
|            |  |                        | |                      |
|            |  +------------------------+ |  Layout:             |
|            |                              |  [Centered       v]  |
+==================================================================+
  * = currently selected section (highlighted)
```

**Full-width layout** (no standard admin shell padding). Three-panel layout:

**Top toolbar:** A horizontal bar with:
- "Back to themes" button (ghost, with left arrow icon) linking to themes index
- "Save" ghost button and "Save and publish" primary button

**Left panel (264px):** Section list
- Heading "Sections"
- Vertical list of section buttons
- Active section gets highlighted background and bold text
- Clicking a section calls `selectSection()`

**Center panel (flexible):** Live preview
- Background color behind the iframe
- Full-height iframe loading the storefront preview URL
- Iframe has rounded border and shadow

**Right panel (320px):** Settings form
- Shows settings for the selected section
- Heading with section label
- Separator
- Dynamic form fields based on section field definitions:

| Field Type | UI Element |
|-----------|------------|
| `text` | Flux input (live-debounced at 500ms) |
| `textarea` | Flux textarea (live-debounced at 500ms, 3 rows) |
| `color` | Native color picker input |
| `select` | Flux select with options from field definition |
| `checkbox` | Flux checkbox |

- When no section is selected: centered text "Select a section to edit its settings."

---

## 13. Pages Management

### 13.1 Pages List

**Route:** `GET /admin/pages`

**Livewire component:** `App\Livewire\Admin\Pages\Index`

**Blade view:** `resources/views/livewire/admin/pages/index.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `search` | `string` | Search by page title |

| Computed Property | Purpose |
|-------------------|---------|
| `pages` | Paginated pages for the current store |

#### Layout

- Heading "Pages" with "Add page" primary button
- Search input
- Table columns: Title (link to edit), Handle, Status (Flux badge), Updated date
- Pagination

### 13.2 Page Form

**Route:** `GET /admin/pages/create` and `GET /admin/pages/{page}/edit`

**Livewire component:** `App\Livewire\Admin\Pages\Form`

**Blade view:** `resources/views/livewire/admin/pages/form.blade.php`

#### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `page` | `?Page` | Page being edited |
| `title` | `string` | Page title |
| `handle` | `string` | URL handle |
| `bodyHtml` | `string` | Page body content (HTML) |
| `status` | `string` | `draft` or `active` |
| `publishedAt` | `?string` | Publish date |

| Action | Behavior |
|--------|----------|
| `save()` | Validates and saves the page |
| `deletePage()` | Deletes the page |

#### Layout (Two-Column)

**Left column (2/3):**
- Title: Flux input
- Handle: Flux input
- Body: Flux textarea (16 rows)

**Right column (1/3):**
- Status: Flux select with Draft/Active
- Published at: Datetime-local input

Sticky save bar (same pattern as other forms).

---

## 14. Navigation Management

**Route:** `GET /admin/navigation`

**Livewire component:** `App\Livewire\Admin\Navigation\Index`

**Blade view:** `resources/views/livewire/admin/navigation/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `menus` | `Collection` | Navigation menus (Main Menu, Footer Menu) |
| `editingMenu` | `?NavigationMenu` | Menu being edited |
| `menuItems` | `array` | Nested array of menu items for the editing menu |
| `editingItem` | `?array` | Item currently being edited in the modal |
| `editingItemIndex` | `?int` | Index of item being edited |
| `itemLabel` | `string` | Item label |
| `itemType` | `string` | `link`, `page`, `collection`, `product` |
| `itemUrl` | `string` | URL if type is link |
| `itemResourceId` | `?int` | Resource ID if type is page/collection/product |

| Action | Behavior |
|--------|----------|
| `selectMenu(int $menuId)` | Loads a menu for editing |
| `addItem()` | Opens the item form modal in create mode |
| `editItem(int $index)` | Opens the item form modal in edit mode |
| `saveItem()` | Saves the item (add or update) |
| `removeItem(int $index)` | Removes an item |
| `reorderItems(array $order)` | Updates item positions (supports nesting) |
| `saveMenu()` | Persists all menu items |

### Layout Wireframe

```
+==================================================================+
| Navigation                                                        |
+==================================================================+
|                                                                   |
| MENUS                                                             |
| +----------------------------+  +----------------------------+   |
| | Main Menu        [Edit]    |  | Footer Menu       [Edit]   |   |
| +----------------------------+  +----------------------------+   |
|                                                                   |
| MENU EDITOR (shown when a menu is selected)                      |
| +--------------------------------------------------------------+ |
| | Main Menu                                    [+ Add item]    | |
| |                                                               | |
| | [drag] Home               link: /            [edit] [trash]  | |
| | [drag] Products           collection: All    [edit] [trash]  | |
| | [drag] About Us           page: About        [edit] [trash]  | |
| | [drag] Contact            link: /contact     [edit] [trash]  | |
| |                                                               | |
| |                                            [Save menu]       | |
| +--------------------------------------------------------------+ |
+==================================================================+
```

**Menu list:** Each menu displayed as a card with title and "Edit" button.

**Menu editor (shown when a menu is selected):**
- Menu title as heading
- "Add item" button (ghost, plus icon) in the header
- Sortable list of menu items, each row containing:
  - Drag handle (bars icon)
  - Item label (bold text)
  - Item type and target (small, muted text: e.g., "link: /about" or "collection: Summer Sale")
  - Edit button (pencil icon) and Delete button (trash icon)
- "Save menu" primary button at the bottom

#### Menu Item Form Modal

| Property | Value |
|----------|-------|
| Modal name | `item-form` |
| Max width | md |
| Heading | "Edit menu item" or "Add menu item" (dynamic) |

**Form fields:**
- Label input (placeholder: "About Us")
- Type select: Custom link, Page, Collection, Product (live-bound)
- Conditional fields based on type:
  - **Custom link:** URL input (placeholder: "https://...")
  - **Page/Collection/Product:** Resource select dropdown populated with available resources of that type
- Cancel button (ghost) and "Save item" button (primary)

---

## 15. Apps Page

**Route:** `GET /admin/apps`

**Livewire component:** `App\Livewire\Admin\Apps\Index`

**Blade view:** `resources/views/livewire/admin/apps/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `installedApps` | `Collection` | Apps installed for this store |

| Action | Behavior |
|--------|----------|
| `uninstallApp(int $appId)` | Uninstalls an app (with confirmation) |

### Layout Wireframe

```
+==================================================================+
| Apps                                                              |
+==================================================================+
|                                                                   |
| +--------------------------------------------------------------+ |
| | [icon]  My Integration App                         [Active]  | |
| |         Installed 3 months ago                               | |
| +--------------------------------------------------------------+ |
| +--------------------------------------------------------------+ |
| | [icon]  Analytics Plugin                          [Inactive] | |
| |         Installed 1 week ago                                 | |
| +--------------------------------------------------------------+ |
|                                                                   |
+==================================================================+
```

List of installed app cards, each containing:
- App icon placeholder (squares icon in a rounded container)
- App name (heading, size md)
- Install date (relative timestamp, muted text)
- Status badge (active=green, inactive=zinc)

Each app links to a detail page showing:
- Scopes granted (list of permission strings)
- Webhook subscriptions table: event type, URL, status
- Usage stats (API calls count, last call timestamp)

**Empty state:** "No apps installed" with descriptive text.

---

## 16. Developers Page

**Route:** `GET /admin/developers`

**Livewire component:** `App\Livewire\Admin\Developers\Index`

**Blade view:** `resources/views/livewire/admin/developers/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `tokens` | `Collection` | API tokens for the store |
| `webhooks` | `Collection` | Webhook subscriptions |
| `newTokenName` | `string` | Name for new token |
| `generatedToken` | `?string` | Newly generated token (shown once) |
| `webhookEventType` | `string` | Event type for webhook form |
| `webhookUrl` | `string` | URL for webhook form |
| `editingWebhook` | `?WebhookSubscription` | Webhook being edited |

| Action | Behavior |
|--------|----------|
| `generateToken()` | Creates a new API token |
| `revokeToken(int $tokenId)` | Revokes a token |
| `openWebhookModal(?WebhookSubscription $webhook)` | Opens webhook create/edit modal |
| `saveWebhook()` | Saves webhook subscription |
| `deleteWebhook(int $webhookId)` | Deletes a webhook |

### Layout Wireframe

```
+==================================================================+
| Developers                                                        |
+==================================================================+
|                                                                   |
| API TOKENS                                                        |
| Manage personal access tokens for the Admin API.                 |
|                                                                   |
| Name           | Last used      | Created       | Actions        |
|----------------|----------------|---------------|----------------|
| My integration | 2 hours ago    | Jan 1, 2024   | [Revoke]       |
| CI Pipeline    | Never          | Feb 10, 2024  | [Revoke]       |
|                                                                   |
| [Generate new token]                                              |
|                                                                   |
| (after generating, shown once:)                                   |
| [!] Copy this token now. It will not be shown again.             |
| [sk_live_abc123xyz789...]                                        |
|                                                                   |
| [separator]                                                       |
|                                                                   |
| WEBHOOKS                                                          |
| Manage webhook subscriptions for real-time event notifications.  |
|                                                                   |
| Event type           | URL                    | Status  | Act    |
|----------------------|------------------------|---------|--------|
| order.created        | https://ex.com/hook    | Active  | [E][D] |
| product.updated      | https://ex.com/prod    | Failing | [E][D] |
|                                                                   |
| [+ Add webhook]                                                   |
+==================================================================+
```

Two sections separated by a Flux separator.

**API Tokens section:**
- Heading "API tokens" (size lg)
- Description text
- Token table with columns: Name, Last used (relative or "Never"), Created (date), Actions (Revoke button)
- "Generate new token" button opens modal
- When a token is generated: a warning callout is displayed with the token value and a note that it will only be shown once

#### Generate Token Modal

| Property | Value |
|----------|-------|
| Modal name | `generate-token` |
| Max width | md |
| Heading | "Generate API token" |

**Form fields:**
- Token name input (placeholder: "My integration")
- Cancel button (ghost) and "Generate" button (primary)

**Webhooks section:**
- Heading "Webhooks" (size lg)
- Description text
- Webhook table with columns:

| Column | Content |
|--------|---------|
| Event type | e.g., `order.created`, `product.updated` |
| URL | Endpoint URL |
| Status | Flux badge: active=green, failing=red |
| Actions | Edit and Delete buttons |

- "Add webhook" button opens modal

#### Webhook Modal

| Property | Value |
|----------|-------|
| Modal name | `webhook-form` |
| Max width | md |
| Heading | "Edit webhook" or "Add webhook" (dynamic) |

**Form fields:**
- Event type select with options: order.created, order.updated, order.cancelled, product.created, product.updated, product.deleted, customer.created, checkout.completed, fulfillment.created, refund.created
- Endpoint URL input (type: url, placeholder: "https://example.com/webhooks")
- Cancel button (ghost) and "Save" button (primary)

---

## 17. Analytics Page

**Route:** `GET /admin/analytics`

**Livewire component:** `App\Livewire\Admin\Analytics\Index`

**Blade view:** `resources/views/livewire/admin/analytics/index.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `dateRange` | `string` | Date range preset |
| `customStartDate` | `?string` | Custom start date |
| `customEndDate` | `?string` | Custom end date |
| `channelFilter` | `string` | Sales channel filter (`all`, `storefront`, `api`) |
| `deviceFilter` | `string` | Device filter (`all`, `desktop`, `mobile`, `tablet`) |
| `totalSales` | `int` | Total sales for the period |
| `ordersCount` | `int` | Orders count |
| `averageOrderValue` | `int` | AOV |
| `conversionRate` | `float` | Conversion rate percentage |
| `salesChartData` | `array` | Daily sales chart data |
| `topProducts` | `Collection` | Top products by revenue |
| `topReferrers` | `array` | Top traffic sources |
| `isExporting` | `bool` | Whether an export is in progress |
| `exportUrl` | `?string` | Download URL when export is ready |

| Action | Behavior |
|--------|----------|
| `updatedDateRange()` | Reloads analytics data |
| `updatedChannelFilter()` | Reloads with channel filter |
| `updatedDeviceFilter()` | Reloads with device filter |
| `loadAnalytics()` | Master method to reload all analytics data |
| `exportCsv()` | Dispatches an async job to generate a CSV export |

### Layout Wireframe

```
+==================================================================+
| Analytics                                                         |
+==================================================================+
| [Today v]  [Channel: All v]  [Device: All v]  [Export CSV]       |
+==================================================================+
|                                                                   |
| KPI TILES (same as Dashboard + Conversion Rate)                  |
| +------------+ +------------+ +------------+ +-----------+       |
| | Total Sales| | Orders     | | AOV        | | Conv Rate |       |
| | $45,678    | | 523        | | $87.34     | | 3.2%      |       |
| +------------+ +------------+ +------------+ +-----------+       |
|                                                                   |
| SALES CHART (larger than dashboard version)                      |
| [line/bar chart - daily revenue/orders]                          |
|                                                                   |
| TOP PRODUCTS (10-20 rows)                                         |
| Rank | Product     | Units Sold | Revenue  | % of Total          |
| 1    | Blue Shirt  | 145        | $14,500  | 31.7%               |
| 2    | Red Hat     | 98         | $9,800   | 21.4%               |
| ...                                                               |
|                                                                   |
| TOP REFERRERS                                                     |
| Source          | Sessions | Orders | Conversion Rate             |
| Google          | 1,200    | 45     | 3.75%                      |
| Direct          | 800      | 32     | 4.00%                      |
| Instagram       | 450      | 12     | 2.67%                      |
+==================================================================+
```

Similar to Dashboard but with additional controls and detail.

**Filter bar:**
- Date range dropdown (same as Dashboard: Today, Last 7 days, Last 30 days, Custom)
- Channel filter: Flux select with All, Storefront, API
- Device filter: Flux select with All, Desktop, Mobile, Tablet
- Export button: ghost variant with download icon - calls `exportCsv()`

When `isExporting` is true, show a loading indicator next to the export button. When `exportUrl` is set, show a download link.

**KPI tiles:** Same as Dashboard (Total Sales, Orders, AOV, Conversion Rate) but with the additional filters applied.

**Sales chart:** Larger chart than Dashboard, line or bar chart showing daily revenue/orders.

**Top Products table:** Extended version with 10-20 rows. Columns: Rank, Product, Units Sold, Revenue, Percentage of Total.

**Top Referrers table:** Columns: Traffic source, Sessions, Orders, Conversion Rate.

---

## 18. Search Settings

**Route:** `GET /admin/search/settings`

**Livewire component:** `App\Livewire\Admin\Search\Settings`

**Blade view:** `resources/views/livewire/admin/search/settings.blade.php`

### Component Requirements

| Property | Type | Purpose |
|----------|------|---------|
| `synonymGroups` | `array` | Array of synonym groups, each a comma-separated string |
| `stopWords` | `string` | Comma-separated stop words |
| `lastIndexedAt` | `?string` | Last reindex timestamp |
| `isReindexing` | `bool` | Whether a reindex is in progress |
| `reindexProgress` | `?int` | Progress percentage (0-100) |

| Action | Behavior |
|--------|----------|
| `save()` | Saves synonym and stop word settings |
| `addSynonymGroup()` | Adds a new empty synonym group |
| `removeSynonymGroup(int $index)` | Removes a synonym group |
| `triggerReindex()` | Dispatches a reindex job |
| `pollReindexStatus()` | Checks reindex progress (polled every 2 seconds) |

### Layout Wireframe

```
+==================================================================+
| Search Settings                                                   |
+==================================================================+
|                                                                   |
| SYNONYMS                                                          |
| Define groups of words that should be treated as equivalent.     |
|                                                                   |
| [t-shirt, tee, tshirt              ] [trash]                    |
| [sneakers, trainers, kicks         ] [trash]                    |
| [+ Add synonym group]                                             |
|                                                                   |
| [separator]                                                       |
|                                                                   |
| STOP WORDS                                                        |
| Words that are excluded from search indexing.                    |
| [textarea: the, a, an, is, are...           ]                   |
| (Separate words with commas.)                                     |
|                                                                   |
| [separator]                                                       |
|                                                                   |
| SEARCH INDEX                                                      |
| [Reindex now]  Last indexed: Jan 15, 2024 3:45 PM               |
|                                                                   |
| (when reindexing:)                                                |
| [==============          ] 65% complete                          |
|                                                                   |
| [Save]                                                            |
+==================================================================+
```

**Synonyms section:**
- Heading "Synonyms" (size lg)
- Description text
- For each synonym group: a row with text input (placeholder: "t-shirt, tee, tshirt") and trash button
- "Add synonym group" ghost button with plus icon

**Stop words section:**
- Separator
- Heading "Stop words" (size lg)
- Description text
- Textarea (4 rows, placeholder: "the, a, an, is, are...")
- Flux description: "Separate words with commas."

**Reindex section:**
- Separator
- Heading "Search index" (size lg)
- "Reindex now" button (disabled while reindexing, text changes to "Reindexing...")
- Last indexed timestamp shown beside button (if available)
- When reindexing: a progress bar (polled every 2 seconds) with percentage text

Save button at the bottom.

---

## 19. Common Patterns and Conventions

### 19.1 List Pages

All list pages follow a consistent pattern:
1. Page heading with optional action button (top-right)
2. Search input with live debounce (300ms)
3. Filter controls (dropdowns, tabs, or both)
4. Data table with sortable columns
5. Pagination at the bottom
6. Empty state when no records exist (and no filters are active)
7. Loading states: table body shows reduced opacity during data loading

### 19.2 Form Pages

All form pages follow a consistent pattern:
1. Page heading with breadcrumbs
2. Two-column layout (2/3 + 1/3 on large screens, stacked on mobile)
3. Left column: primary content fields
4. Right column: status, metadata, and secondary fields
5. Sticky save bar at the bottom with "Discard" and "Save" buttons
6. Inline validation errors using Flux field component with error display
7. Loading indicator on save: save button shows loading text and becomes disabled
8. Success toast dispatched after save
9. Delete action behind a confirmation Flux modal

### 19.3 Delete Confirmations

Every destructive action uses a Flux modal for confirmation:
- Heading describing the action
- Body text explaining consequences
- Cancel button (ghost variant) that closes the modal
- Confirm button (danger variant) that executes the destructive action

### 19.4 Toast Notifications

All successful actions dispatch a success toast (e.g., "Changes saved."). Error states dispatch an error toast (e.g., "Something went wrong. Please try again.").

### 19.5 Breadcrumbs

Every page includes Flux breadcrumbs:
- First item: "Home" linking to admin dashboard
- Intermediate items: parent section (e.g., "Products" linking to products index)
- Last item: current page title (no link)

### 19.6 Loading States

All interactive elements show loading states:
- **Buttons:** Disabled attribute during loading, text swaps to loading message (e.g., "Saving...")
- **Tables:** Body rows show reduced opacity during data fetching
- **Charts/tiles:** Spinner overlay targeting the specific loading method

### 19.7 Card Pattern

Reusable card wrapper used throughout the admin:
- White background (dark: zinc-900)
- Rounded corners (lg)
- Border (zinc-200, dark: zinc-700)
- Padding (p-6)

### 19.8 Responsive Design

- All layouts use Tailwind responsive breakpoints: `sm:`, `md:`, `lg:`, `xl:`
- Sidebar collapses on screens below `lg`
- Two-column form layouts stack to single column below `lg`
- Tables become horizontally scrollable on small screens
- Grid columns reduce on smaller screens (e.g., 4 cols on lg, 2 on sm, 1 on mobile)

### 19.9 Accessibility

- All form inputs have associated Flux label elements
- Interactive elements are keyboard-navigable
- Focus states are visible (Tailwind's default focus ring utilities)
- Flux modal handles focus trapping and Escape key dismissal
- Color is never the sole indicator of state (badges include text labels)
- Semantic HTML: headings hierarchy, landmark regions, lists for navigation
- ARIA attributes where needed (e.g., `aria-expanded` on collapsibles, `aria-current="page"` on active nav)

---

## 20. Flash / Toast Messages

All flash messages use the toast notification component. Messages are displayed after the relevant action completes.

| Action | Message | Type |
|--------|---------|------|
| Product created | "Product saved" | success |
| Product updated | "Product saved" | success |
| Product archived | "Product archived" | success |
| Variant added | "Variant saved" | success |
| Collection saved | "Collection saved" | success |
| Discount created | "Discount saved" | success |
| Discount updated | "Discount saved" | success |
| Order fulfilled | "Fulfillment created" | success |
| Refund issued | "Refund issued" | success |
| Customer created | "Customer saved" | success |
| Customer updated | "Customer saved" | success |
| Settings saved | "Settings saved" | success |
| Theme published | "Theme published" | success |
| Page saved | "Page saved" | success |
| Navigation saved | "Navigation saved" | success |
| Login failed | "Invalid credentials" | error |
| Validation error | Dynamic field-specific message | error |

---

## 21. Livewire Component Summary

| Component Class | Blade View | Route(s) |
|----------------|-----------|---------|
| `App\Livewire\Admin\Layout\Sidebar` | `livewire.admin.layout.sidebar` | Layout (all pages) |
| `App\Livewire\Admin\Layout\TopBar` | `livewire.admin.layout.top-bar` | Layout (all pages) |
| `App\Livewire\Admin\Dashboard` | `livewire.admin.dashboard` | `GET /admin` |
| `App\Livewire\Admin\Products\Index` | `livewire.admin.products.index` | `GET /admin/products` |
| `App\Livewire\Admin\Products\Form` | `livewire.admin.products.form` | `GET /admin/products/create`, `GET /admin/products/{product}/edit` |
| `App\Livewire\Admin\Collections\Index` | `livewire.admin.collections.index` | `GET /admin/collections` |
| `App\Livewire\Admin\Collections\Form` | `livewire.admin.collections.form` | `GET /admin/collections/create`, `GET /admin/collections/{collection}/edit` |
| `App\Livewire\Admin\Inventory\Index` | `livewire.admin.inventory.index` | `GET /admin/inventory` |
| `App\Livewire\Admin\Orders\Index` | `livewire.admin.orders.index` | `GET /admin/orders` |
| `App\Livewire\Admin\Orders\Show` | `livewire.admin.orders.show` | `GET /admin/orders/{order}` |
| `App\Livewire\Admin\Customers\Index` | `livewire.admin.customers.index` | `GET /admin/customers` |
| `App\Livewire\Admin\Customers\Show` | `livewire.admin.customers.show` | `GET /admin/customers/{customer}` |
| `App\Livewire\Admin\Discounts\Index` | `livewire.admin.discounts.index` | `GET /admin/discounts` |
| `App\Livewire\Admin\Discounts\Form` | `livewire.admin.discounts.form` | `GET /admin/discounts/create`, `GET /admin/discounts/{discount}/edit` |
| `App\Livewire\Admin\Settings\General` | `livewire.admin.settings.general` | `GET /admin/settings` |
| `App\Livewire\Admin\Settings\Domains` | `livewire.admin.settings.domains` | Tab within `GET /admin/settings` |
| `App\Livewire\Admin\Settings\Shipping` | `livewire.admin.settings.shipping` | `GET /admin/settings/shipping` |
| `App\Livewire\Admin\Settings\Taxes` | `livewire.admin.settings.taxes` | `GET /admin/settings/taxes` |
| `App\Livewire\Admin\Themes\Index` | `livewire.admin.themes.index` | `GET /admin/themes` |
| `App\Livewire\Admin\Themes\Editor` | `livewire.admin.themes.editor` | `GET /admin/themes/{theme}/editor` |
| `App\Livewire\Admin\Pages\Index` | `livewire.admin.pages.index` | `GET /admin/pages` |
| `App\Livewire\Admin\Pages\Form` | `livewire.admin.pages.form` | `GET /admin/pages/create`, `GET /admin/pages/{page}/edit` |
| `App\Livewire\Admin\Navigation\Index` | `livewire.admin.navigation.index` | `GET /admin/navigation` |
| `App\Livewire\Admin\Apps\Index` | `livewire.admin.apps.index` | `GET /admin/apps` |
| `App\Livewire\Admin\Developers\Index` | `livewire.admin.developers.index` | `GET /admin/developers` |
| `App\Livewire\Admin\Analytics\Index` | `livewire.admin.analytics.index` | `GET /admin/analytics` |
| `App\Livewire\Admin\Search\Settings` | `livewire.admin.search.settings` | `GET /admin/search/settings` |
