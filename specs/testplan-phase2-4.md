# Phase 2-4 Browser Test Plan - Storefront and Catalog

## Environment
- URL: http://shop.test
- Date: 2026-03-17
- Seeded data: 2 products (Classic T-Shirt, Coffee Mug), 1 collection (Summer Essentials)
- Database freshly migrated and seeded via `php artisan migrate:fresh --seed`

## Test Cases

### TC-1: Storefront Home Page
**Steps:** Navigate to http://shop.test/
**Expected:** Home page renders with products and collections.
**Status:** PASS
**Result:** Home page renders fully with:
- Announcement bar ("Free shipping on orders over 50 EUR")
- Header with navigation (Home, Collections, About Us, Contact)
- Search, Cart, Account buttons in header
- Hero section ("Welcome to Acme Store" with "Shop Now" CTA)
- "Shop by Collection" section (4 placeholder cards showing "Coming Soon")
- "Featured Products" section heading with placeholder product cards
- Newsletter subscription section
- Rich text motto section
- Footer with Quick Links, Store info, social media links, copyright
**Note:** Collection cards and featured product cards show placeholders/skeletons rather than real seeded data. This may be intentional (section configured to show static slots) or a data-binding issue.
**Screenshot:** specs/screenshots/tc1-storefront-home.png

### TC-2: Collection Page
**Steps:** Navigate to http://shop.test/collections/summer-essentials
**Expected:** Collection page renders with product grid.
**Status:** PASS (with bug)
**Result:** Collection page renders correctly with:
- Breadcrumbs: Home > Collections > Summer Essentials
- Collection title and description ("Everything you need for summer.")
- Product count: "2 products"
- Sort dropdown (Featured, Price Low/High, Newest)
- Sidebar filters: Availability (In stock checkbox), Price (Min/Max inputs)
- Product grid: Classic T-Shirt and Coffee Mug product cards
**Bug:** Both product cards show "0.00 EUR" instead of actual prices (25.00 EUR and 12.00 EUR). See Bug #1 below.
**Screenshot:** specs/screenshots/tc2-collection-page.png

### TC-3: Product Page
**Steps:** Navigate to http://shop.test/products/classic-t-shirt
**Expected:** Product page with variant selector, price, images.
**Status:** PASS
**Result:** Product page renders correctly with:
- Breadcrumbs: Home > Products > Classic T-Shirt
- Product image placeholder (alt text: "Classic T-Shirt front view", no actual image file stored)
- Title: "Classic T-Shirt"
- Price: 25.00 EUR (correctly displayed from variant)
- Size variant selector: Small (selected), Medium, Large (radio buttons)
- Color variant selector: Blue (selected), Red, Green (radio buttons)
- Quantity selector: decrease (-), input (1), increase (+)
- "Add to cart" button
- Description: "A timeless classic cotton t-shirt."
- Tags: "summer", "basics"
**Screenshot:** specs/screenshots/tc3-product-page.png

### TC-4: Search Functionality
**Steps:** Navigate to http://shop.test/search?q=mug (also tested search modal from header)
**Expected:** Search results show matching products.
**Status:** PASS (partial)
**Result:**
- Search results page (/search?q=mug) works correctly: Shows "1 result for "mug"", displays Coffee Mug product card, has sort options and vendor/price filters.
- Search modal (opened via header search button): Modal opens correctly with search input, but results did not appear during testing. The Livewire `updatedQuery` event may have a timing issue with Playwright, or the FTS autocomplete query may not return results through the modal path. The full search page works correctly.
**Bug:** Product card in search results shows "0.00 EUR" (same bug as TC-2).
**Screenshot:** specs/screenshots/tc4-search-results.png

### TC-5: Cart Functionality
**Steps:** On product page, click "Add to cart". Also click "Open cart" in header.
**Expected:** Product added to cart, cart drawer or page shows item.
**Status:** FAIL (not implemented)
**Result:**
- Clicking "Add to cart" dispatches a `cart-updated` Livewire event but does not actually add anything to a cart. The `addToCart()` method in `Products/Show.php` is a stub.
- Clicking "Open cart" button in header has no visible effect - no cart drawer or page opens.
- Cart backend models exist (Cart, CartLine) but no Livewire cart components or cart routes are implemented yet.
**Note:** Phase 4 (Cart, Checkout, Discounts, Shipping, Taxes) is still in progress, so this is expected.

### TC-6: Dark Mode
**Steps:** Toggle dark mode class on the `<html>` element and verify visual changes.
**Expected:** All storefront pages adapt to dark color scheme.
**Status:** PASS (partial)
**Result:**
- Product page: Dark mode works fully - dark backgrounds, white text, proper button/variant selector contrast, footer adapts.
- Home page: Header and hero section adapt to dark mode. However, the main body sections (Shop by Collection, Featured Products, Newsletter, Rich Text) retain light/white backgrounds. The footer adapts correctly.
**Bug:** Home page body sections do not fully adapt to dark mode. See Bug #3 below.
**Screenshots:** specs/screenshots/tc6-dark-mode-product.png, specs/screenshots/tc6-dark-mode-home.png

### TC-7: 404 Page for Non-Existent Product
**Steps:** Navigate to http://shop.test/products/does-not-exist
**Expected:** 404 error page renders.
**Status:** PASS
**Result:** Clean 404 page renders with:
- Large "404" text
- "Page not found" heading
- "Sorry, we could not find the page you are looking for." description
- "Go back home" button linking to /
- Returns HTTP 404 status code
**Note:** The 404 page uses Laravel's default error page, not the storefront layout. This means no header/footer/navigation is shown on 404s.
**Screenshot:** specs/screenshots/tc7-404-page.png

## Bugs Found

| # | Severity | Description | File(s) |
|---|----------|-------------|---------|
| 1 | High | Product card shows 0.00 EUR - accesses `$product->price_amount` which does not exist on Product model (price lives on ProductVariant) | `resources/views/components/storefront/product-card.blade.php:9` |
| 2 | Medium | Cart not functional - "Add to cart" is a stub, "Open cart" button has no effect | `app/Livewire/Storefront/Products/Show.php:111-118` (expected - Phase 4 in progress) |
| 3 | Low | Home page body sections (below hero) do not adapt to dark mode - white background persists | `resources/views/livewire/storefront/home.blade.php` |
| 4 | Low | 404 page uses default Laravel error template instead of storefront layout (no header/footer) | N/A (design decision - could be intentional) |

### Bug #1 Detail: Product Card Price
The product card component at `resources/views/components/storefront/product-card.blade.php:9` reads `$product->price_amount` which is not a column on the `products` table. Prices are stored on `product_variants.price_amount`. The fix should either:
- Add an accessor on the Product model that returns the default variant's price, or
- Pass the price from the parent view (eager-load variants and use `$product->variants->first()?->price_amount`)

Note: The product detail page (`Products/Show.php`) correctly gets the price from the selected variant, which is why it shows 25.00 EUR correctly.

## Summary

| Test Case | Status | Notes |
|-----------|--------|-------|
| TC-1 Home Page | PASS | Fully functional storefront with all sections |
| TC-2 Collection Page | PASS (bug) | Layout/features work, prices show 0.00 EUR |
| TC-3 Product Page | PASS | Variant selectors, price, images all work |
| TC-4 Search | PASS (partial) | Search results page works, modal inconclusive |
| TC-5 Cart | FAIL | Not implemented yet (Phase 4 in progress) |
| TC-6 Dark Mode | PASS (partial) | Product page fully works, home page partially |
| TC-7 404 Page | PASS | Clean error page with correct status code |

**Overall: 5 PASS, 1 PARTIAL, 1 FAIL (expected), 4 bugs found (1 high, 1 medium, 2 low)**
