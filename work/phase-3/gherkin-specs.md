# Phase 3: Themes, Pages, Navigation, Storefront Layout - Gherkin Specifications

---

## Step 3.1: Migrations (Batch 3)

### Feature: Theme Database Tables

```gherkin
Feature: Theme database migrations
  The platform needs database tables for themes, theme files, theme settings,
  pages, navigation menus, and navigation items to support storefront customization.

  # --- themes table ---

  Scenario: themes table exists with required columns
    Given the migrations have been run
    Then the "themes" table should exist
    And it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "store_id" integer column
    And it should have a non-nullable "name" text column
    And it should have a nullable "version" text column
    And it should have a non-nullable "status" text column with default "draft"
    And it should have a nullable "published_at" text column
    And it should have nullable "created_at" and "updated_at" text columns

  Scenario: themes table has a check constraint on status
    Given the migrations have been run
    Then the "themes" table "status" column should only allow values "draft" and "published"

  Scenario: themes table has a foreign key to stores
    Given the migrations have been run
    Then the "themes" table should have a foreign key from "store_id" to "stores(id)" with ON DELETE CASCADE

  Scenario: themes table has required indexes
    Given the migrations have been run
    Then the "themes" table should have an index "idx_themes_store_id" on ("store_id")
    And the "themes" table should have an index "idx_themes_store_status" on ("store_id", "status")

  # --- theme_files table ---

  Scenario: theme_files table exists with required columns
    Given the migrations have been run
    Then the "theme_files" table should exist
    And it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "theme_id" integer column
    And it should have a non-nullable "path" text column
    And it should have a non-nullable "storage_key" text column
    And it should have a non-nullable "sha256" text column
    And it should have a non-nullable "byte_size" integer column with default 0

  Scenario: theme_files table has a foreign key to themes
    Given the migrations have been run
    Then the "theme_files" table should have a foreign key from "theme_id" to "themes(id)" with ON DELETE CASCADE

  Scenario: theme_files table has required indexes
    Given the migrations have been run
    Then the "theme_files" table should have a unique index "idx_theme_files_theme_path" on ("theme_id", "path")
    And the "theme_files" table should have an index "idx_theme_files_theme_id" on ("theme_id")

  # --- theme_settings table ---

  Scenario: theme_settings table exists with required columns
    Given the migrations have been run
    Then the "theme_settings" table should exist
    And it should have a "theme_id" integer as primary key
    And it should have a non-nullable "settings_json" text column with default "{}"
    And it should have a nullable "updated_at" text column

  Scenario: theme_settings table has a foreign key to themes
    Given the migrations have been run
    Then the "theme_settings" table should have a foreign key from "theme_id" to "themes(id)" with ON DELETE CASCADE

  # --- pages table ---

  Scenario: pages table exists with required columns
    Given the migrations have been run
    Then the "pages" table should exist
    And it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "store_id" integer column
    And it should have a non-nullable "title" text column
    And it should have a non-nullable "handle" text column
    And it should have a nullable "body_html" text column
    And it should have a non-nullable "status" text column with default "draft"
    And it should have a nullable "published_at" text column
    And it should have nullable "created_at" and "updated_at" text columns

  Scenario: pages table has a check constraint on status
    Given the migrations have been run
    Then the "pages" table "status" column should only allow values "draft", "published", and "archived"

  Scenario: pages table has a foreign key to stores
    Given the migrations have been run
    Then the "pages" table should have a foreign key from "store_id" to "stores(id)" with ON DELETE CASCADE

  Scenario: pages table has required indexes
    Given the migrations have been run
    Then the "pages" table should have a unique index "idx_pages_store_handle" on ("store_id", "handle")
    And the "pages" table should have an index "idx_pages_store_id" on ("store_id")
    And the "pages" table should have an index "idx_pages_store_status" on ("store_id", "status")

  # --- navigation_menus table ---

  Scenario: navigation_menus table exists with required columns
    Given the migrations have been run
    Then the "navigation_menus" table should exist
    And it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "store_id" integer column
    And it should have a non-nullable "handle" text column
    And it should have a non-nullable "title" text column
    And it should have nullable "created_at" and "updated_at" text columns

  Scenario: navigation_menus table has a foreign key to stores
    Given the migrations have been run
    Then the "navigation_menus" table should have a foreign key from "store_id" to "stores(id)" with ON DELETE CASCADE

  Scenario: navigation_menus table has required indexes
    Given the migrations have been run
    Then the "navigation_menus" table should have a unique index "idx_navigation_menus_store_handle" on ("store_id", "handle")
    And the "navigation_menus" table should have an index "idx_navigation_menus_store_id" on ("store_id")

  # --- navigation_items table ---

  Scenario: navigation_items table exists with required columns
    Given the migrations have been run
    Then the "navigation_items" table should exist
    And it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "menu_id" integer column
    And it should have a non-nullable "type" text column with default "link"
    And it should have a non-nullable "label" text column
    And it should have a nullable "url" text column
    And it should have a nullable "resource_id" integer column
    And it should have a non-nullable "position" integer column with default 0

  Scenario: navigation_items table has a check constraint on type
    Given the migrations have been run
    Then the "navigation_items" table "type" column should only allow values "link", "page", "collection", and "product"

  Scenario: navigation_items table has a foreign key to navigation_menus
    Given the migrations have been run
    Then the "navigation_items" table should have a foreign key from "menu_id" to "navigation_menus(id)" with ON DELETE CASCADE

  Scenario: navigation_items table has required indexes
    Given the migrations have been run
    Then the "navigation_items" table should have an index "idx_navigation_items_menu_id" on ("menu_id")
    And the "navigation_items" table should have an index "idx_navigation_items_menu_position" on ("menu_id", "position")
```

---

## Step 3.2: Models, Enums, Factories, Seeders

### Feature: Theme Model

```gherkin
Feature: Theme model
  The Theme model represents a storefront theme belonging to a store.

  Scenario: Theme belongs to a Store
    Given a store exists
    And a theme exists for that store
    When I access the theme's store relationship
    Then I should receive the owning Store model

  Scenario: Theme has many ThemeFiles
    Given a theme exists
    And 3 theme files exist for that theme
    When I access the theme's files relationship
    Then I should receive a collection of 3 ThemeFile models

  Scenario: Theme has one ThemeSettings
    Given a theme exists
    And theme settings exist for that theme
    When I access the theme's settings relationship
    Then I should receive the ThemeSettings model

  Scenario: Theme casts status to ThemeStatus enum
    Given a theme exists with status "published"
    When I access the theme's status attribute
    Then I should receive a ThemeStatus::Published enum value

  Scenario: Theme factory creates valid theme
    Given a store exists
    When I use the Theme factory to create a theme
    Then the theme should be persisted in the database
    And it should have a name, status, and store_id

  Scenario: Deleting a store cascades to its themes
    Given a store exists
    And a theme exists for that store
    When the store is deleted
    Then the theme should also be deleted
```

### Feature: ThemeFile Model

```gherkin
Feature: ThemeFile model
  The ThemeFile model represents an individual file within a theme.

  Scenario: ThemeFile belongs to a Theme
    Given a theme exists
    And a theme file exists for that theme
    When I access the theme file's theme relationship
    Then I should receive the owning Theme model

  Scenario: ThemeFile enforces unique path per theme
    Given a theme exists
    And a theme file exists with path "templates/index.html" for that theme
    When I try to create another theme file with path "templates/index.html" for the same theme
    Then a unique constraint violation should occur

  Scenario: ThemeFile factory creates valid theme file
    Given a theme exists
    When I use the ThemeFile factory to create a theme file
    Then the theme file should be persisted in the database
    And it should have a path, storage_key, sha256, and byte_size

  Scenario: Deleting a theme cascades to its files
    Given a theme exists
    And 3 theme files exist for that theme
    When the theme is deleted
    Then all 3 theme files should also be deleted
```

### Feature: ThemeSettings Model

```gherkin
Feature: ThemeSettings model
  The ThemeSettings model stores JSON configuration for a theme.

  Scenario: ThemeSettings belongs to a Theme
    Given a theme exists
    And theme settings exist for that theme
    When I access the theme settings' theme relationship
    Then I should receive the owning Theme model

  Scenario: ThemeSettings casts settings_json to array
    Given theme settings exist with settings_json containing announcement bar configuration
    When I access the settings_json attribute
    Then I should receive a PHP array (not a raw JSON string)

  Scenario: ThemeSettings uses theme_id as primary key
    Given a theme exists with id 42
    And theme settings exist for that theme
    Then the theme_settings record should have theme_id 42 as its primary key

  Scenario: ThemeSettings factory creates valid settings
    Given a theme exists
    When I use the ThemeSettings factory to create settings
    Then the settings should be persisted in the database
    And it should have a valid settings_json value

  Scenario: Deleting a theme cascades to its settings
    Given a theme exists
    And theme settings exist for that theme
    When the theme is deleted
    Then the theme settings should also be deleted
```

### Feature: Page Model

```gherkin
Feature: Page model
  The Page model represents a static CMS page belonging to a store.

  Scenario: Page belongs to a Store
    Given a store exists
    And a page exists for that store
    When I access the page's store relationship
    Then I should receive the owning Store model

  Scenario: Page casts status to PageStatus enum
    Given a page exists with status "published"
    When I access the page's status attribute
    Then I should receive a PageStatus::Published enum value

  Scenario: Page enforces unique handle per store
    Given a store exists
    And a page exists with handle "about-us" for that store
    When I try to create another page with handle "about-us" for the same store
    Then a unique constraint violation should occur

  Scenario: Same handle allowed for different stores
    Given store A and store B exist
    And a page exists with handle "about-us" for store A
    When I create a page with handle "about-us" for store B
    Then the page should be created successfully

  Scenario: Page factory creates valid page
    Given a store exists
    When I use the Page factory to create a page
    Then the page should be persisted in the database
    And it should have a title, handle, and status

  Scenario: Deleting a store cascades to its pages
    Given a store exists
    And 2 pages exist for that store
    When the store is deleted
    Then all 2 pages should also be deleted
```

### Feature: NavigationMenu Model

```gherkin
Feature: NavigationMenu model
  The NavigationMenu model represents a named menu belonging to a store.

  Scenario: NavigationMenu belongs to a Store
    Given a store exists
    And a navigation menu exists for that store
    When I access the menu's store relationship
    Then I should receive the owning Store model

  Scenario: NavigationMenu has many NavigationItems
    Given a navigation menu exists
    And 5 navigation items exist for that menu
    When I access the menu's items relationship
    Then I should receive a collection of 5 NavigationItem models

  Scenario: NavigationMenu enforces unique handle per store
    Given a store exists
    And a navigation menu exists with handle "main-menu" for that store
    When I try to create another menu with handle "main-menu" for the same store
    Then a unique constraint violation should occur

  Scenario: NavigationMenu factory creates valid menu
    Given a store exists
    When I use the NavigationMenu factory to create a menu
    Then the menu should be persisted in the database
    And it should have a handle and title

  Scenario: Deleting a store cascades to its menus
    Given a store exists
    And a navigation menu exists for that store
    When the store is deleted
    Then the navigation menu should also be deleted
```

### Feature: NavigationItem Model

```gherkin
Feature: NavigationItem model
  The NavigationItem model represents an individual link within a navigation menu.

  Scenario: NavigationItem belongs to a NavigationMenu
    Given a navigation menu exists
    And a navigation item exists for that menu
    When I access the item's menu relationship
    Then I should receive the owning NavigationMenu model

  Scenario: NavigationItem casts type to NavigationItemType enum
    Given a navigation item exists with type "collection"
    When I access the item's type attribute
    Then I should receive a NavigationItemType::Collection enum value

  Scenario: NavigationItem with type "link" uses url directly
    Given a navigation item exists with type "link" and url "https://example.com"
    Then the item's url field should be "https://example.com"
    And the item's resource_id should be null

  Scenario: NavigationItem with type "page" uses resource_id
    Given a page exists with id 5
    And a navigation item exists with type "page" and resource_id 5
    Then the item's resource_id should be 5
    And the item's url should be null

  Scenario: NavigationItem with type "collection" uses resource_id
    Given a collection exists with id 10
    And a navigation item exists with type "collection" and resource_id 10
    Then the item's resource_id should be 10

  Scenario: NavigationItem with type "product" uses resource_id
    Given a product exists with id 7
    And a navigation item exists with type "product" and resource_id 7
    Then the item's resource_id should be 7

  Scenario: NavigationItems are ordered by position
    Given a navigation menu exists
    And navigation items exist at positions 2, 0, 1
    When I query items ordered by position
    Then the items should be returned in order 0, 1, 2

  Scenario: NavigationItem factory creates valid item
    Given a navigation menu exists
    When I use the NavigationItem factory to create an item
    Then the item should be persisted in the database
    And it should have a type, label, and position

  Scenario: Deleting a menu cascades to its items
    Given a navigation menu exists
    And 3 navigation items exist for that menu
    When the menu is deleted
    Then all 3 navigation items should also be deleted
```

### Feature: Enums

```gherkin
Feature: Phase 3 Enums
  Enums for theme status, page status, and navigation item type.

  Scenario: ThemeStatus enum has correct values
    Then the ThemeStatus enum should have a "Draft" case
    And the ThemeStatus enum should have a "Published" case
    And the ThemeStatus enum should have exactly 2 cases

  Scenario: PageStatus enum has correct values
    Then the PageStatus enum should have a "Draft" case
    And the PageStatus enum should have a "Published" case
    And the PageStatus enum should have an "Archived" case
    And the PageStatus enum should have exactly 3 cases

  Scenario: NavigationItemType enum has correct values
    Then the NavigationItemType enum should have a "Link" case
    And the NavigationItemType enum should have a "Page" case
    And the NavigationItemType enum should have a "Collection" case
    And the NavigationItemType enum should have a "Product" case
    And the NavigationItemType enum should have exactly 4 cases
```

### Feature: Seeders

```gherkin
Feature: Phase 3 Seeders
  Seeders populate the database with sample themes, pages, and navigation data.

  Scenario: Theme seeder creates themes with files and settings
    When the theme seeder is run
    Then at least one theme should exist in the database
    And each theme should have associated theme files
    And each theme should have associated theme settings

  Scenario: Page seeder creates pages
    When the page seeder is run
    Then at least one page should exist in the database
    And each page should have a title, handle, and body_html

  Scenario: NavigationMenu seeder creates menus with items
    When the navigation menu seeder is run
    Then at least one navigation menu should exist
    And each menu should have at least one navigation item
    And the seeder should create a "main-menu" and a "footer-menu"
```

---

## Step 3.3: Storefront Blade Layout

### Feature: Base Layout (app.blade.php)

```gherkin
Feature: Storefront base layout
  The master layout provides the page shell with header, footer, cart drawer,
  and dark mode support for all storefront pages.

  # --- Document Head ---

  Scenario: Layout renders correct document head
    Given a published theme exists for the current store
    When I visit any storefront page
    Then the HTML should have charset UTF-8
    And the viewport meta tag should be set to "width=device-width, initial-scale=1"
    And the page title should include the app name
    And Vite-compiled CSS and JS should be included
    And Livewire styles should be injected
    And the HTML element should have a lang attribute from the application locale
    And smooth scroll behavior should be enabled

  Scenario: Layout supports page-specific meta description
    Given a page yields a meta description "About our store"
    When I visit that page
    Then the meta description tag should contain "About our store"

  # --- Skip Link ---

  Scenario: Layout includes a skip-to-content link
    When I visit any storefront page
    Then a visually hidden "Skip to main content" link should be the first element in the body
    And it should become visible on keyboard focus
    And it should link to the main content area

  # --- Announcement Bar ---

  Scenario: Announcement bar renders when enabled in theme settings
    Given theme settings have announcement bar enabled with text "Free shipping over 50 EUR"
    When I visit any storefront page
    Then the announcement bar should be visible above the header
    And it should display the text "Free shipping over 50 EUR"

  Scenario: Announcement bar does not render when disabled
    Given theme settings have announcement bar disabled
    When I visit any storefront page
    Then the announcement bar should not be visible

  Scenario: Announcement bar renders optional link
    Given theme settings have announcement bar enabled with link "/collections/sale"
    When I visit any storefront page
    Then the announcement bar should contain a link to "/collections/sale"

  Scenario: Announcement bar can be dismissed
    Given the announcement bar is visible
    When the user clicks the dismiss (X) button
    Then the announcement bar should be hidden
    And the dismissal should be persisted via localStorage

  # --- Header (Desktop) ---

  Scenario: Desktop header shows logo, navigation, and action icons
    Given the viewport is at the large breakpoint or above
    And a "main-menu" navigation menu exists with items
    When I visit any storefront page
    Then the logo should be visible on the left (image if logo URL set, otherwise store name text)
    And the main navigation should be visible in the center with horizontal links
    And a search icon button should be visible on the right
    And a cart icon button with item count badge should be visible on the right
    And an account icon should be visible on the right

  Scenario: Desktop header navigation supports one level of dropdown submenus
    Given the "main-menu" has an item with child items
    And the viewport is at the large breakpoint or above
    When I hover over or focus on that navigation item
    Then a dropdown submenu should appear with the child items

  # --- Header (Mobile) ---

  Scenario: Mobile header shows hamburger, logo, and cart
    Given the viewport is below the large breakpoint
    When I visit any storefront page
    Then a hamburger menu button should be visible on the left
    And the logo should be centered
    And the cart icon should be visible on the right

  Scenario: Mobile navigation drawer opens on hamburger click
    Given the viewport is below the large breakpoint
    When I click the hamburger menu button
    Then a mobile navigation drawer should slide in from the left
    And a dark overlay should appear behind it
    And focus should be trapped within the drawer

  Scenario: Mobile navigation drawer has correct structure
    Given the mobile navigation drawer is open
    Then it should contain a close button (X) at the top-right
    And navigation items should be displayed as a vertical stack
    And submenus should use a collapsible accordion pattern
    And an account link should be at the bottom of the navigation list
    And it should be labeled "Mobile navigation" via ARIA

  Scenario: Mobile navigation drawer closes on close button or escape
    Given the mobile navigation drawer is open
    When I click the close button
    Then the drawer should close
    When the drawer is open again
    And I press the Escape key
    Then the drawer should close

  # --- Sticky Header ---

  Scenario: Sticky header is enabled via theme settings
    Given theme settings have sticky header enabled
    When I scroll down the page
    Then the header should stick to the top of the viewport
    And it should have a semi-transparent background with backdrop blur
    And a subtle bottom border should appear

  Scenario: Sticky header is not applied when disabled
    Given theme settings have sticky header disabled
    When I scroll down the page
    Then the header should scroll away with the page content

  # --- Main Content Area ---

  Scenario: Main content area has correct structure
    When I visit any storefront page
    Then the main content area should be a landmark element
    And it should have a unique ID for skip-link targeting
    And it should occupy at least the full viewport height

  # --- Footer ---

  Scenario: Footer renders navigation columns from footer-menu
    Given a "footer-menu" navigation menu exists with items
    When I visit any storefront page
    Then the footer should render link columns sourced from the footer-menu
    And each column should have an uppercase heading and a list of links

  Scenario: Footer renders store info column
    Given the store has a name and contact email
    When I visit any storefront page
    Then the footer should display the store name and contact email

  Scenario: Footer renders social links from theme settings
    Given theme settings have social links configured for Facebook and Instagram
    When I visit any storefront page
    Then the footer should display social icon links for Facebook and Instagram
    And each icon link should have a screen reader label

  Scenario: Footer renders copyright and payment icons
    When I visit any storefront page
    Then the footer should display "(c) {year} {store name}. All rights reserved."
    And payment method icons should be visible

  # --- Cart Drawer in Layout ---

  Scenario: Cart drawer is always present in the layout
    When I visit any storefront page
    Then the CartDrawer Livewire component should be rendered in the layout
    And it should listen for the "cart-updated" browser event

  # --- Dark Mode ---

  Scenario: Dark mode follows system preference by default
    Given theme settings have dark mode set to "system"
    When the user's OS is set to dark mode
    Then the storefront should render in dark mode colors

  Scenario: Dark mode toggle when set to "toggle"
    Given theme settings have dark mode set to "toggle"
    When I click the dark mode toggle
    Then the storefront should switch between light and dark mode
    And the preference should be stored in localStorage

  Scenario: Dark mode forced when set to a specific mode
    Given theme settings have dark mode set to "forced dark"
    When I visit any storefront page
    Then the storefront should render in dark mode regardless of system preference
```

### Feature: Storefront Page Templates

```gherkin
Feature: Storefront page templates
  Individual page templates fill the main content section of the base layout.

  Scenario: Home page template exists and renders
    When I visit "/"
    Then the home page template should render within the base layout

  Scenario: Collection page template exists and renders
    Given a published collection with handle "summer" exists
    When I visit "/collections/summer"
    Then the collection page template should render within the base layout

  Scenario: Product page template exists and renders
    Given a published product with handle "t-shirt" exists
    When I visit "/products/t-shirt"
    Then the product page template should render within the base layout

  Scenario: Cart page template exists and renders
    When I visit "/cart"
    Then the cart page template should render within the base layout

  Scenario: Search page template exists and renders
    When I visit "/search?q=test"
    Then the search page template should render within the base layout

  Scenario: CMS page template exists and renders
    Given a published page with handle "about-us" exists
    When I visit "/pages/about-us"
    Then the CMS page template should render within the base layout
```

### Feature: Checkout Templates

```gherkin
Feature: Checkout templates
  Checkout and confirmation pages use the base layout.

  Scenario: Checkout index template exists and renders
    Given a checkout session exists
    When I visit the checkout page
    Then the checkout template should render within the base layout

  Scenario: Checkout confirmation template exists and renders
    Given a completed order exists
    When I visit the confirmation page
    Then the confirmation template should render within the base layout
```

### Feature: Account Templates

```gherkin
Feature: Account templates
  Customer account pages use the base layout with authentication.

  Scenario: Login template exists and renders
    When I visit "/account/login"
    Then the login template should render within the base layout

  Scenario: Register template exists and renders
    When I visit "/account/register"
    Then the register template should render within the base layout

  Scenario: Dashboard template exists and renders
    Given I am logged in as a customer
    When I visit "/account"
    Then the dashboard template should render within the base layout

  Scenario: Order history template exists and renders
    Given I am logged in as a customer
    When I visit "/account/orders"
    Then the order history template should render within the base layout

  Scenario: Order detail template exists and renders
    Given I am logged in as a customer
    And I have an order with number "1042"
    When I visit "/account/orders/1042"
    Then the order detail template should render within the base layout

  Scenario: Address book template exists and renders
    Given I am logged in as a customer
    When I visit "/account/addresses"
    Then the address book template should render within the base layout
```

### Feature: Error Pages

```gherkin
Feature: Error pages
  Custom error pages for 404 and 503 responses.

  Scenario: 404 page renders with correct structure
    When I visit a non-existent URL
    Then a 404 page should be displayed
    And it should show "404" as a large, muted background element
    And it should show "Page not found" as a heading
    And it should show descriptive subtext
    And it should include a search input
    And it should include a "Go to home page" button
    And the layout should be centered vertically and horizontally at full viewport height

  Scenario: 503 maintenance page renders with correct structure
    Given the application is in maintenance mode
    When I visit any storefront page
    Then a 503 page should be displayed
    And it should show the store logo
    And it should show "We'll be back soon" as a heading
    And it should show maintenance subtext
    And the layout should be centered vertically at full viewport height
```

### Feature: Blade Components

```gherkin
Feature: Storefront Blade components
  Reusable UI components for the storefront.

  # --- Product Card Component ---

  Scenario: Product card renders product image with correct aspect ratio
    Given a product exists with media
    When I render the product-card component with that product
    Then the image should have a square (1:1) aspect ratio
    And the image should be lazy-loaded
    And the image should have an alt attribute matching the product title

  Scenario: Product card shows secondary image on hover (desktop)
    Given a product exists with 2 or more images
    And the device supports hover
    When I hover over the product card image
    Then it should crossfade to the second product image

  Scenario: Product card shows placeholder when no image exists
    Given a product exists without media
    When I render the product-card component
    Then a shopping bag placeholder icon should be displayed

  Scenario: Product card shows "Sale" badge when on sale
    Given a product exists where compare_at_amount is greater than price_amount
    When I render the product-card component
    Then a "Sale" badge should be displayed at the top-left of the image

  Scenario: Product card shows "Sold out" badge when out of stock
    Given a product exists where all variants are out of stock with deny policy
    When I render the product-card component
    Then a "Sold out" badge should be displayed at the top-left of the image

  Scenario: Product card shows price with optional compare-at price
    Given a product with price 2499 cents and compare_at_amount 3499 cents
    When I render the product-card component
    Then the current price "24.99 EUR" should be displayed
    And the compare-at price "34.99 EUR" should be displayed with strikethrough styling

  Scenario: Product card shows "Add to cart" for single variant
    Given a product exists with exactly one variant
    When I render the product-card component
    Then "Add to cart" should be the quick-add action

  Scenario: Product card shows "Choose options" for multiple variants
    Given a product exists with multiple variants
    When I render the product-card component
    Then "Choose options" should be the quick-add action

  # --- Price Component ---

  Scenario: Price component formats amount correctly
    When I render the price component with amount 2499 and currency "EUR"
    Then it should display "24.99 EUR"

  Scenario: Price component formats zero amount
    When I render the price component with amount 0 and currency "EUR"
    Then it should display "0.00 EUR"

  Scenario: Price component formats large amount with thousands separator
    When I render the price component with amount 149900 and currency "EUR"
    Then it should display "1,499.00 EUR"

  Scenario: Price component shows compare-at price with strikethrough
    When I render the price component with amount 2499, currency "EUR", and compareAtAmount 3499
    Then the current price "24.99 EUR" should be displayed
    And the compare-at price "34.99 EUR" should be displayed with strikethrough
    And a "Sale" badge should be displayed

  # --- Badge Component ---

  Scenario Outline: Badge component renders with correct variant styling
    When I render the badge component with text "<text>" and variant "<variant>"
    Then the badge should display "<text>"
    And it should use the "<variant>" color scheme

    Examples:
      | text     | variant  |
      | Sale     | sale     |
      | Sold out | sold-out |
      | New      | new      |
      | Default  | default  |

  # --- Quantity Selector Component ---

  Scenario: Quantity selector renders with correct structure
    When I render the quantity-selector component with value 1
    Then a decrease button ("-") should be displayed
    And a numeric input with value 1 should be displayed
    And an increase button ("+") should be displayed
    And the input should be labeled "Quantity"

  Scenario: Quantity selector decrease button is disabled at minimum
    When I render the quantity-selector component with value 1 and min 1
    Then the decrease button should be disabled

  Scenario: Quantity selector increase button is disabled at maximum
    When I render the quantity-selector component with value 5 and max 5
    Then the increase button should be disabled

  Scenario: Compact quantity selector uses smaller buttons
    When I render the quantity-selector component with compact mode
    Then the buttons should be 32x32px instead of 40x40px

  # --- Address Form Component ---

  Scenario: Address form renders all required fields
    When I render the address-form component
    Then it should include inputs for first name, last name, address line 1, address line 2, city, state/province, postal code, country, and phone
    And first name and last name should be side by side
    And address lines should be full width
    And city and state/province should be side by side
    And postal code and country should be side by side

  Scenario: Address form pre-fills with existing data
    Given address data with first name "Jane" and city "Berlin"
    When I render the address-form component with that data
    Then the first name input should contain "Jane"
    And the city input should contain "Berlin"

  # --- Order Summary Component ---

  Scenario: Order summary renders checkout details
    Given a checkout exists with 2 cart lines
    When I render the order-summary component
    Then it should display thumbnails, titles, quantities, and prices for each item
    And it should display subtotal, shipping, tax, and total amounts

  # --- Breadcrumbs Component ---

  Scenario: Breadcrumbs render navigation trail
    Given breadcrumb items: Home (url: "/"), Collections (url: "/collections"), "Summer" (no url)
    When I render the breadcrumbs component
    Then it should display "Home > Collections > Summer"
    And "Home" and "Collections" should be links
    And "Summer" should be the current page (not a link)
    And the component should be wrapped in a nav element labeled "Breadcrumb"

  # --- Pagination Component ---

  Scenario: Pagination renders page navigation
    Given a paginator with 5 pages and current page 3
    When I render the pagination component
    Then previous and next buttons with arrows should be displayed
    And numbered page buttons should be displayed
    And page 3 should be highlighted as current
    And the component should be wrapped in a nav element labeled "Pagination"
```

### Feature: ThemeSettings Service

```gherkin
Feature: ThemeSettings Service
  A singleton service that loads and caches the active theme settings for the current store.

  Scenario: ThemeSettings service is registered as a singleton
    When I resolve the ThemeSettings service from the container
    Then it should be the same instance on subsequent resolutions

  Scenario: ThemeSettings service loads the published theme's settings
    Given a store exists
    And a published theme exists for that store with settings_json containing announcement bar configuration
    When the ThemeSettings service loads settings for that store
    Then it should return the announcement bar configuration

  Scenario: ThemeSettings service caches loaded settings
    Given the ThemeSettings service has loaded settings for a store
    When I request settings for the same store again
    Then it should return the cached result without querying the database again

  Scenario: ThemeSettings service returns defaults when no published theme exists
    Given a store exists with no published theme
    When the ThemeSettings service loads settings for that store
    Then it should return sensible default settings
```

---

## Step 3.4: Storefront Livewire Components

### Feature: Storefront Home Component

```gherkin
Feature: Storefront Home page
  The home page renders configurable sections based on theme settings.

  Scenario: Home page renders sections in configured order
    Given theme settings define section order as: hero, featured_collections, featured_products, newsletter, rich_text
    When I visit the home page
    Then sections should appear in that order

  Scenario: Home page hero banner renders with configured content
    Given theme settings have hero heading "Summer Collection" and subheading "Discover our latest arrivals"
    And theme settings have a hero background image and CTA linking to "/collections/summer"
    When I visit the home page
    Then the hero banner should display the heading "Summer Collection"
    And the subheading "Discover our latest arrivals"
    And a CTA button linking to "/collections/summer"
    And a background image with a semi-transparent dark overlay

  Scenario: Home page hero banner responsive height
    When I visit the home page on a desktop viewport
    Then the hero banner should have a minimum height of 600px
    When I visit on a mobile viewport
    Then the hero banner should have a minimum height of 400px

  Scenario: Featured collections section renders collection cards
    Given theme settings have featured collections enabled with 4 collections
    When I visit the home page
    Then 4 collection cards should be displayed in a grid
    And each card should show the collection image with a 3:4 aspect ratio
    And each card should show the collection title overlaid at the bottom
    And each card should show a "Shop now" link
    And each card should be fully clickable

  Scenario: Featured products section lazy-loads products
    Given theme settings have featured products enabled with 8 products
    When I visit the home page
    Then skeleton placeholders should appear initially
    And then 8 product cards should load and replace the skeletons

  Scenario: Newsletter signup section renders form
    Given theme settings have newsletter section enabled
    When I visit the home page
    Then the newsletter section should display heading "Stay in the loop"
    And subtext "Subscribe for exclusive offers and updates."
    And an email input with placeholder "Enter your email"
    And a "Subscribe" button

  Scenario: Newsletter signup handles successful submission
    Given the newsletter section is visible
    When I enter "user@example.com" and click "Subscribe"
    Then the form should be replaced with "Thanks for subscribing!"

  Scenario: Newsletter signup handles validation errors
    Given the newsletter section is visible
    When I submit the form without entering an email
    Then an inline error should appear below the input

  Scenario: Rich text section renders HTML from theme settings
    Given theme settings have rich text content "<h2>About Us</h2><p>We sell great things.</p>"
    When I visit the home page
    Then the rich text section should render the HTML with prose/typography styling

  Scenario: Home page sections can be individually disabled
    Given theme settings have the newsletter section disabled
    When I visit the home page
    Then the newsletter section should not appear
    And other enabled sections should still render
```

### Feature: Collections Index Component

```gherkin
Feature: Collections Index page
  The collections index page displays all collections in a grid.

  Scenario: Collections index renders all published collections
    Given 6 published collections exist
    When I visit "/collections"
    Then all 6 collections should be displayed as cards in a grid
    And each card should show the collection image and title

  Scenario: Collections index uses responsive grid
    Given collections exist
    When I visit "/collections" on a mobile viewport
    Then the grid should display 2 columns
    When I visit on a desktop viewport
    Then the grid should display 4 columns
```

### Feature: Collections Show Component

```gherkin
Feature: Collection page
  The collection page shows products with filtering, sorting, and pagination.

  # --- Header ---

  Scenario: Collection page shows header with breadcrumbs
    Given a published collection "Summer" exists with a description
    When I visit "/collections/summer"
    Then breadcrumbs should show "Home > Collections > Summer"
    And the H1 should display "Summer"
    And the description should be rendered with prose styling

  # --- Toolbar ---

  Scenario: Collection page toolbar shows product count and sort dropdown
    Given a collection exists with 24 products
    When I visit that collection's page
    Then the toolbar should display "24 products"
    And a sort dropdown should be visible

  Scenario Outline: Collection products can be sorted
    Given a collection exists with products at various prices and dates
    When I select sort option "<option>"
    Then the products should be reordered by "<criteria>"

    Examples:
      | option              | criteria                |
      | Featured            | default sort order      |
      | Price: Low to High  | ascending by price      |
      | Price: High to Low  | descending by price     |
      | Newest              | descending by created   |
      | Best Selling        | descending by sales     |

  # --- Filter Sidebar ---

  Scenario: Filter sidebar is visible on desktop
    Given the viewport is at the large breakpoint
    When I visit a collection page
    Then the filter sidebar should be persistently visible on the left

  Scenario: Filter sidebar is a drawer on mobile
    Given the viewport is below the large breakpoint
    When I click the "Filter" button
    Then a filter drawer should slide in from the left
    And it should contain the same filter groups

  Scenario: Availability filter filters by in-stock products
    Given a collection has both in-stock and out-of-stock products
    When I check the "In stock" availability filter
    Then only in-stock products should be displayed

  Scenario: Price range filter limits products by price
    Given a collection has products priced from 10 to 100 EUR
    When I set the price range min to 20 and max to 50
    Then only products priced between 20 and 50 EUR should be displayed

  Scenario: Product type filter filters by type
    Given a collection has products of types "T-Shirt" and "Hoodie"
    When I select the "T-Shirt" product type filter
    Then only T-Shirt products should be displayed

  Scenario: Vendor filter filters by vendor
    Given a collection has products from vendors "Nike" and "Adidas"
    When I select the "Nike" vendor filter
    Then only Nike products should be displayed

  Scenario: Active filter pills display above the product grid
    Given I have applied a "T-Shirt" type filter
    Then a filter pill "T-Shirt" should appear above the product grid
    And the pill should have a remove (X) button

  Scenario: Clear all filters removes all active filters
    Given I have applied multiple filters
    When I click "Clear all filters"
    Then all filters should be removed
    And all products should be displayed

  # --- Product Grid ---

  Scenario: Product grid uses responsive columns
    Given a collection exists with products
    When I view the collection on a mobile viewport
    Then the product grid should display 2 columns
    When I view on a tablet viewport
    Then the product grid should display 3 columns

  Scenario: Product grid shows loading state during filter updates
    Given I change a filter
    Then the product grid should show a subtle opacity reduction during loading

  # --- Pagination ---

  Scenario: Paginated collection shows pagination controls
    Given a collection has more products than fit on one page
    When I visit the collection page
    Then pagination controls should appear below the product grid
    And the current page should be highlighted

  # --- Empty State ---

  Scenario: Empty state shows when no products match filters
    Given I apply filters that match no products
    Then a "No products found" message should appear
    And guidance text "Try adjusting your filters or browse our full collection." should appear
    And a "Clear filters" button should be available
```

### Feature: Products Show Component

```gherkin
Feature: Product page
  The product page shows product details with variant selection and add-to-cart.

  # --- Layout ---

  Scenario: Product page uses two-column layout on desktop
    Given the viewport is at the large breakpoint or above
    When I visit a product page
    Then the left column should contain the image gallery (sticky)
    And the right column should contain the product info (scrollable)

  Scenario: Product page stacks on mobile
    Given the viewport is below the large breakpoint
    When I visit a product page
    Then images should appear first
    And product info should appear below the images

  # --- Image Gallery ---

  Scenario: Desktop image gallery shows main image and thumbnails
    Given a product has 4 images
    When I visit the product page on desktop
    Then the main image should display at 1:1 aspect ratio
    And a thumbnail strip should show 4 thumbnails below it
    And the active thumbnail should have a colored border

  Scenario: Clicking a thumbnail changes the main image
    Given a product has multiple images
    When I click the second thumbnail
    Then the main image should change to the second image

  Scenario: Mobile image gallery is a horizontal scrollable row
    Given a product has 4 images
    When I visit the product page on mobile
    Then images should be in a horizontally scrollable row with snap-scroll
    And scroll indicator dots should appear below

  Scenario: Variant-specific images display on variant selection
    Given a product variant has specific associated images
    When I select that variant
    Then the gallery should update to show those variant-specific images

  # --- Product Info ---

  Scenario: Product page shows breadcrumbs
    Given a product "T-Shirt" belongs to collection "Summer"
    When I visit the product page
    Then breadcrumbs should show "Home > Summer > T-Shirt"

  Scenario: Product page shows title and price
    Given a product "Premium T-Shirt" with price 2499 cents (EUR)
    When I visit the product page
    Then the H1 should display "Premium T-Shirt"
    And the price should display "24.99 EUR"

  Scenario: Product page shows sale price with compare-at
    Given a product with price 2499 and compare_at_amount 3499 (EUR)
    When I visit the product page
    Then the current price "24.99 EUR" should be bold
    And the compare-at price "34.99 EUR" should have strikethrough styling
    And a "Sale" badge should be visible

  # --- Variant Selection ---

  Scenario: Variant selector renders radio buttons for 6 or fewer values
    Given a product has a "Size" option with values S, M, L, XL
    When I visit the product page
    Then radio-style pill buttons should display for S, M, L, XL

  Scenario: Variant selector renders dropdown for more than 6 values
    Given a product has an option with more than 6 values
    When I visit the product page
    Then a select dropdown should be used for that option

  Scenario: Color option renders as circular swatches
    Given a product has a "Color" option with values "Red" and "Blue"
    When I visit the product page
    Then circular color swatches should be displayed
    And each swatch should show a tooltip with the color name on hover

  Scenario: Unavailable variant option is visually disabled
    Given a product variant combination (Size: XL, Color: Red) is out of stock with deny policy
    When I select Color: Red
    Then the Size: XL option should appear muted with strikethrough

  Scenario: Selecting variant updates price and stock
    Given a product has variants at different prices
    When I select a variant
    Then the price display should update to the selected variant's price
    And the stock messaging should update

  # --- Stock Messaging ---

  Scenario Outline: Stock message displays correctly for variant state
    Given a variant with inventory quantity <quantity> and out_of_stock_policy "<policy>"
    When I select that variant
    Then the stock message should display "<message>" in "<color>" text

    Examples:
      | quantity | policy   | message                    | color |
      | 50       | deny     | In stock                   | green |
      | 5        | deny     | Only 5 left in stock       | amber |
      | 0        | deny     | Out of stock               | red   |
      | 0        | continue | Available on backorder     | blue  |

  # --- Quantity Selector ---

  Scenario: Quantity selector allows changing quantity
    Given a variant is in stock with quantity 10
    When I set the quantity to 3
    Then the quantity input should display 3

  Scenario: Quantity selector respects maximum stock (deny policy)
    Given a variant is in stock with quantity 5 and deny policy
    Then the quantity selector maximum should be 5
    And the increase button should be disabled at quantity 5

  # --- Add to Cart ---

  Scenario: Add to cart button adds item and opens cart drawer
    Given a variant is selected and in stock
    When I click "Add to cart"
    Then the button should show "Adding..." with a spinner
    And the item should be added to the cart
    And a "cart-updated" browser event should be dispatched
    And the cart drawer should open

  Scenario: Add to cart shows "Sold out" when variant is unavailable
    Given the selected variant is out of stock with deny policy
    Then the add-to-cart button should display "Sold out"
    And it should be disabled with a not-allowed cursor

  # --- Product Description and Tags ---

  Scenario: Product description renders as HTML
    Given a product has a description with HTML content
    When I visit the product page
    Then the description should render with prose/typography styling

  Scenario: Product tags render as pills
    Given a product has tags "summer", "cotton", "new"
    When I visit the product page
    Then 3 tag pills should be displayed below the description
```

### Feature: Cart Show Component

```gherkin
Feature: Full cart page
  The cart page shows all items with quantity management and checkout action.

  Scenario: Cart page renders table layout on desktop
    Given the cart has 2 items
    And the viewport is desktop
    When I visit "/cart"
    Then a table should display with columns: Image, Product, Price, Quantity, Total, and remove
    And each item should show its thumbnail, title, variant info, price, quantity selector, and line total

  Scenario: Cart page renders card layout on mobile
    Given the cart has 2 items
    And the viewport is mobile
    When I visit "/cart"
    Then items should be displayed as stacked cards (same as cart drawer items)

  Scenario: Cart page shows totals and checkout button
    Given the cart has items
    When I visit "/cart"
    Then the subtotal should be displayed
    And a discount code input with "Apply" button should be visible
    And a "Checkout" button should be visible
    And a "Continue shopping" link should be visible

  Scenario: Cart page shows empty state when cart is empty
    Given the cart has no items
    When I visit "/cart"
    Then a shopping bag icon should be displayed
    And "Your cart is empty" text should appear
    And a "Continue shopping" button should be available

  Scenario: Updating quantity on cart page recalculates totals
    Given the cart has an item with quantity 1 at 24.99 EUR
    When I increase the quantity to 3
    Then the line total should update to "74.97 EUR"
    And the cart subtotal should update accordingly

  Scenario: Removing an item from the cart page
    Given the cart has 2 items
    When I click the remove button on the first item
    Then only 1 item should remain in the cart
```

### Feature: CartDrawer Component

```gherkin
Feature: Cart drawer
  The cart drawer slides in from the right showing cart contents.

  # --- Trigger and Animation ---

  Scenario: Cart drawer opens on cart-updated event
    When a "cart-updated" browser event is dispatched
    Then the cart drawer should slide in from the right
    And a semi-transparent dark backdrop should appear
    And the body scroll should be locked

  Scenario: Cart drawer opens on cart icon click
    When I click the cart icon in the header
    Then the cart drawer should open

  Scenario: Cart drawer closes on backdrop click
    Given the cart drawer is open
    When I click the backdrop
    Then the cart drawer should close

  Scenario: Cart drawer closes on Escape key
    Given the cart drawer is open
    When I press the Escape key
    Then the cart drawer should close

  Scenario: Cart drawer closes on close button click
    Given the cart drawer is open
    When I click the close (X) button
    Then the cart drawer should close

  # --- Drawer Content ---

  Scenario: Cart drawer shows heading with item count
    Given the cart has 3 items
    When the cart drawer is open
    Then the heading should display "Your Cart (3)"

  Scenario: Cart drawer shows line items with details
    Given the cart has items
    When the cart drawer is open
    Then each item should show a 64x64 thumbnail, title, variant info, quantity stepper, line total, and remove button

  Scenario: Cart drawer quantity update changes line total
    Given the cart drawer is open with an item at quantity 1
    When I increase the quantity to 2
    Then the line total should double
    And the cart totals should update

  Scenario: Cart drawer remove item works
    Given the cart drawer is open with 2 items
    When I click the trash icon on the first item
    Then the item should be removed
    And the item count in the heading should update

  # --- Discount Code ---

  Scenario: Applying a valid discount code in the cart drawer
    Given the cart drawer is open
    When I enter discount code "SUMMER20" and click "Apply"
    Then the discount should be applied
    And the code "SUMMER20" should be displayed with a remove button
    And a discount line should appear in the totals

  Scenario: Applying an invalid discount code shows error
    Given the cart drawer is open
    When I enter an invalid discount code and click "Apply"
    Then an error message should appear (e.g., "Invalid discount code")

  # --- Totals ---

  Scenario: Cart drawer shows totals section
    Given the cart has items
    When the cart drawer is open
    Then the subtotal should be displayed
    And "Shipping and taxes calculated at checkout" should appear below

  # --- Action Buttons ---

  Scenario: Checkout button navigates to checkout
    Given the cart drawer is open with items
    When I click the "Checkout" button
    Then a checkout record should be created
    And I should be navigated to the checkout page

  Scenario: Continue shopping closes the drawer
    Given the cart drawer is open
    When I click "Continue shopping"
    Then the cart drawer should close

  # --- Empty State ---

  Scenario: Cart drawer shows empty state when cart is empty
    Given the cart has no items
    When the cart drawer is open
    Then a shopping bag icon should be centered
    And "Your cart is empty" text should appear
    And a "Continue shopping" button should be available

  # --- Accessibility ---

  Scenario: Cart drawer has correct accessibility attributes
    Given the cart drawer is open
    Then it should have dialog role with modal behavior
    And it should be labeled "Shopping cart"
    And focus should be trapped within the drawer
    And focus should move to the close button on open
```

### Feature: Search Index Component

```gherkin
Feature: Search results page
  The search results page shows filtered, sortable results matching a query.

  Scenario: Search results page shows matching products
    Given products "Blue T-Shirt" and "Blue Hoodie" exist
    When I visit "/search?q=Blue"
    Then the heading should display results count and query
    And both "Blue T-Shirt" and "Blue Hoodie" should appear in the product grid
    And breadcrumbs should show "Home > Search results"

  Scenario: Search results page supports filtering
    Given search results include multiple product types
    When I apply a product type filter
    Then the results should be filtered accordingly

  Scenario: Search results page supports sorting
    Given search results exist
    When I change the sort order
    Then the results should be reordered

  Scenario: Search results page shows empty state for no matches
    When I visit "/search?q=xyznonexistent"
    Then the message should indicate no results found for "xyznonexistent"
    And a search input should be available to try again
```

### Feature: Search Modal Component

```gherkin
Feature: Search modal
  The search modal provides autocomplete search from the header.

  Scenario: Search modal opens on search icon click
    When I click the search icon in the header
    Then a search modal should appear with a dark backdrop
    And the search input should be autofocused

  Scenario: Search modal shows results as user types
    Given I have opened the search modal
    When I type "Blue" in the search input
    Then after a 300ms debounce, results should appear grouped by "Products" and "Collections"
    And product results should show thumbnail, title, and price
    And collection results should show title only
    And a maximum of 5 results per category should be shown

  Scenario: Search modal shows loading state
    Given I have opened the search modal
    When I type a query
    Then skeleton lines should appear while fetching

  Scenario: Search modal shows "no results" message
    Given I have opened the search modal
    When I type a query that matches nothing
    Then "No results for '{query}'" should be displayed

  Scenario: Search modal "View all" link navigates to search page
    Given search results are displayed in the modal
    When I click "View all X results"
    Then I should be navigated to the search results page with the query

  Scenario: Search modal closes on Escape
    Given the search modal is open
    When I press Escape
    Then the modal should close

  Scenario: Search modal keyboard navigation
    Given the search modal has results displayed
    When I press the down arrow key
    Then the first result should be highlighted
    When I press Enter
    Then I should be navigated to that result's page

  Scenario: Search modal accessibility
    Given the search modal is open
    Then it should have dialog role with modal behavior
    And it should be labeled "Search"
    And focus should be trapped within the modal
    And results should be a listbox with option items
```

### Feature: Pages Show Component

```gherkin
Feature: CMS page rendering
  Static CMS pages render their body HTML within the storefront layout.

  Scenario: CMS page renders title and body
    Given a published page exists with title "About Us" and body HTML "<p>We are a great company.</p>"
    When I visit "/pages/about-us"
    Then the H1 should display "About Us"
    And the body should render the HTML with prose/typography styling
    And breadcrumbs should show "Home > About Us"

  Scenario: CMS page uses constrained width by default
    Given a published page exists
    When I visit the page
    Then the body content should be constrained to a comfortable reading width

  Scenario: Visiting a draft page returns 404
    Given a page exists with status "draft"
    When I visit that page's URL
    Then a 404 response should be returned

  Scenario: Visiting an archived page returns 404
    Given a page exists with status "archived"
    When I visit that page's URL
    Then a 404 response should be returned
```

---

## Step 3.5: Navigation Service

### Feature: Navigation Service

```gherkin
Feature: Navigation Service
  The NavigationService builds navigation trees and resolves URLs for navigation items.

  # --- buildTree ---

  Scenario: buildTree returns flat list for menu with no nesting
    Given a navigation menu "main-menu" with 3 items at positions 0, 1, 2
    When I call buildTree with that menu
    Then I should receive an array of 3 items in position order

  Scenario: buildTree returns items in position order
    Given a navigation menu with items at positions 2, 0, 1
    When I call buildTree with that menu
    Then the items should be returned in order: position 0, position 1, position 2

  Scenario: buildTree includes resolved URLs for each item
    Given a navigation menu with items of different types (link, page, collection, product)
    When I call buildTree with that menu
    Then each item in the tree should have a resolved URL

  Scenario: buildTree includes item labels
    Given a navigation menu with items labeled "Home", "Products", "About"
    When I call buildTree with that menu
    Then the tree should contain items with labels "Home", "Products", "About"

  # --- resolveUrl ---

  Scenario: resolveUrl returns url field for link-type items
    Given a navigation item with type "link" and url "https://example.com"
    When I call resolveUrl with that item
    Then the result should be "https://example.com"

  Scenario: resolveUrl generates URL for page-type items
    Given a page exists with handle "about-us"
    And a navigation item with type "page" and resource_id pointing to that page
    When I call resolveUrl with that item
    Then the result should be the URL for "/pages/about-us"

  Scenario: resolveUrl generates URL for collection-type items
    Given a collection exists with handle "summer"
    And a navigation item with type "collection" and resource_id pointing to that collection
    When I call resolveUrl with that item
    Then the result should be the URL for "/collections/summer"

  Scenario: resolveUrl generates URL for product-type items
    Given a product exists with handle "t-shirt"
    And a navigation item with type "product" and resource_id pointing to that product
    When I call resolveUrl with that item
    Then the result should be the URL for "/products/t-shirt"

  Scenario: resolveUrl returns fallback for deleted resource
    Given a navigation item with type "page" and resource_id pointing to a deleted page
    When I call resolveUrl with that item
    Then the result should be a fallback URL (e.g., "#" or "/")

  # --- Caching ---

  Scenario: Navigation data is cached per store
    Given a navigation menu exists for store A
    When I call buildTree for store A's menu
    And I call buildTree for store A's menu again
    Then the second call should use cached data (no additional database queries)

  Scenario: Cache has a 5-minute TTL
    Given navigation data is cached for store A
    When 5 minutes have elapsed
    And I call buildTree for store A's menu
    Then fresh data should be loaded from the database

  Scenario: Cache is keyed per store
    Given store A and store B each have a "main-menu"
    When I call buildTree for store A's main-menu
    And I call buildTree for store B's main-menu
    Then the results should be independent (store B should not receive store A's cached data)
```

---

## Traceability Table

| Spec Reference | Gherkin Feature | Scenarios |
|---|---|---|
| Roadmap 3.1 - create_themes_table | Theme Database Tables | themes table (columns, FK, constraint, indexes) |
| Roadmap 3.1 - create_theme_files_table | Theme Database Tables | theme_files table (columns, FK, indexes) |
| Roadmap 3.1 - create_theme_settings_table | Theme Database Tables | theme_settings table (columns, FK) |
| Roadmap 3.1 - create_pages_table | Theme Database Tables | pages table (columns, FK, constraint, indexes) |
| Roadmap 3.1 - create_navigation_menus_table | Theme Database Tables | navigation_menus table (columns, FK, indexes) |
| Roadmap 3.1 - create_navigation_items_table | Theme Database Tables | navigation_items table (columns, FK, constraint, indexes) |
| Roadmap 3.2 - Theme model | Theme Model | 6 scenarios (relationships, casts, factory, cascade) |
| Roadmap 3.2 - ThemeFile model | ThemeFile Model | 4 scenarios (relationship, unique constraint, factory, cascade) |
| Roadmap 3.2 - ThemeSettings model | ThemeSettings Model | 5 scenarios (relationship, cast, PK, factory, cascade) |
| Roadmap 3.2 - Page model | Page Model | 6 scenarios (relationship, cast, unique, cross-store, factory, cascade) |
| Roadmap 3.2 - NavigationMenu model | NavigationMenu Model | 5 scenarios (relationship, has-many, unique, factory, cascade) |
| Roadmap 3.2 - NavigationItem model | NavigationItem Model | 9 scenarios (relationship, cast, link/page/collection/product types, ordering, factory, cascade) |
| Roadmap 3.2 - Enums | Phase 3 Enums | 3 scenarios (ThemeStatus, PageStatus, NavigationItemType) |
| Roadmap 3.2 - Seeders | Phase 3 Seeders | 3 scenarios (themes, pages, navigation) |
| Roadmap 3.3 - app.blade.php | Storefront Base Layout | 19 scenarios (head, skip link, announcement bar, header desktop/mobile, sticky header, main content, footer, cart drawer, dark mode) |
| Roadmap 3.3 - Page templates | Storefront Page Templates | 6 scenarios (home, collection, product, cart, search, CMS page) |
| Roadmap 3.3 - Checkout templates | Checkout Templates | 2 scenarios (checkout, confirmation) |
| Roadmap 3.3 - Account templates | Account Templates | 6 scenarios (login, register, dashboard, orders, order detail, addresses) |
| Roadmap 3.3 - Error pages | Error Pages | 2 scenarios (404, 503) |
| Roadmap 3.3 - Blade components | Storefront Blade Components | 19 scenarios (product-card, price, badge, quantity-selector, address-form, order-summary, breadcrumbs, pagination) |
| Roadmap 3.3 - ThemeSettings Service | ThemeSettings Service | 4 scenarios (singleton, loads, caches, defaults) |
| Spec 04 Section 3 - Home page | Storefront Home Component | 11 scenarios (section order, hero, collections, products, newsletter, rich text, toggles) |
| Spec 04 Section 4 - Collection page | Collections Show Component | 15 scenarios (header, toolbar, sorting, filters, grid, pagination, empty state) |
| Roadmap 3.4 - Collections\Index | Collections Index Component | 2 scenarios (renders all, responsive grid) |
| Spec 04 Section 5 - Product page | Products Show Component | 17 scenarios (layout, gallery, info, variants, stock, quantity, add-to-cart, description, tags) |
| Spec 04 Section 6 - Cart drawer | CartDrawer Component | 16 scenarios (trigger, close, content, discount, totals, actions, empty, accessibility) |
| Spec 04 Section 7 - Cart page | Cart Show Component | 6 scenarios (desktop table, mobile cards, totals, empty state, quantity update, remove) |
| Spec 04 Section 11.1 - Search modal | Search Modal Component | 7 scenarios (open, results, loading, no results, view all, keyboard, accessibility) |
| Spec 04 Section 11.2 - Search results | Search Index Component | 4 scenarios (results, filter, sort, empty) |
| Spec 04 Section 12 - Content pages | Pages Show Component | 4 scenarios (renders, width, draft 404, archived 404) |
| Roadmap 3.5 - buildTree | Navigation Service | 4 scenarios (flat list, ordering, resolved URLs, labels) |
| Roadmap 3.5 - resolveUrl | Navigation Service | 5 scenarios (link, page, collection, product, deleted resource fallback) |
| Roadmap 3.5 - Caching | Navigation Service | 3 scenarios (cached, TTL, per-store key) |

---

## Self-Assessment

### Coverage Analysis

**Step 3.1 (Migrations):** Full coverage. Every table has scenarios for columns, foreign keys, check constraints, and indexes. All 6 tables covered: themes, theme_files, theme_settings, pages, navigation_menus, navigation_items.

**Step 3.2 (Models):** Full coverage. All 6 models have scenarios for relationships, factories, and cascade deletes. Enums covered with value verification. Seeders covered for data creation. Page and NavigationMenu handle uniqueness constraints verified including cross-store allowance.

**Step 3.3 (Storefront Blade Layout):** Full coverage. The base layout (app.blade.php) is thoroughly covered including document head, skip link, announcement bar (enabled/disabled/link/dismiss), desktop and mobile header, sticky header, main content area, footer (links/info/social/copyright), cart drawer presence, and dark mode (system/toggle/forced). All page templates, checkout templates, account templates, and error pages have existence/rendering scenarios. All 8 Blade components covered (product-card, price, badge, quantity-selector, address-form, order-summary, breadcrumbs, pagination). ThemeSettings service covered for singleton registration, loading, caching, and defaults.

**Step 3.4 (Livewire Components):** Full coverage. All 9 Livewire components specified in the roadmap are covered:
- Home: section ordering, hero, featured collections/products, newsletter (success/error), rich text, section toggling
- Collections/Index: rendering, responsive grid
- Collections/Show: header, toolbar, 5 sort options, 4 filter types, active pills, clear all, grid, pagination, empty state
- Products/Show: layout (desktop/mobile), image gallery (desktop/mobile/variant), breadcrumbs, price/sale, variant selectors (radio/dropdown/color swatches), stock messaging (4 states), quantity, add-to-cart (success/sold-out), description, tags
- Cart/Show: desktop table, mobile cards, totals, empty state, quantity update, remove
- CartDrawer: open/close triggers (event/icon/backdrop/escape/button), heading, line items, quantity, remove, discount (valid/invalid), totals, checkout, continue shopping, empty state, accessibility
- Search/Index: results, filtering, sorting, empty state
- Search/Modal: open, results with debounce, loading, no results, view all, escape, keyboard nav, accessibility
- Pages/Show: rendering, width, draft/archived 404

**Step 3.5 (Navigation Service):** Full coverage. buildTree scenarios cover flat list, ordering, URL resolution, and labels. resolveUrl scenarios cover all 4 item types (link, page, collection, product) plus deleted resource fallback. Caching scenarios cover cache hit, 5-minute TTL, and per-store key isolation.

### Potential Gaps

1. **Navigation nesting depth** - The spec mentions "supports nesting via position" but does not define parent_id. The Gherkin covers ordering by position as specified in the schema. If nesting is added later, additional scenarios would be needed.
2. **Image zoom on hover** - The product page spec mentions optional image zoom (theme setting). This is a visual enhancement that maps to the existing variant-specific image and gallery scenarios but could warrant a dedicated scenario if implemented.
3. **Checkout and account page business logic** - While templates are verified to exist and render, the detailed checkout flow logic (steps, payment, order creation) belongs to Phase 4 and Phase 5 and is intentionally excluded from Phase 3 Gherkin.

### Total Scenario Count

- Step 3.1 (Migrations): 15 scenarios
- Step 3.2 (Models/Enums/Seeders): 38 scenarios
- Step 3.3 (Layout/Templates/Components/Service): 58 scenarios
- Step 3.4 (Livewire Components): 80 scenarios
- Step 3.5 (Navigation Service): 12 scenarios
- **Total: 203 scenarios**
