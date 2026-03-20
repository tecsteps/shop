# Phase 7: Admin Panel - Gherkin Specifications

## Overview

Phase 7 covers the entire admin panel: layout shell (sidebar, topbar, breadcrumbs, toasts), dashboard with KPIs and charts, product management (list, create/edit, variants, media), order management (list, detail, fulfillment, refund, bank transfer confirmation), and additional sections (collections, customers, discounts, settings, pages, navigation, analytics, themes, apps, developers). All admin routes are under `/admin`, protected by authentication middleware and store-scoping. The UI is built with Livewire v4 + Flux UI Free + Tailwind CSS v4.

---

## Feature 1: Admin Layout Shell

Source: Spec 03 S1.1-1.6, Spec 09 Step 7.1

```gherkin
Feature: Admin Layout Shell
  As an admin user
  I want a persistent layout with sidebar, topbar, breadcrumbs, and toast notifications
  So that I can navigate and manage the store efficiently

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And an admin user "admin@acme.test" with role "owner" exists

  Scenario: Sidebar displays all navigation groups and items
    Given I am logged in as admin "admin@acme.test"
    When I visit "/admin"
    Then the sidebar should display the brand logo and "Platform Name"
    And the sidebar should display navigation items:
      | Group      | Item        | Route                    |
      | -          | Dashboard   | admin.dashboard          |
      | PRODUCTS   | Products    | admin.products.index     |
      | PRODUCTS   | Collections | admin.collections.index  |
      | PRODUCTS   | Inventory   | admin.inventory.index    |
      | ORDERS     | Orders      | admin.orders.index       |
      | CUSTOMERS  | Customers   | admin.customers.index    |
      | DISCOUNTS  | Discounts   | admin.discounts.index    |
      | CONTENT    | Pages       | admin.pages.index        |
      | CONTENT    | Navigation  | admin.navigation.index   |
      | CONTENT    | Themes      | admin.themes.index       |
      | -          | Analytics   | admin.analytics.index    |
      | -          | Settings    | admin.settings.index     |
      | -          | Apps        | admin.apps.index         |
      | -          | Developers  | admin.developers.index   |
    And each item should display its corresponding icon

  Scenario: Active sidebar item is highlighted
    Given I am logged in as admin "admin@acme.test"
    When I visit "/admin/products"
    Then the "Products" sidebar item should have a highlighted background and bold text
    And the "Dashboard" sidebar item should not be highlighted

  Scenario: Sidebar navigation with Livewire SPA transitions
    Given I am logged in as admin "admin@acme.test"
    And I am on the dashboard "/admin"
    When I click "Products" in the sidebar
    Then I should be navigated to "/admin/products" without full page reload
    And I should see the heading "Products"

  Scenario: Sidebar responsive behavior on desktop
    Given I am logged in as admin "admin@acme.test"
    And my viewport is "lg" or larger
    When I visit "/admin"
    Then the sidebar should be visible and fixed at 256px width
    And the main content should have a left margin of 256px

  Scenario: Sidebar responsive behavior on mobile
    Given I am logged in as admin "admin@acme.test"
    And my viewport is below "lg"
    When I visit "/admin"
    Then the sidebar should be hidden off-screen
    And a hamburger button should be visible in the topbar
    When I click the hamburger button
    Then the sidebar should slide in as an overlay with a backdrop
    When I click a nav item
    Then the sidebar should close

  Scenario: Topbar displays store selector and user profile
    Given I am logged in as admin "admin@acme.test"
    And the user has access to stores "Acme" and "SecondStore"
    When I visit "/admin"
    Then the topbar should display the current store name "Acme"
    And the topbar should display a user profile dropdown trigger

  Scenario: Switching stores via topbar
    Given I am logged in as admin "admin@acme.test"
    And the user has access to stores "Acme" and "SecondStore"
    When I click the store name dropdown
    And I select "SecondStore"
    Then I should be redirected to "/admin"
    And the active store in session should be "SecondStore"

  Scenario: Logging out from profile dropdown
    Given I am logged in as admin "admin@acme.test"
    When I click the user profile dropdown
    And I click "Log out"
    Then I should be redirected to the admin login page
    And I should no longer be authenticated

  Scenario: Breadcrumbs display on every admin page
    Given I am logged in as admin "admin@acme.test"
    When I visit "/admin/products"
    Then the breadcrumbs should show "Home > Products"
    When I visit a product edit page for "Blue T-Shirt"
    Then the breadcrumbs should show "Home > Products > Blue T-Shirt"

  Scenario: Toast notification on successful action
    Given I am logged in as admin "admin@acme.test"
    When I perform a save action that succeeds
    Then a toast notification should appear in the top-right corner
    And the toast should display type "success" with a green left border
    And the toast should auto-dismiss after 5 seconds

  Scenario: Toast notification on error
    Given I am logged in as admin "admin@acme.test"
    When I perform an action that fails
    Then a toast notification should appear with type "error" and a red left border

  Scenario: Multiple toast notifications stack
    Given I am logged in as admin "admin@acme.test"
    When multiple actions dispatch toast events in quick succession
    Then multiple toasts should stack vertically in the top-right corner

  Scenario: Dark mode follows system preference
    Given I am logged in as admin "admin@acme.test"
    And my system preference is "dark"
    When I visit "/admin"
    Then the admin interface should render in dark mode using "dark:" Tailwind variants
```

---

## Feature 2: Admin Dashboard

Source: Spec 03 S2, Spec 09 Step 7.2, Pest DashboardTest (4 tests)

```gherkin
Feature: Admin Dashboard
  As an admin user
  I want a dashboard with KPIs, charts, and recent data
  So that I can monitor store performance at a glance

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And an admin user "admin@acme.test" with role "owner" exists

  Scenario: Dashboard renders with heading and date range filter
    Given I am logged in as admin "admin@acme.test"
    When I visit "/admin"
    Then I should see the heading "Dashboard"
    And I should see a date range filter dropdown with options:
      | Today           |
      | Last 7 days     |
      | Last 30 days    |
      | Custom range    |

  Scenario: KPI tiles display correct data
    Given I am logged in as admin "admin@acme.test"
    And the store has 5 orders totaling 25000 cents in the selected period
    When I visit "/admin"
    Then I should see 4 KPI tiles in a responsive grid
    And the "Total Sales" tile should display the formatted revenue
    And the "Orders" tile should display "5"
    And the "Avg Order" tile should display the formatted average
    And the "Visitors" tile should display the visitor count
    And each tile should show a percentage change badge (green for positive, red for negative)

  Scenario: Orders chart renders
    Given I am logged in as admin "admin@acme.test"
    And the store has orders spanning the last 30 days
    When I visit "/admin"
    Then I should see a card with heading "Orders over time"
    And the card should contain a Chart.js canvas with daily order counts

  Scenario: Top products table displays
    Given I am logged in as admin "admin@acme.test"
    And the store has sales data
    When I visit "/admin"
    Then I should see a card with heading "Top products"
    And the table should show columns "Product Title", "Units Sold", "Revenue"
    And the table should display a maximum of 5 rows

  Scenario: Top products table empty state
    Given I am logged in as admin "admin@acme.test"
    And the store has no sales data in the selected period
    When I visit "/admin"
    Then the top products section should display "No sales data for this period."

  Scenario: Conversion funnel displays
    Given I am logged in as admin "admin@acme.test"
    When I visit "/admin"
    Then I should see a card with heading "Conversion funnel"
    And the funnel should show steps: "Visits", "Add to Cart", "Checkout Started", "Checkout Completed"
    And each step should have a proportional-width bar

  Scenario: Date range filtering updates KPIs
    Given I am logged in as admin "admin@acme.test"
    And orders exist across multiple date ranges
    When I select "Last 7 days" from the date range filter
    Then the KPI tiles should update to reflect only the last 7 days of data
    And the chart should update accordingly

  Scenario: Custom date range shows date inputs
    Given I am logged in as admin "admin@acme.test"
    When I select "Custom range" from the date range filter
    Then two date input fields should appear for start and end dates
    When I enter a custom start and end date
    Then the KPIs should reload for that custom range

  Scenario: Dashboard loading state
    Given I am logged in as admin "admin@acme.test"
    When the dashboard data is loading
    Then KPI tiles should show reduced opacity
    And the chart area should show a spinner overlay

  Scenario: Dashboard restricted to authenticated admins
    Given I am not logged in
    When I visit "/admin"
    Then I should be redirected to "/admin/login"
```

---

## Feature 3: Product List

Source: Spec 03 S3, Spec 09 Step 7.3, Pest ProductManagementTest (8 tests), E2E Suite 3

```gherkin
Feature: Product List
  As an admin user
  I want to view, search, filter, and bulk-manage products
  So that I can efficiently manage the product catalog

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Product list displays with seeded products
    Given products "Classic Cotton T-Shirt" and "Premium Slim Fit Jeans" exist for the store
    When I visit "/admin/products"
    Then I should see the heading "Products"
    And I should see an "Add product" button
    And I should see "Classic Cotton T-Shirt" in the product list
    And I should see "Premium Slim Fit Jeans" in the product list

  Scenario: Product list shows correct columns
    Given products exist for the store
    When I visit "/admin/products"
    Then the product table should have columns:
      | Select | Image | Title | Status | Inventory | Type | Vendor | Updated |

  Scenario: Product list with pagination
    Given 25 products exist for the store
    When I visit "/admin/products"
    Then I should see the first page of products (15 per page)
    And pagination controls should be visible

  Scenario: Search products by title
    Given products "Classic Cotton T-Shirt" and "Premium Slim Fit Jeans" exist
    When I enter "Cotton" in the search field
    Then I should see "Classic Cotton T-Shirt" in the results
    And "Premium Slim Fit Jeans" should not be visible
    And pagination should reset to page 1

  Scenario: Filter products by status
    Given products exist with statuses "active", "draft", and "archived"
    When I select "Draft" from the status filter
    Then only draft products should be visible
    When I select "Active" from the status filter
    Then only active products should be visible
    When I select "All" from the status filter
    Then all products should be visible

  Scenario: Filter products by type
    Given products exist with types "Tops" and "Shoes"
    When I select "Tops" from the type filter
    Then only products of type "Tops" should be visible

  Scenario: Sort products by column
    Given multiple products exist
    When I click the "Title" column header
    Then products should be sorted by title ascending
    When I click the "Title" column header again
    Then products should be sorted by title descending

  Scenario: Default sort by updated_at
    When I visit "/admin/products"
    Then products should be sorted by "updated_at" descending by default

  Scenario: Bulk select products
    Given 5 products exist
    When I check the "select all" checkbox
    Then all 5 visible product checkboxes should be checked
    And I should see "5 products selected"

  Scenario: Bulk archive selected products
    Given 3 products are selected
    When I click the "Archive" bulk action button
    Then all 3 selected products should have status "archived"
    And a success toast should appear

  Scenario: Bulk set active for selected products
    Given 3 archived products are selected
    When I click the "Set Active" bulk action button
    Then all 3 selected products should have status "active"

  Scenario: Bulk delete with confirmation modal
    Given 2 products are selected
    When I click the "Delete" bulk action button
    Then a confirmation modal should appear with heading "Delete products?"
    And the body should say "This will archive 2 product(s)."
    When I click "Confirm" in the modal
    Then the selected products should be soft-deleted
    And a success toast should appear

  Scenario: Cancel bulk delete
    Given 2 products are selected
    When I click the "Delete" bulk action button
    And I click "Cancel" in the confirmation modal
    Then the products should not be deleted
    And the modal should close

  Scenario: Status badges display correct colors
    Given a product with status "active" exists
    And a product with status "draft" exists
    And a product with status "archived" exists
    When I visit "/admin/products"
    Then the "active" product should have a green badge
    And the "draft" product should have a zinc/gray badge
    And the "archived" product should have a red badge

  Scenario: Product title links to edit page
    Given a product "Blue Shirt" exists
    When I click "Blue Shirt" in the product list
    Then I should be navigated to the product edit page for "Blue Shirt"

  Scenario: Empty state when no products exist
    Given no products exist for the store
    When I visit "/admin/products"
    Then I should see the empty state with heading "Add your first product"
    And I should see the text "Start building your catalog by adding products."
    And I should see an "Add product" button

  Scenario: Empty state when filters match nothing
    Given products exist but none match the current filters
    When I apply a filter that returns no results
    Then I should see "No products match your filters"

  Scenario: Loading state shows reduced opacity
    When the product list is loading
    Then the table body should show reduced opacity

  Scenario: Product list restricted to authorized roles
    Given a support user "support@acme.test" exists
    And I am logged in as "support@acme.test"
    When I visit "/admin/products"
    Then the support user should see the product list (read-only)

  Scenario: Staff cannot delete products
    Given a staff user "staff@acme.test" exists
    And I am logged in as "staff@acme.test"
    When the staff user attempts to delete a product
    Then they should receive a 403 response
```

---

## Feature 4: Product Create and Edit

Source: Spec 03 S4, Spec 09 Step 7.3, Pest ProductManagementTest (8 tests), E2E Suite 3

```gherkin
Feature: Product Create and Edit
  As an admin user
  I want to create and edit products with variants and media
  So that I can build and maintain the product catalog

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Create product page renders in create mode
    When I visit "/admin/products/create"
    Then I should see the heading "Add product"
    And the form should be empty with default values
    And no delete button should be visible
    And a sticky save bar should be visible at the bottom

  Scenario: Successfully create a product
    When I visit "/admin/products/create"
    And I fill in "Title" with "Test Product"
    And I fill in "Description" with "A test product description"
    And I select "Draft" for "Status"
    And I fill in "Vendor" with "Test Vendor"
    And I fill in "Product type" with "T-Shirts"
    And I fill in "Tags" with "summer, cotton"
    And I fill in variant 0 price with "2999"
    And I fill in variant 0 SKU with "TP-001"
    And I fill in variant 0 quantity with "50"
    And I click "Save"
    Then a success toast should say "Product saved"
    And a product "Test Product" should exist in the database with status "draft"

  Scenario: Edit product page renders in edit mode
    Given a product "Blue Shirt" exists
    When I visit the edit page for "Blue Shirt"
    Then I should see the heading "Blue Shirt"
    And all fields should be pre-filled with the product data
    And a delete button should be visible (for authorized users)

  Scenario: Successfully edit a product title
    Given a product "Classic Cotton T-Shirt" exists
    When I visit the edit page for "Classic Cotton T-Shirt"
    And I change the title to "Classic Cotton T-Shirt Updated"
    And I click "Save"
    Then a success toast should say "Product saved"
    And the product title should be "Classic Cotton T-Shirt Updated" in the database

  Scenario: Two-column layout renders correctly
    When I visit "/admin/products/create"
    Then the left column (2/3 width) should contain: Title, Description, Media, Variants, SEO
    And the right column (1/3 width) should contain: Status, Publishing, Organization, Collections

  Scenario: Validation fails for missing required fields
    When I visit "/admin/products/create"
    And I click "Save" without filling required fields
    Then I should see a validation error for "title"
    And I should see a validation error for "variants.0.price"
    And I should see a validation error for "handle"

  Scenario: Validation for unique handle
    Given a product with handle "blue-shirt" exists
    When I create a product with handle "blue-shirt"
    And I click "Save"
    Then I should see a validation error indicating the handle is already taken

  Scenario: Status select with draft, active, archived options
    When I visit "/admin/products/create"
    Then the Status select should have options: "Draft", "Active", "Archived"

  Scenario: Publishing card with datetime input
    When I visit "/admin/products/create"
    Then the publishing card should have a "Published at" datetime-local input

  Scenario: Organization fields render
    When I visit "/admin/products/create"
    Then I should see fields for "Vendor", "Product type", and "Tags"
    And the Tags field should have the description "Separate tags with commas"

  Scenario: Collections checkboxes
    Given collections "Summer Sale", "New Arrivals" exist for the store
    When I visit "/admin/products/create"
    Then I should see checkboxes for "Summer Sale" and "New Arrivals" in the Collections card

  Scenario: SEO section is collapsible
    When I visit "/admin/products/create"
    Then the SEO section should be collapsed by default
    When I click "Search engine listing"
    Then the SEO section should expand to show the URL handle input

  Scenario: Discard button navigates back
    When I visit "/admin/products/create"
    And I click "Discard"
    Then I should be navigated back to "/admin/products"

  Scenario: Save button shows loading state
    When I click "Save" on the product form
    Then the save button should be disabled
    And the button text should change to "Saving..."

  Scenario: Delete product with confirmation modal (edit only)
    Given a product "Old Product" exists
    When I visit the edit page for "Old Product"
    And I click the delete button
    Then a confirmation modal should appear with heading "Delete this product?"
    When I click "Confirm" in the modal
    Then the product should be archived
    And I should be redirected to "/admin/products"

  Scenario: Cancel product deletion
    Given a product "Old Product" exists
    When I visit the edit page for "Old Product"
    And I click the delete button
    And I click "Cancel" in the modal
    Then the product should not be deleted
    And the modal should close
```

---

## Feature 5: Variants Builder

Source: Spec 03 S4 (Variants section), Spec 09 Step 7.3, Pest ProductManagementTest

```gherkin
Feature: Variants Builder
  As an admin user
  I want to manage product options and auto-generated variants
  So that I can offer size, color, and other variations of a product

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Add a product option
    When I visit "/admin/products/create"
    And I click "Add another option"
    Then a new option row should appear with "Option name" and "Values" fields

  Scenario: Define option values
    When I add an option with name "Size" and values "S, M, L, XL"
    Then the option should show name "Size" and 4 values

  Scenario: Auto-generate variants from options
    When I add option "Size" with values "S, M, L"
    And I add option "Color" with values "Red, Blue"
    Then 6 variants should be auto-generated:
      | S / Red  |
      | S / Blue |
      | M / Red  |
      | M / Blue |
      | L / Red  |
      | L / Blue |

  Scenario: Variant table displays inline-editable fields
    Given variants have been generated
    Then each variant row should show:
      | Column            | Input Type    |
      | Variant           | Read-only     |
      | SKU               | Text input    |
      | Price             | Number input  |
      | Compare at price  | Number input  |
      | Quantity          | Number input  |
      | Requires shipping | Checkbox      |

  Scenario: Remove an option regenerates variants
    Given options "Size" (S, M) and "Color" (Red, Blue) exist with 4 variants
    When I click the trash button on the "Color" option
    Then only 2 variants should remain: "S" and "M"

  Scenario: Add an option value regenerates variants
    Given option "Size" exists with values "S, M"
    When I add value "L" to the "Size" option
    Then 3 variants should exist: "S", "M", "L"

  Scenario: Manages variants via product form (Pest test mapping)
    When I add options and values via the Livewire product form
    Then the variants should be generated correctly in the component state
```

---

## Feature 6: Media Upload

Source: Spec 03 S4 (Media section), Spec 09 Step 7.3, Pest ProductManagementTest

```gherkin
Feature: Media Upload
  As an admin user
  I want to upload, reorder, and manage product images
  So that products have rich visual content

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Media section renders upload zone
    When I visit "/admin/products/create"
    Then I should see a media section with heading "Media"
    And a dashed-border drop zone should be visible with text "Drag and drop images or click to upload"

  Scenario: Upload a media file
    When I select an image file to upload
    Then a progress bar should be shown during upload
    And after upload completes, the image should appear as a thumbnail in the media grid

  Scenario: Media grid layout
    Given 4 images are uploaded for a product
    Then the media grid should display in 4 columns on lg, 3 on sm, 2 on smaller viewports
    And each thumbnail should be square with object-cover and rounded

  Scenario: Delete a media item
    Given an image "hero.jpg" is attached to a product
    When I hover over the "hero.jpg" thumbnail
    And I click the delete button (X icon)
    Then the image should be removed from the media list

  Scenario: Edit alt text for media
    Given an image is attached to a product
    When I hover over the thumbnail
    And I click the alt text edit button (pencil icon)
    And I enter alt text "Blue shirt front view"
    Then the alt text should be saved for that media item

  Scenario: Reorder media via drag and drop
    Given 3 images are attached to a product
    When I drag the third image to the first position
    Then the media positions should update accordingly

  Scenario: Uploads media from product form (Pest test mapping)
    When I use Livewire file upload on the product edit form
    Then the media should be attached to the product in the database
```

---

## Feature 7: Order List

Source: Spec 03 S7, Spec 09 Step 7.4, Pest OrderManagementTest (5 tests), E2E Suite 4

```gherkin
Feature: Order List
  As an admin user
  I want to view, search, and filter orders
  So that I can manage the store's order pipeline

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Order list displays with seeded orders
    Given orders "#1001" and "#1000" exist for the store
    When I visit "/admin/orders"
    Then I should see the heading "Orders"
    And I should see "#1001" in the order list
    And I should see "#1000" in the order list

  Scenario: Order list shows correct columns
    When I visit "/admin/orders"
    Then the order table should have columns:
      | Order # | Date | Customer | Payment | Fulfill | Total |

  Scenario: Search orders by order number
    Given orders "#1001" and "#1000" exist
    When I enter "1001" in the search field
    Then I should see "#1001" in the results
    And "#1000" should not be visible

  Scenario: Search orders by customer email
    Given an order by "john@example.com" exists
    When I enter "john@example" in the search field
    Then the matching order should be visible

  Scenario: Filter orders by status tabs
    Given 3 pending orders and 2 paid orders exist
    When I click the "Paid" filter tab
    Then only 2 paid orders should be visible
    When I click the "All" tab
    Then all orders should be visible

  Scenario: Status filter tabs display correctly
    When I visit "/admin/orders"
    Then I should see filter tabs: "All", "Pending", "Paid", "Fulfilled", "Cancelled", "Refunded"
    And the active tab should have a bottom border and bold text

  Scenario: Financial status badges display correct colors
    Given orders with various financial statuses exist
    Then "pending" orders should have a zinc badge
    And "paid" orders should have a green badge
    And "refunded" orders should have a yellow badge
    And "cancelled" orders should have a red badge

  Scenario: Fulfillment status badges display correct colors
    Given orders with various fulfillment statuses exist
    Then "unfulfilled" orders should have a zinc badge
    And "partial" orders should have a yellow badge
    And "fulfilled" orders should have a green badge

  Scenario: Sort orders by column
    When I click the "Date" column header
    Then orders should toggle sort direction

  Scenario: Default sort by placed_at descending
    When I visit "/admin/orders"
    Then orders should be sorted by "placed_at" descending

  Scenario: Order number links to detail page
    Given order "#1001" exists
    When I click "#1001" in the order list
    Then I should be navigated to the order detail page for "#1001"

  Scenario: Pagination displays below order list
    Given more than 15 orders exist
    When I visit "/admin/orders"
    Then pagination controls should be visible below the table

  Scenario: Order list restricted by role
    Given a support user "support@acme.test" exists
    And I am logged in as "support@acme.test"
    When I visit "/admin/orders"
    Then the support user should see the order list (read-only)
    And the support user should not be able to update or fulfill orders
```

---

## Feature 8: Order Detail

Source: Spec 03 S8, Spec 09 Step 7.4, Pest OrderManagementTest (5 tests), E2E Suite 4

```gherkin
Feature: Order Detail
  As an admin user
  I want to view full order details, create fulfillments, and process refunds
  So that I can manage individual orders end-to-end

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Order detail displays heading with statuses
    Given order "#1001" exists with financial_status "paid" and fulfillment_status "unfulfilled"
    When I visit the order detail page for "#1001"
    Then I should see heading "#1001"
    And I should see a "Paid" financial status badge (green)
    And I should see an "Unfulfilled" fulfillment status badge (zinc)
    And I should see the placed_at date

  Scenario: Order detail shows line items table
    Given order "#1001" has line items "Blue Shirt" (qty 2, $60.00) and "Red Hat" (qty 1, $20.00)
    When I visit the order detail page for "#1001"
    Then I should see a line items table with columns: Image, Product, Quantity, Unit Price, Total
    And I should see "Blue Shirt" with quantity "2" and total "$120.00"
    And I should see "Red Hat" with quantity "1" and total "$20.00"

  Scenario: Order summary block displays
    Given order "#1001" has subtotal, discount, shipping, tax, and total
    When I visit the order detail page for "#1001"
    Then I should see a summary block with:
      | Subtotal  |
      | Discount  |
      | Shipping  |
      | Tax       |
      | Total     |

  Scenario: Order timeline displays events
    Given order "#1001" has events: "Order placed" and "Payment received"
    When I visit the order detail page for "#1001"
    Then I should see a vertical timeline
    And the timeline should show "Order placed" with a timestamp
    And the timeline should show "Payment received" with a timestamp

  Scenario: Customer card in right column
    Given order "#1001" belongs to customer "John Doe" with email "john@example.com"
    When I visit the order detail page for "#1001"
    Then the right column should show a "Customer" card
    And the card should display "John Doe" and "john@example.com"
    And a "View customer" link should be visible

  Scenario: Guest order shows "Guest" in customer card
    Given order "#1002" has no associated customer
    When I visit the order detail page for "#1002"
    Then the customer card should display "Guest"
    And no "View customer" link should be visible

  Scenario: Shipping and billing address cards
    Given order "#1001" has shipping and billing addresses
    When I visit the order detail page for "#1001"
    Then I should see a "Shipping address" card with the full address
    And I should see a "Billing address" card with the full address

  Scenario: Payment details card
    Given order "#1001" was paid by credit card with reference "mock_xxx"
    When I visit the order detail page for "#1001"
    Then I should see a payment details card showing:
      | Method    | Credit Card |
      | Status    | Captured    |
      | Amount    | formatted   |
      | Reference | mock_xxx    |

  Scenario: Action buttons display based on order state
    Given order "#1001" is paid and unfulfilled
    When I visit the order detail page for "#1001"
    Then "Create fulfillment" button should be visible
    And "Refund" button should be visible

  Scenario: Confirm payment button for bank transfer
    Given order "#1005" has payment_method "bank_transfer" and financial_status "pending"
    When I visit the order detail page for "#1005"
    Then I should see a "Confirm payment" button
    When I click "Confirm payment"
    Then the financial status should change to "Paid"
    And a success toast should say "Payment confirmed"
    And the "Confirm payment" button should no longer be visible

  Scenario: Fulfillment guard for unpaid order
    Given order "#1005" has financial_status "pending"
    When I visit the order detail page for "#1005"
    Then I should see a warning callout: "Cannot create fulfillment. Payment must be confirmed before items can be fulfilled."
    And the "Create fulfillment" button should be disabled or hidden

  Scenario: Create fulfillment via modal
    Given order "#1001" is paid and unfulfilled
    When I click "Create fulfillment" on the order detail
    Then a modal should open with heading "Create fulfillment"
    And I should see unfulfilled lines with checkboxes and quantity inputs
    When I fill in tracking company with "DHL"
    And I fill in tracking number with "DHL123456789"
    And I click "Create fulfillment" in the modal
    Then a fulfillment should be created for the selected lines
    And the page should show "DHL" and "DHL123456789" in a fulfillment card
    And a success toast should appear

  Scenario: Mark fulfillment as shipped
    Given order "#1001" has a fulfillment with status "pending"
    When I click "Mark as shipped" on the fulfillment card
    Then the fulfillment status badge should change to "Shipped"
    And the shipped_at timestamp should be set

  Scenario: Mark fulfillment as delivered
    Given order "#1001" has a fulfillment with status "shipped"
    When I click "Mark as delivered" on the fulfillment card
    Then the fulfillment status badge should change to "Delivered"
    And the delivered_at timestamp should be set
    And if all fulfillments are delivered, the order fulfillment_status should be "fulfilled"

  Scenario: Create refund via modal
    Given order "#1001" is paid
    When I click "Refund" on the order detail
    Then a modal should open with heading "Refund order"
    And I should see order lines with checkboxes and quantity inputs
    And I should see a "custom amount" field and a "Reason" textarea
    When I enter amount "10.00"
    And I enter reason "Customer requested partial refund"
    And I click "Create refund"
    Then a refund should be created
    And the financial status should update to "Partially refunded"
    And a success toast should say "Refund processed"

  Scenario: Fulfillment card shows tracking details
    Given order "#1001" has a fulfillment with tracking company "DHL", number "DHL123", URL "https://dhl.com/track/DHL123"
    When I visit the order detail page for "#1001"
    Then the fulfillment card should display:
      | Company | DHL                              |
      | Number  | DHL123                           |
      | URL     | https://dhl.com/track/DHL123     |
```

---

## Feature 9: Collections Management

Source: Spec 03 S5, Spec 09 Step 7.5, E2E Suite 15

```gherkin
Feature: Collections Management
  As an admin user
  I want to create, edit, and manage product collections
  So that I can organize products into curated groups

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Collection list displays seeded collections
    Given collections "T-Shirts" and "New Arrivals" exist
    When I visit "/admin/collections"
    Then I should see the heading "Collections"
    And I should see "T-Shirts" in the list
    And I should see "New Arrivals" in the list
    And I should see an "Add collection" button

  Scenario: Collection list shows correct columns
    When I visit "/admin/collections"
    Then the table should have columns: Title, Products count, Status, Updated date

  Scenario: Search collections
    Given collections "T-Shirts" and "New Arrivals" exist
    When I enter "Shirts" in the search field
    Then "T-Shirts" should be visible
    And "New Arrivals" should not be visible

  Scenario: Filter collections by status
    Given active and archived collections exist
    When I select "Archived" from the status filter
    Then only archived collections should be visible

  Scenario: Empty state for no collections
    Given no collections exist for the store
    When I visit "/admin/collections"
    Then I should see "Create your first collection" with a CTA button

  Scenario: Create a new collection
    When I visit "/admin/collections/create"
    And I fill in "Title" with "E2E Test Collection"
    And I fill in "Handle" with "e2e-test-collection"
    And I fill in "Description" with "A test collection"
    And I select "Active" for status
    And I click "Save"
    Then a success toast should say "Collection saved"
    And the collection "E2E Test Collection" should exist in the database

  Scenario: Edit an existing collection
    Given collection "T-Shirts" exists
    When I visit the edit page for "T-Shirts"
    And I change the description to "Updated description for T-Shirts collection."
    And I click "Save"
    Then a success toast should say "Collection saved"

  Scenario: Collection form two-column layout
    When I visit "/admin/collections/create"
    Then the left column should contain: Title, Handle, Description, Products section
    And the right column should contain: Status select

  Scenario: Add products to collection via search
    Given products "Blue Shirt" and "Red Hat" exist
    When I visit "/admin/collections/create"
    And I enter "Blue" in the product search field
    Then "Blue Shirt" should appear in the search results with an "Add" button
    When I click "Add" next to "Blue Shirt"
    Then "Blue Shirt" should appear in the assigned products list

  Scenario: Remove products from collection
    Given "Blue Shirt" is assigned to the collection
    When I click the remove button next to "Blue Shirt" in the assigned list
    Then "Blue Shirt" should no longer be in the assigned products list

  Scenario: Reorder assigned products via drag
    Given products "Blue Shirt" and "Red Hat" are assigned to the collection
    When I drag "Red Hat" above "Blue Shirt"
    Then the product order should update

  Scenario: Delete a collection
    Given collection "Old Collection" exists
    When I delete the collection
    Then the collection should be removed from the database
```

---

## Feature 10: Customer Management

Source: Spec 03 S9, Spec 09 Step 7.5, E2E Suite 16

```gherkin
Feature: Customer Management
  As an admin user
  I want to view customer lists and details with order history and addresses
  So that I can manage customer relationships

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Customer list displays customers
    Given customer "John Doe" with email "customer@acme.test" exists
    When I visit "/admin/customers"
    Then I should see the heading "Customers"
    And I should see "John Doe" in the list
    And I should see "customer@acme.test" in the list

  Scenario: Customer list shows correct columns
    When I visit "/admin/customers"
    Then the table should have columns: Name, Email, Orders count, Total spent, Created date

  Scenario: Search customers by name or email
    Given customers "John Doe" and "Jane Smith" exist
    When I enter "Jane" in the search field
    Then "Jane Smith" should be visible
    And "John Doe" should not be visible

  Scenario: Customer row links to detail page
    When I click "John Doe" in the customer list
    Then I should be navigated to the customer detail page

  Scenario: Customer detail shows info card
    Given customer "John Doe" with email "customer@acme.test" exists
    When I visit the detail page for "John Doe"
    Then I should see "John Doe" and "customer@acme.test"
    And I should see the created date
    And I should see the marketing opt-in status as a badge

  Scenario: Customer detail shows order history
    Given customer "John Doe" has orders "#1001" and "#1005"
    When I visit the detail page for "John Doe"
    Then I should see an order history table with columns: Order #, Date, Status, Total
    And I should see "#1001" in the order history
    And each order should link to the order detail page

  Scenario: Customer detail shows addresses
    Given customer "John Doe" has addresses "Home" and "Office"
    When I visit the detail page for "John Doe"
    Then I should see addresses in the right column
    And each address should display label, full address text, and Edit/Delete buttons
    And the default address should show a "Default" badge

  Scenario: Add address via modal
    When I click "Add address" on the customer detail page
    Then a modal should open with heading "Add address"
    When I fill in the address form fields
    And I click "Save"
    Then the address should be saved for the customer

  Scenario: Edit address via modal
    Given customer "John Doe" has an address "Home"
    When I click "Edit" on the "Home" address
    Then a modal should open with heading "Edit address" pre-filled with the address data
    When I update the city and click "Save"
    Then the address should be updated

  Scenario: Delete customer address
    Given customer "John Doe" has an address "Office"
    When I click "Delete" on the "Office" address
    Then the address should be removed

  Scenario: Set default address
    Given customer "John Doe" has addresses "Home" (default) and "Office"
    When I click "Set as default" on the "Office" address
    Then "Office" should become the default address
    And "Home" should no longer be the default
```

---

## Feature 11: Discount Management

Source: Spec 03 S10, Spec 09 Step 7.5, Pest DiscountManagementTest (6 tests), E2E Suite 5

```gherkin
Feature: Discount Management
  As an admin user
  I want to create, edit, and manage discounts
  So that I can offer promotions to customers

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Discount list displays seeded discounts
    Given discounts "WELCOME10", "FLAT5", and "FREESHIP" exist
    When I visit "/admin/discounts"
    Then I should see the heading "Discounts"
    And I should see "WELCOME10", "FLAT5", and "FREESHIP" in the list
    And I should see a "Create discount" button

  Scenario: Discount list shows correct columns
    When I visit "/admin/discounts"
    Then the table should have columns: Code, Type, Value, Usage, Status, Dates

  Scenario: Search discounts by code
    When I enter "WELCOME" in the search field
    Then "WELCOME10" should be visible
    And "FLAT5" should not be visible

  Scenario: Filter discounts by status
    Given active and expired discounts exist
    When I select "Expired" from the status filter
    Then only expired discounts should be visible

  Scenario: Status badges display correct colors
    Given active and expired discounts exist
    Then "active" discounts should have a green badge
    And "expired" discounts should have a red badge
    And "scheduled" discounts should have a yellow badge

  Scenario: Create a percentage discount
    When I visit "/admin/discounts/create"
    And I select "Discount code" as the type
    And I fill in code with "SAVE10"
    And I select "Percentage" for value type
    And I fill in the value with "10"
    And I set starts_at to "2026-01-01"
    And I click "Save"
    Then a success toast should say "Discount saved"
    And a discount "SAVE10" should exist with type "percent" and value "10"

  Scenario: Create a fixed amount discount
    When I visit "/admin/discounts/create"
    And I select "Discount code" as the type
    And I fill in code with "5OFF"
    And I select "Fixed amount" for value type
    And I fill in the value with "500"
    And I click "Save"
    Then a discount "5OFF" should exist with type "fixed" and value "500"

  Scenario: Create a free shipping discount
    When I visit "/admin/discounts/create"
    And I select "Discount code" as the type
    And I fill in code with "FREESHIP2"
    And I select "Free shipping" for value type
    And I click "Save"
    Then a discount "FREESHIP2" should exist with type "free_shipping"

  Scenario: Auto-generate discount code
    When I visit "/admin/discounts/create"
    And I select "Discount code" as the type
    And I click "Generate"
    Then the code field should be populated with a random code

  Scenario: Create an automatic discount
    When I visit "/admin/discounts/create"
    And I select "Automatic discount" as the type
    Then the code input should not be visible
    When I select "Percentage" and enter value "15"
    And I click "Save"
    Then an automatic discount should exist in the database

  Scenario: Edit an existing discount
    Given discount "WELCOME10" exists with value "10"
    When I visit the edit page for "WELCOME10"
    And I change the value to "15"
    And I click "Save"
    Then a success toast should say "Discount saved"
    And the discount value should be "15" in the database

  Scenario: Validates discount code uniqueness within store
    Given discount "SAVE10" exists in the store
    When I create another discount with code "SAVE10"
    And I click "Save"
    Then I should see a validation error about duplicate code

  Scenario: Disable a discount
    Given discount "WELCOME10" is active
    When I toggle the active switch to off
    And I click "Save"
    Then the discount status should be disabled

  Scenario: Conditions section with minimum purchase
    When I visit "/admin/discounts/create"
    Then I should see a "Minimum purchase amount" field
    And the description should say "Leave empty for no minimum"

  Scenario: Add specific products to discount
    Given product "Blue Shirt" exists
    When I visit "/admin/discounts/create"
    And I search for "Blue" in the specific products field
    Then "Blue Shirt" should appear in results
    When I click to add "Blue Shirt"
    Then "Blue Shirt" should appear in the selected products list

  Scenario: Usage limits configuration
    When I visit "/admin/discounts/create"
    Then I should see a "Total usage limit" field
    And I should see a "Limit to one use per customer" checkbox

  Scenario: Active dates configuration
    When I visit "/admin/discounts/create"
    Then I should see "Start date" and "End date" datetime inputs
    And the end date should have description "Leave empty for no end date"
```

---

## Feature 12: Settings - General

Source: Spec 03 S11.1, Spec 09 Step 7.5, Pest SettingsTest (6 tests), E2E Suite 6

```gherkin
Feature: Settings - General
  As an admin user
  I want to configure store settings
  So that my store operates with the correct defaults

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Settings page renders with tabs
    When I visit "/admin/settings"
    Then I should see "Store Settings" or "Settings" heading
    And I should see tabs: General, Domains, Shipping, Taxes

  Scenario: General settings display store details
    When I visit "/admin/settings"
    Then I should see "Store details" section with store name "Acme Fashion"
    And I should see the store handle (disabled)

  Scenario: Store handle is read-only
    When I visit "/admin/settings"
    Then the store handle input should be disabled
    And the description should say "The store handle cannot be changed after creation."

  Scenario: Update store name
    When I change the store name to "Acme Fashion Updated"
    And I click "Save"
    Then a success toast should say "Settings saved"
    And the store name should be "Acme Fashion Updated" in the database
    When I reload the settings page
    Then I should see "Acme Fashion Updated"

  Scenario: Defaults section displays currency, locale, timezone
    When I visit "/admin/settings"
    Then I should see "Defaults" section
    And I should see select fields for:
      | Default currency | EUR, USD, GBP, etc.      |
      | Default locale   | English, German, French   |
      | Timezone         | PHP timezone list         |

  Scenario: Settings restricted to owner and admin roles
    Given a staff user "staff@acme.test" exists
    And I am logged in as "staff@acme.test"
    When I visit "/admin/settings"
    Then I should receive a 403 response
```

---

## Feature 13: Settings - Domains

Source: Spec 03 S11.2, Pest SettingsTest (6 tests), E2E Suite 6

```gherkin
Feature: Settings - Domains
  As an admin user
  I want to manage store domains
  So that my store is accessible via the correct hostnames

  Background:
    Given a store "Acme" exists with domain "acme-fashion.test"
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Domains tab shows existing domains
    When I click the "Domains" tab on the settings page
    Then I should see "acme-fashion.test" in the domains table
    And the table should show columns: Hostname, Type, Primary, TLS, Actions

  Scenario: Add a new domain via modal
    When I click "Add domain"
    Then a modal should open with heading "Add domain"
    When I fill in hostname with "shop.example.com"
    And I select type "Storefront"
    And I click "Add domain" in the modal
    Then the domain "shop.example.com" should appear in the list

  Scenario: Set a domain as primary
    Given domains "acme-fashion.test" (primary) and "shop.example.com" exist
    When I click "Set Primary" on "shop.example.com"
    Then "shop.example.com" should become the primary domain
    And "acme-fashion.test" should no longer be primary

  Scenario: Delete a domain
    Given domain "shop.example.com" exists (non-primary)
    When I click "Delete" on "shop.example.com"
    Then the domain should be removed from the list
```

---

## Feature 14: Settings - Shipping

Source: Spec 03 S11.3, Pest SettingsTest (6 tests), E2E Suite 6

```gherkin
Feature: Settings - Shipping
  As an admin user
  I want to configure shipping zones and rates
  So that customers see correct shipping options at checkout

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Shipping settings display existing zones
    Given shipping zone "Domestic" with countries "US, CA" and rate "Standard Shipping" ($4.99) exists
    When I navigate to the Shipping settings tab
    Then I should see zone card "Domestic"
    And the zone should list countries "US, CA"
    And the rate "Standard Shipping" should show "$4.99"

  Scenario: Add a new shipping zone via modal
    When I click "Add zone"
    Then a modal should open with heading "Add shipping zone"
    When I fill in zone name with "Europe"
    And I check countries "DE", "FR", "GB"
    And I click "Save zone"
    Then zone "Europe" should appear in the list

  Scenario: Edit a shipping zone
    Given zone "Domestic" exists
    When I click "Edit" on "Domestic"
    And I change the name to "North America"
    And I click "Save zone"
    Then the zone should be renamed to "North America"

  Scenario: Delete a shipping zone
    Given zone "Old Zone" exists
    When I click "Delete" on "Old Zone"
    Then the zone should be removed

  Scenario: Add a shipping rate to a zone
    Given zone "Domestic" exists
    When I click "Add rate" within the "Domestic" zone
    Then a modal should open with heading "Add shipping rate"
    When I fill in rate name with "Overnight Shipping"
    And I select rate type "Flat rate"
    And I enter price "14.99"
    And I click "Save rate"
    Then "Overnight Shipping" should appear in the "Domestic" zone with price "14.99"

  Scenario: Rate type determines dynamic config fields
    When I open the rate form modal
    And I select type "Flat rate"
    Then I should see a price input
    When I select type "Weight-based"
    Then I should see min weight, max weight, and price inputs
    When I select type "Price-based"
    Then I should see min order amount, max order amount, and price inputs
    When I select type "Carrier-calculated"
    Then I should see an info callout about carrier integration

  Scenario: Delete a shipping rate
    Given rate "Old Rate" exists in zone "Domestic"
    When I click "Delete" on "Old Rate"
    Then the rate should be removed

  Scenario: Test shipping address tool
    Given zone "Domestic" with countries "US" and rate "Standard" ($5.00) exists
    When I fill in the test address with country "US", state "NY", city "New York", ZIP "10001"
    And I click "Test"
    Then I should see "Matched zone: Domestic"
    And I should see "Standard - $5.00"

  Scenario: Test address with no matching zone
    When I fill in the test address with a country not in any zone
    And I click "Test"
    Then I should see a warning "No shipping zone matches this address."
```

---

## Feature 15: Settings - Taxes

Source: Spec 03 S11.4, Pest SettingsTest (6 tests), E2E Suite 6

```gherkin
Feature: Settings - Taxes
  As an admin user
  I want to configure tax settings
  So that taxes are correctly applied to orders

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" with role "owner" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Tax settings page renders
    When I navigate to the Taxes settings tab
    Then I should see mode selection: "Manual tax rates" and "Tax provider"

  Scenario: Manual mode shows rate table
    When I select "Manual tax rates"
    Then I should see a table with columns: Zone name, Rate (%)
    And I should see a "Add rate" button

  Scenario: Add manual tax rate
    When I select "Manual tax rates"
    And I click "Add rate"
    Then a new row should appear with empty zone name and rate fields
    When I fill in zone name "EU" and rate "19.00"
    And I click "Save"
    Then the rate should be saved

  Scenario: Remove manual tax rate
    Given manual rate "EU" at 19% exists
    When I click the trash button on the "EU" rate
    Then the rate row should be removed

  Scenario: Provider mode shows provider config
    When I select "Tax provider"
    Then I should see a provider select (e.g., "Stripe Tax")
    And I should see an API key input field

  Scenario: Toggle tax-inclusive pricing
    When I toggle "Prices include tax" to on
    And I click "Save"
    Then a success toast should say "Tax settings saved"
    And the prices_include_tax setting should be true

  Scenario: Tax-inclusive toggle description
    Then the toggle should have description "When enabled, the listed price includes tax. Tax is calculated backwards from the price."
```

---

## Feature 16: Pages Management

Source: Spec 03 S13, Spec 09 Step 7.5, E2E Suite 17

```gherkin
Feature: Pages Management
  As an admin user
  I want to create and edit CMS pages
  So that I can publish static content like About and FAQ pages

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Pages list displays seeded pages
    Given page "About" exists for the store
    When I visit "/admin/pages"
    Then I should see the heading "Pages"
    And I should see "About" in the list
    And I should see an "Add page" button

  Scenario: Pages list shows correct columns
    When I visit "/admin/pages"
    Then the table should have columns: Title, Handle, Status, Updated date

  Scenario: Search pages by title
    Given pages "About" and "Contact" exist
    When I enter "About" in the search field
    Then "About" should be visible
    And "Contact" should not be visible

  Scenario: Create a new page
    When I visit "/admin/pages/create"
    And I fill in "Title" with "FAQ"
    And I fill in "Handle" with "faq"
    And I fill in body with "Frequently asked questions content here."
    And I select "Active" for status
    And I click "Save"
    Then a success toast should say "Page saved"
    And a page "FAQ" should exist in the database

  Scenario: Edit an existing page
    Given page "About" exists
    When I visit the edit page for "About"
    And I change the body to "Updated about page content."
    And I click "Save"
    Then a success toast should say "Page saved"

  Scenario: Page form two-column layout
    When I visit "/admin/pages/create"
    Then the left column should contain: Title, Handle, Body (textarea, 16 rows)
    And the right column should contain: Status select, Published at input

  Scenario: Delete a page
    Given page "Old Page" exists
    When I delete the page
    Then the page should be removed from the database
```

---

## Feature 17: Navigation Management

Source: Spec 03 S14, Spec 09 Step 7.5

```gherkin
Feature: Navigation Management
  As an admin user
  I want to manage navigation menus and menu items
  So that storefront visitors can navigate the site

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Navigation page shows existing menus
    Given menus "Main Menu" and "Footer Menu" exist
    When I visit "/admin/navigation"
    Then I should see the heading "Navigation"
    And I should see cards for "Main Menu" and "Footer Menu" with "Edit" buttons

  Scenario: Select a menu for editing
    When I click "Edit" on "Main Menu"
    Then the menu editor should appear
    And I should see the heading "Main Menu" with an "Add item" button
    And I should see existing menu items with drag handles, labels, types, and action buttons

  Scenario: Add a menu item via modal
    When I click "Add item" in the menu editor
    Then a modal should open with heading "Add menu item"
    When I fill in "Label" with "About Us"
    And I select type "Custom link"
    And I fill in URL with "/about"
    And I click "Save item"
    Then "About Us" should appear in the menu items list with "link: /about"

  Scenario: Add a page-type menu item
    When I add a menu item with type "Page" and select page "About"
    Then the item should appear with "page: About" as the target

  Scenario: Add a collection-type menu item
    When I add a menu item with type "Collection" and select "Summer Sale"
    Then the item should appear with "collection: Summer Sale" as the target

  Scenario: Edit a menu item
    Given menu item "Home" exists with link "/"
    When I click the edit button on "Home"
    Then a modal should open with heading "Edit menu item" pre-filled
    When I change the label to "Homepage"
    And I click "Save item"
    Then the item should show label "Homepage"

  Scenario: Remove a menu item
    Given menu item "Contact" exists
    When I click the trash button on "Contact"
    Then "Contact" should be removed from the items list

  Scenario: Reorder menu items via drag and drop
    Given items "Home", "Products", "About Us" exist in order
    When I drag "About Us" above "Products"
    Then the order should update to "Home", "About Us", "Products"

  Scenario: Save menu persists items
    Given I have modified menu items
    When I click "Save menu"
    Then all menu items and their positions should be persisted
    And a success toast should appear
```

---

## Feature 18: Themes Management

Source: Spec 03 S12, Spec 09 Step 7.5

```gherkin
Feature: Themes Management
  As an admin user
  I want to view, publish, duplicate, and customize themes
  So that I can control the store's appearance

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Themes list displays theme cards
    Given themes "My Theme" (published, v1.0) and "Dark Theme" (draft, v1.2) exist
    When I visit "/admin/themes"
    Then I should see the heading "Themes"
    And I should see a responsive grid of theme cards
    And "My Theme" should show a "Published" badge (green) and a blue ring border
    And "Dark Theme" should show a "Draft" badge (zinc)

  Scenario: Theme card displays correct elements
    Given theme "My Theme" exists
    Then the card should show: preview thumbnail, name, version, status badge
    And the card should have a "Customize" button and a more-actions dropdown

  Scenario: Theme more-actions dropdown
    When I click the more-actions (ellipsis) button on "Dark Theme"
    Then the dropdown should show: "Preview", "Publish", "Duplicate", and "Delete"

  Scenario: Publish a theme
    Given theme "Dark Theme" is a draft
    When I click "Publish" from the more-actions dropdown
    Then "Dark Theme" should become published
    And the previously published theme should become a draft

  Scenario: Duplicate a theme
    When I click "Duplicate" from the more-actions dropdown on "My Theme"
    Then a copy of "My Theme" should be created
    And the copy should appear in the themes grid

  Scenario: Delete a theme
    Given theme "Old Theme" is a draft
    When I click "Delete" from the more-actions dropdown
    Then the theme should be removed from the list

  Scenario: Customize button navigates to theme editor
    When I click "Customize" on "My Theme"
    Then I should be navigated to "/admin/themes/{themeId}/editor"
```

---

## Feature 19: Theme Editor

Source: Spec 03 S12.2, Spec 09 Step 7.5

```gherkin
Feature: Theme Editor
  As an admin user
  I want to customize theme sections and settings with a live preview
  So that I can visually configure the store's look and feel

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"
    And theme "My Theme" exists with sections: Header, Hero, Products, Footer

  Scenario: Theme editor three-panel layout
    When I visit the theme editor for "My Theme"
    Then I should see three panels:
      | Panel   | Width    | Content                    |
      | Left    | 264px    | Section list               |
      | Center  | Flexible | Live preview iframe        |
      | Right   | 320px    | Settings form              |

  Scenario: Top toolbar displays actions
    When I visit the theme editor
    Then I should see a "Back to themes" button (with left arrow)
    And I should see "Save" and "Save and publish" buttons

  Scenario: Section list displays all sections
    When I visit the theme editor
    Then the left panel should list: Header, Hero, Products, Footer

  Scenario: Select a section loads its settings
    When I click "Header" in the section list
    Then "Header" should be highlighted
    And the right panel should show settings for the Header section
    And the right panel heading should say "Header"

  Scenario: No section selected shows placeholder
    When I first load the theme editor without selecting a section
    Then the right panel should show "Select a section to edit its settings."

  Scenario: Dynamic settings form fields
    Given the Header section has fields:
      | Field         | Type     |
      | Logo text     | text     |
      | Background    | color    |
      | Show search   | checkbox |
      | Layout        | select   |
    When I select the "Header" section
    Then I should see a text input for "Logo text"
    And I should see a color picker for "Background"
    And I should see a checkbox for "Show search"
    And I should see a select for "Layout"

  Scenario: Live preview renders in iframe
    When I visit the theme editor
    Then the center panel should contain an iframe loading the storefront preview URL

  Scenario: Save theme settings
    When I modify a setting value
    And I click "Save"
    Then the theme settings should be persisted
    And a success toast should appear

  Scenario: Save and publish
    When I click "Save and publish"
    Then the theme settings should be saved
    And the theme should be published as the active theme
```

---

## Feature 20: Analytics Page

Source: Spec 03 S17, Spec 09 Step 7.5, E2E Suite 18

```gherkin
Feature: Analytics Page
  As an admin user
  I want to view sales analytics, top products, and conversion data
  So that I can make data-driven business decisions

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Analytics page renders with heading and filters
    When I visit "/admin/analytics"
    Then I should see the heading "Analytics"
    And I should see filters: Date range, Channel, Device
    And I should see an "Export CSV" button

  Scenario: KPI tiles display
    Given orders exist for the store
    When I visit "/admin/analytics"
    Then I should see KPI tiles: "Total Sales", "Orders", "AOV", "Conv Rate"

  Scenario: Sales chart renders
    When I visit "/admin/analytics"
    Then I should see a sales chart showing daily revenue or order data

  Scenario: Top products table (10-20 rows)
    Given sales data exists
    When I visit "/admin/analytics"
    Then I should see a top products table with columns: Rank, Product, Units Sold, Revenue, % of Total

  Scenario: Top referrers table
    When I visit "/admin/analytics"
    Then I should see a top referrers table with columns: Source, Sessions, Orders, Conversion Rate

  Scenario: Channel filter
    When I select "Storefront" from the Channel filter
    Then analytics should update to show only storefront data

  Scenario: Device filter
    When I select "Mobile" from the Device filter
    Then analytics should update to show only mobile data

  Scenario: Date range filtering
    When I select "Last 7 days" from the date range dropdown
    Then the analytics data should update for the last 7 days

  Scenario: Export CSV
    When I click "Export CSV"
    Then an export should be initiated
    And a loading indicator should appear next to the button
    And when the export is ready, a download link should be displayed

  Scenario: Conversion funnel data
    When I visit "/admin/analytics"
    Then I should see "Visits" label as part of the funnel display
```

---

## Feature 21: Apps Page (Stub)

Source: Spec 03 S15, Spec 09 Step 7.5

```gherkin
Feature: Apps Page
  As an admin user
  I want to view installed apps
  So that I can manage third-party integrations

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Apps page renders
    When I visit "/admin/apps"
    Then I should see the heading "Apps"

  Scenario: Installed apps list
    Given app "My Integration App" is installed with status "active"
    When I visit "/admin/apps"
    Then I should see "My Integration App" with an "Active" badge (green)
    And I should see the install date as a relative timestamp

  Scenario: Empty state when no apps installed
    Given no apps are installed
    When I visit "/admin/apps"
    Then I should see "No apps installed"

  Scenario: App card links to detail page
    Given app "My Integration App" is installed
    When I click on "My Integration App"
    Then I should see app detail with scopes, webhooks table, and usage stats

  Scenario: Uninstall app with confirmation
    Given app "My Integration App" is installed
    When I click "Uninstall" on the app
    Then a confirmation should appear
    When I confirm
    Then the app should be uninstalled
```

---

## Feature 22: Developers Page (Stub)

Source: Spec 03 S16, Spec 09 Step 7.5

```gherkin
Feature: Developers Page
  As an admin user
  I want to manage API tokens and webhook subscriptions
  So that I can integrate external services with the store

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Developers page renders with two sections
    When I visit "/admin/developers"
    Then I should see the heading "Developers"
    And I should see "API tokens" section with a table
    And I should see "Webhooks" section with a table

  Scenario: API tokens table displays existing tokens
    Given token "My integration" exists with last used "2 hours ago"
    When I visit "/admin/developers"
    Then I should see "My integration" in the tokens table
    And the table should show columns: Name, Last used, Created, Actions

  Scenario: Generate a new API token
    When I click "Generate new token"
    Then a modal should open with heading "Generate API token"
    When I fill in token name "CI Pipeline"
    And I click "Generate"
    Then a warning callout should display: "Copy this token now. It will not be shown again."
    And the generated token should be visible
    And the token should appear in the tokens table

  Scenario: Revoke an API token
    Given token "Old Token" exists
    When I click "Revoke" on "Old Token"
    Then the token should be removed from the table

  Scenario: Webhooks table displays existing subscriptions
    Given webhook for "order.created" to "https://ex.com/hook" exists with status "active"
    When I visit "/admin/developers"
    Then the webhooks table should show "order.created", "https://ex.com/hook", "Active"
    And the table should have columns: Event type, URL, Status, Actions

  Scenario: Add a webhook via modal
    When I click "Add webhook"
    Then a modal should open with heading "Add webhook"
    When I select event type "order.created"
    And I fill in endpoint URL "https://example.com/webhooks"
    And I click "Save"
    Then the webhook should appear in the table

  Scenario: Edit a webhook
    Given webhook for "order.created" exists
    When I click the edit button on the webhook
    Then a modal should open with heading "Edit webhook" pre-filled
    When I change the event type to "product.updated"
    And I click "Save"
    Then the webhook should be updated

  Scenario: Delete a webhook
    Given webhook for "order.created" exists
    When I click the delete button on the webhook
    Then the webhook should be removed from the table

  Scenario: Webhook status indicators
    Given webhooks with statuses "active" and "failing" exist
    Then "active" webhooks should have a green badge
    And "failing" webhooks should have a red badge
```

---

## Feature 23: Inventory Management

Source: Spec 03 S6, Spec 09 Step 7.5 (implied via sidebar nav)

```gherkin
Feature: Inventory Management
  As an admin user
  I want to view and manage inventory levels across all variants
  So that I can monitor stock and prevent overselling

  Background:
    Given a store "Acme" exists
    And an admin user "admin@acme.test" exists
    And I am logged in as admin "admin@acme.test"

  Scenario: Inventory page renders
    When I visit "/admin/inventory"
    Then I should see the heading "Inventory"
    And I should see a search field and stock filter dropdown

  Scenario: Inventory table shows correct columns
    Then the table should have columns: Product, Variant, SKU, On Hand, Reserved, Policy, Actions

  Scenario: Search inventory by product title or SKU
    Given inventory items for "Shirt" (SKU: SH-001) and "Hat" (SKU: HT-001) exist
    When I enter "SH-001" in the search field
    Then I should see the "Shirt" inventory row
    And "Hat" should not be visible

  Scenario: Filter by stock level
    When I select "Out of stock" from the stock filter
    Then only items with 0 on-hand quantity should be visible
    When I select "Low stock" from the filter
    Then only items below the low stock threshold should be visible

  Scenario: Inline edit on-hand quantity
    Given inventory item "SH-001" has on-hand quantity "45"
    When I change the on-hand quantity to "50" inline
    Then the quantity should be updated in the database

  Scenario: Policy displays as badge
    Then "deny" policy items should show a "deny" badge
    And "continue" policy items should show a "continue" badge
```

---

# Traceability Table

| Scenario | Spec Reference | Pest Test | E2E Test |
|----------|---------------|-----------|----------|
| F1: Sidebar nav groups | Spec 03 S1.2 | - | Test 2.8 |
| F1: Active item highlight | Spec 03 S1.2 | - | Test 2.8 |
| F1: SPA navigation | Spec 03 S1.2 | - | Test 2.8 |
| F1: Desktop sidebar | Spec 03 S1.2 | - | - |
| F1: Mobile sidebar | Spec 03 S1.2 | - | - |
| F1: Topbar store selector | Spec 03 S1.3 | - | - |
| F1: Switch store | Spec 03 S1.3 | - | - |
| F1: Logout | Spec 03 S1.3 | - | Test 2.7 |
| F1: Breadcrumbs | Spec 03 S1.4 | - | - |
| F1: Toast success | Spec 03 S1.5 | - | - |
| F1: Toast error | Spec 03 S1.5 | - | - |
| F1: Toast stacking | Spec 03 S1.5 | - | - |
| F1: Dark mode | Spec 03 S1.6 | - | - |
| F2: Dashboard renders | Spec 03 S2 | DashboardTest: renders the admin dashboard | Test 2.1 |
| F2: KPI tiles correct data | Spec 03 S2 | DashboardTest: shows KPI tiles with correct data | - |
| F2: Orders chart | Spec 03 S2 | - | - |
| F2: Top products | Spec 03 S2 | - | - |
| F2: Top products empty | Spec 03 S2 | - | - |
| F2: Conversion funnel | Spec 03 S2 | - | - |
| F2: Date range filtering | Spec 03 S2 | DashboardTest: filters metrics by date range | - |
| F2: Custom date range | Spec 03 S2 | - | - |
| F2: Loading state | Spec 03 S2 | - | - |
| F2: Auth restriction | Spec 03 S2, Spec 06 | DashboardTest: restricts dashboard to authenticated admins | Test 2.5, 2.6 |
| F3: Product list seeded | Spec 03 S3 | ProductManagementTest: lists products with pagination | Test 3.1 |
| F3: Correct columns | Spec 03 S3 | - | - |
| F3: Pagination | Spec 03 S3 | ProductManagementTest: lists products with pagination | - |
| F3: Search products | Spec 03 S3 | - | Test 3.6 |
| F3: Filter by status | Spec 03 S3 | - | Test 3.7 |
| F3: Filter by type | Spec 03 S3 | - | - |
| F3: Sort by column | Spec 03 S3 | - | - |
| F3: Default sort | Spec 03 S3 | - | - |
| F3: Bulk select | Spec 03 S3 | - | - |
| F3: Bulk archive | Spec 03 S3 | ProductManagementTest: bulk archives selected products | - |
| F3: Bulk set active | Spec 03 S3 | - | - |
| F3: Bulk delete | Spec 03 S3 | - | - |
| F3: Cancel bulk delete | Spec 03 S3 | - | - |
| F3: Status badge colors | Spec 03 S3 | - | - |
| F3: Title links to edit | Spec 03 S3 | - | - |
| F3: Empty state | Spec 03 S3 | - | - |
| F3: Filtered empty state | Spec 03 S3 | - | - |
| F3: Loading state | Spec 03 S3 | - | - |
| F3: Role restriction | Spec 03, Spec 06 | ProductManagementTest: restricts product management to authorized roles | - |
| F3: Staff cannot delete | Spec 06 | ProductManagementTest: staff can create but not delete products | - |
| F4: Create mode rendering | Spec 03 S4 | - | - |
| F4: Create product | Spec 03 S4 | ProductManagementTest: creates a product via admin form | Test 3.2 |
| F4: Edit mode rendering | Spec 03 S4 | - | - |
| F4: Edit product title | Spec 03 S4 | ProductManagementTest: edits a product via admin form | Test 3.3 |
| F4: Two-column layout | Spec 03 S4 | - | - |
| F4: Validation fails | Spec 03 S4 | - | - |
| F4: Unique handle | Spec 03 S4 | - | - |
| F4: Status select | Spec 03 S4 | - | Test 3.4 |
| F4: Publishing card | Spec 03 S4 | - | - |
| F4: Organization fields | Spec 03 S4 | - | - |
| F4: Collections checkboxes | Spec 03 S4 | - | - |
| F4: SEO collapsible | Spec 03 S4 | - | - |
| F4: Discard button | Spec 03 S4 | - | - |
| F4: Save loading state | Spec 03 S4 | - | - |
| F4: Delete with confirmation | Spec 03 S4 | - | - |
| F4: Cancel deletion | Spec 03 S4 | - | - |
| F5: Add option | Spec 03 S4 (Variants) | - | - |
| F5: Define option values | Spec 03 S4 | - | - |
| F5: Auto-generate variants | Spec 03 S4 | ProductManagementTest: manages variants from the product form | - |
| F5: Variant table fields | Spec 03 S4 | - | - |
| F5: Remove option regenerates | Spec 03 S4 | - | - |
| F5: Add value regenerates | Spec 03 S4 | - | - |
| F5: Manages variants (Pest) | Spec 09 | ProductManagementTest: manages variants from the product form | - |
| F6: Upload zone renders | Spec 03 S4 (Media) | - | - |
| F6: Upload file | Spec 03 S4 | ProductManagementTest: uploads media from the product form | - |
| F6: Media grid layout | Spec 03 S4 | - | - |
| F6: Delete media | Spec 03 S4 | - | - |
| F6: Edit alt text | Spec 03 S4 | - | - |
| F6: Reorder media | Spec 03 S4 | - | - |
| F6: Uploads media (Pest) | Spec 09 | ProductManagementTest: uploads media from the product form | - |
| F7: Order list seeded | Spec 03 S7 | - | Test 4.1 |
| F7: Correct columns | Spec 03 S7 | - | - |
| F7: Search by order # | Spec 03 S7 | - | - |
| F7: Search by email | Spec 03 S7 | - | - |
| F7: Filter by status tabs | Spec 03 S7 | OrderManagementTest: lists orders with status filter | Test 4.2 |
| F7: Status filter tabs | Spec 03 S7 | - | - |
| F7: Financial badges | Spec 03 S7 | - | - |
| F7: Fulfillment badges | Spec 03 S7 | - | - |
| F7: Sort by column | Spec 03 S7 | - | - |
| F7: Default sort | Spec 03 S7 | - | - |
| F7: Order link to detail | Spec 03 S7 | - | - |
| F7: Pagination | Spec 03 S7 | - | - |
| F7: Role restriction | Spec 06 | OrderManagementTest: restricts order management by role | - |
| F8: Order heading + statuses | Spec 03 S8 | OrderManagementTest: shows order detail page | Test 4.3 |
| F8: Line items table | Spec 03 S8 | OrderManagementTest: shows order detail page | Test 4.3 |
| F8: Order summary block | Spec 03 S8 | - | Test 4.3 |
| F8: Timeline | Spec 03 S8 | - | Test 4.4 |
| F8: Customer card | Spec 03 S8 | - | Test 4.7 |
| F8: Guest customer | Spec 03 S8 | - | - |
| F8: Address cards | Spec 03 S8 | - | - |
| F8: Payment details | Spec 03 S8 | - | - |
| F8: Action buttons | Spec 03 S8 | - | - |
| F8: Confirm bank transfer | Spec 03 S8 | - | Test 4.8 |
| F8: Fulfillment guard | Spec 03 S8 | - | Test 4.9 |
| F8: Create fulfillment | Spec 03 S8 | OrderManagementTest: creates a fulfillment from order detail | Test 4.5 |
| F8: Mark as shipped | Spec 03 S8 | - | Test 4.10 |
| F8: Mark as delivered | Spec 03 S8 | - | Test 4.11 |
| F8: Create refund | Spec 03 S8 | OrderManagementTest: processes a refund from order detail | Test 4.6 |
| F8: Fulfillment tracking | Spec 03 S8 | - | - |
| F9: Collection list | Spec 03 S5.1 | - | Test 15.1 |
| F9: Correct columns | Spec 03 S5.1 | - | - |
| F9: Search collections | Spec 03 S5.1 | - | - |
| F9: Filter by status | Spec 03 S5.1 | - | - |
| F9: Empty state | Spec 03 S5.1 | - | - |
| F9: Create collection | Spec 03 S5.2 | - | Test 15.2 |
| F9: Edit collection | Spec 03 S5.2 | - | Test 15.3 |
| F9: Two-column layout | Spec 03 S5.2 | - | - |
| F9: Add products | Spec 03 S5.2 | - | - |
| F9: Remove products | Spec 03 S5.2 | - | - |
| F9: Reorder products | Spec 03 S5.2 | - | - |
| F9: Delete collection | Spec 03 S5.1 | - | - |
| F10: Customer list | Spec 03 S9.1 | - | Test 16.1 |
| F10: Correct columns | Spec 03 S9.1 | - | - |
| F10: Search customers | Spec 03 S9.1 | - | - |
| F10: Row links to detail | Spec 03 S9.1 | - | - |
| F10: Detail info card | Spec 03 S9.2 | - | Test 16.2 |
| F10: Order history | Spec 03 S9.2 | - | Test 16.2 |
| F10: Addresses | Spec 03 S9.2 | - | Test 16.3 |
| F10: Add address modal | Spec 03 S9.2 | - | - |
| F10: Edit address modal | Spec 03 S9.2 | - | - |
| F10: Delete address | Spec 03 S9.2 | - | - |
| F10: Set default address | Spec 03 S9.2 | - | - |
| F11: Discount list seeded | Spec 03 S10.1 | DiscountManagementTest: lists discounts | Test 5.1 |
| F11: Correct columns | Spec 03 S10.1 | - | - |
| F11: Search discounts | Spec 03 S10.1 | - | - |
| F11: Filter by status | Spec 03 S10.1 | - | - |
| F11: Status badge colors | Spec 03 S10.1 | - | Test 5.6 |
| F11: Create percent | Spec 03 S10.2 | DiscountManagementTest: creates a percent discount | Test 5.2 |
| F11: Create fixed | Spec 03 S10.2 | DiscountManagementTest: creates a fixed discount | Test 5.3 |
| F11: Create free shipping | Spec 03 S10.2 | - | Test 5.4 |
| F11: Auto-generate code | Spec 03 S10.2 | - | - |
| F11: Create automatic | Spec 03 S10.2 | - | - |
| F11: Edit discount | Spec 03 S10.2 | DiscountManagementTest: edits a discount | Test 5.5 |
| F11: Validates uniqueness | Spec 03 S10.2 | DiscountManagementTest: validates discount code uniqueness | - |
| F11: Disable discount | Spec 03 S10.2 | DiscountManagementTest: disables a discount | - |
| F11: Conditions section | Spec 03 S10.2 | - | - |
| F11: Add specific products | Spec 03 S10.2 | - | - |
| F11: Usage limits | Spec 03 S10.2 | - | - |
| F11: Active dates | Spec 03 S10.2 | - | - |
| F12: Settings renders | Spec 03 S11.1 | SettingsTest: renders the settings page | Test 6.1 |
| F12: Store details | Spec 03 S11.1 | - | Test 6.1 |
| F12: Handle read-only | Spec 03 S11.1 | - | - |
| F12: Update store name | Spec 03 S11.1 | SettingsTest: updates general store settings | Test 6.2 |
| F12: Defaults section | Spec 03 S11.1 | - | - |
| F12: Role restriction | Spec 06 | SettingsTest: restricts settings to owner and admin roles | - |
| F13: Domains tab | Spec 03 S11.2 | SettingsTest: manages store domains | Test 6.7 |
| F13: Add domain | Spec 03 S11.2 | SettingsTest: manages store domains | - |
| F13: Set primary | Spec 03 S11.2 | - | - |
| F13: Delete domain | Spec 03 S11.2 | - | - |
| F14: Shipping zones | Spec 03 S11.3 | SettingsTest: configures shipping zones | Test 6.3 |
| F14: Add zone | Spec 03 S11.3 | SettingsTest: configures shipping zones | - |
| F14: Edit zone | Spec 03 S11.3 | - | - |
| F14: Delete zone | Spec 03 S11.3 | - | - |
| F14: Add rate | Spec 03 S11.3 | - | Test 6.4 |
| F14: Rate type fields | Spec 03 S11.3 | - | - |
| F14: Delete rate | Spec 03 S11.3 | - | - |
| F14: Test address match | Spec 03 S11.3 | - | - |
| F14: Test address no match | Spec 03 S11.3 | - | - |
| F15: Tax settings render | Spec 03 S11.4 | SettingsTest: configures tax settings | Test 6.5 |
| F15: Manual rate table | Spec 03 S11.4 | SettingsTest: configures tax settings | - |
| F15: Add manual rate | Spec 03 S11.4 | - | - |
| F15: Remove manual rate | Spec 03 S11.4 | - | - |
| F15: Provider mode | Spec 03 S11.4 | - | - |
| F15: Toggle tax-inclusive | Spec 03 S11.4 | - | Test 6.6 |
| F15: Toggle description | Spec 03 S11.4 | - | - |
| F16: Pages list | Spec 03 S13.1 | - | Test 17.1 |
| F16: Correct columns | Spec 03 S13.1 | - | - |
| F16: Search pages | Spec 03 S13.1 | - | - |
| F16: Create page | Spec 03 S13.2 | - | Test 17.2 |
| F16: Edit page | Spec 03 S13.2 | - | Test 17.3 |
| F16: Two-column layout | Spec 03 S13.2 | - | - |
| F16: Delete page | Spec 03 S13.2 | - | - |
| F17: Menu list | Spec 03 S14 | - | - |
| F17: Select menu | Spec 03 S14 | - | - |
| F17: Add menu item | Spec 03 S14 | - | - |
| F17: Page-type item | Spec 03 S14 | - | - |
| F17: Collection-type item | Spec 03 S14 | - | - |
| F17: Edit menu item | Spec 03 S14 | - | - |
| F17: Remove menu item | Spec 03 S14 | - | - |
| F17: Reorder items | Spec 03 S14 | - | - |
| F17: Save menu | Spec 03 S14 | - | - |
| F18: Themes list | Spec 03 S12.1 | - | Test 2.10 |
| F18: Card elements | Spec 03 S12.1 | - | - |
| F18: More-actions dropdown | Spec 03 S12.1 | - | - |
| F18: Publish theme | Spec 03 S12.1 | - | - |
| F18: Duplicate theme | Spec 03 S12.1 | - | - |
| F18: Delete theme | Spec 03 S12.1 | - | - |
| F18: Customize navigates | Spec 03 S12.1 | - | - |
| F19: Three-panel layout | Spec 03 S12.2 | - | - |
| F19: Top toolbar | Spec 03 S12.2 | - | - |
| F19: Section list | Spec 03 S12.2 | - | - |
| F19: Select section | Spec 03 S12.2 | - | - |
| F19: No section placeholder | Spec 03 S12.2 | - | - |
| F19: Dynamic settings fields | Spec 03 S12.2 | - | - |
| F19: Live preview iframe | Spec 03 S12.2 | - | - |
| F19: Save settings | Spec 03 S12.2 | - | - |
| F19: Save and publish | Spec 03 S12.2 | - | - |
| F20: Analytics renders | Spec 03 S17 | - | Test 18.1 |
| F20: KPI tiles | Spec 03 S17 | - | Test 18.2 |
| F20: Sales chart | Spec 03 S17 | - | - |
| F20: Top products | Spec 03 S17 | - | - |
| F20: Top referrers | Spec 03 S17 | - | - |
| F20: Channel filter | Spec 03 S17 | - | - |
| F20: Device filter | Spec 03 S17 | - | - |
| F20: Date range | Spec 03 S17 | - | - |
| F20: Export CSV | Spec 03 S17 | - | - |
| F20: Funnel data | Spec 03 S17 | - | Test 18.3 |
| F21: Apps page | Spec 03 S15 | - | - |
| F21: Installed apps list | Spec 03 S15 | - | - |
| F21: Empty state | Spec 03 S15 | - | - |
| F21: App detail link | Spec 03 S15 | - | - |
| F21: Uninstall app | Spec 03 S15 | - | - |
| F22: Developers page | Spec 03 S16 | - | - |
| F22: API tokens table | Spec 03 S16 | - | - |
| F22: Generate token | Spec 03 S16 | - | - |
| F22: Revoke token | Spec 03 S16 | - | - |
| F22: Webhooks table | Spec 03 S16 | - | - |
| F22: Add webhook | Spec 03 S16 | - | - |
| F22: Edit webhook | Spec 03 S16 | - | - |
| F22: Delete webhook | Spec 03 S16 | - | - |
| F22: Webhook status | Spec 03 S16 | - | - |
| F23: Inventory page | Spec 03 S6 | - | - |
| F23: Correct columns | Spec 03 S6 | - | - |
| F23: Search inventory | Spec 03 S6 | - | - |
| F23: Filter by stock | Spec 03 S6 | - | - |
| F23: Inline edit quantity | Spec 03 S6 | - | - |
| F23: Policy badge | Spec 03 S6 | - | - |

---

# Self-Assessment

## Coverage Analysis

- **Spec 09 (Implementation Roadmap) Steps 7.1-7.5:** Fully covered. All listed Livewire components and views are represented across Features 1-23.
- **Spec 03 (Admin UI) Sections 1-17:** All 17 sections of the admin UI spec are covered:
  - S1 (Layout Shell) -> Feature 1
  - S2 (Dashboard) -> Feature 2
  - S3 (Products List) -> Feature 3
  - S4 (Product Create/Edit) -> Features 4, 5, 6
  - S5 (Collections) -> Feature 9
  - S6 (Inventory) -> Feature 23
  - S7 (Orders List) -> Feature 7
  - S8 (Order Detail) -> Feature 8
  - S9 (Customers) -> Feature 10
  - S10 (Discounts) -> Feature 11
  - S11 (Settings) -> Features 12, 13, 14, 15
  - S12 (Themes) -> Features 18, 19
  - S13 (Pages) -> Feature 16
  - S14 (Navigation) -> Feature 17
  - S15 (Apps) -> Feature 21
  - S16 (Developers) -> Feature 22
  - S17 (Analytics) -> Feature 20

## Pest Test Mapping

- **DashboardTest (4 tests):** All 4 tests mapped (F2 scenarios: renders, KPI data, auth restriction, date range filtering).
- **ProductManagementTest (8 tests):** All 8 tests mapped (F3: list/pagination, bulk archive, role restriction, staff authorization; F4: create, edit; F5: variants; F6: media upload).
- **OrderManagementTest (5 tests):** All 5 tests mapped (F7: list with status filter, role restriction; F8: order detail, create fulfillment, process refund).
- **DiscountManagementTest (6 tests):** All 6 tests mapped (F11: list, create percent, create fixed, uniqueness validation, edit, disable).
- **SettingsTest (6 tests):** All 6 tests mapped (F12: renders, update store name, role restriction; F13: manage domains; F14: configure shipping zones; F15: configure tax settings).

## E2E Test Mapping

- **Suite 2 (Admin Authentication, 10 tests):** Tests 2.1, 2.5-2.10 mapped to Features 1 and 2.
- **Suite 3 (Admin Product Management, 7 tests):** Tests 3.1-3.7 mapped to Features 3 and 4.
- **Suite 4 (Admin Order Management, 11 tests):** Tests 4.1-4.11 mapped to Features 7 and 8.
- **Suite 5 (Admin Discount Management, 6 tests):** Tests 5.1-5.6 mapped to Feature 11.
- **Suite 6 (Admin Settings, 7 tests):** Tests 6.1-6.7 mapped to Features 12-15.
- **Suite 15 (Admin Collections, 3 tests):** Tests 15.1-15.3 mapped to Feature 9.
- **Suite 16 (Admin Customers, 3 tests):** Tests 16.1-16.3 mapped to Feature 10.
- **Suite 17 (Admin Pages, 3 tests):** Tests 17.1-17.3 mapped to Feature 16.
- **Suite 18 (Admin Analytics, 3 tests):** Tests 18.1-18.3 mapped to Feature 20.

## Gaps and Notes

- **Admin authentication (login form):** E2E Suite 2 covers Tests 2.1-2.4 (login, invalid credentials, empty fields). These are admin auth flows, not admin panel CRUD. They are referenced in the traceability table but not broken into separate Gherkin features since login/registration is a cross-cutting concern already tested in admin auth.
- **Inventory management (F23):** The inventory page is included in the sidebar navigation (Spec 03 S6) but is not listed as a separate step in the roadmap. It is covered as Feature 23 for completeness.
- **Theme Editor preview (F19):** The live preview iframe behavior depends on the storefront being rendered, which involves Phase 3 components. The Gherkin scenarios cover the editor UI but the iframe content is storefront-owned.
- **Apps and Developers (F21, F22):** These are stubs in Phase 7 since the underlying app/webhook infrastructure is Phase 10 (Apps and Webhooks). The Gherkin scenarios cover the admin UI that will exist, but full functionality requires Phase 10 completion.
- **Responsive layout testing:** Spec 03 mentions responsive behavior throughout (mobile sidebar, responsive grids, mobile-specific controls). These are covered at the layout level (F1) but individual page responsive tests are presentational details not broken into separate scenarios.
- **Draft product visibility (Test 3.5):** This cross-cuts admin and storefront. The admin side (seeing draft products in the list) is covered in F3. The storefront side (not showing drafts) belongs to storefront browsing tests from earlier phases.
