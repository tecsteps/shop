# Phase 6: Customer Accounts - Gherkin Specifications

## Overview

Phase 6 covers customer authentication (login, registration, logout) and customer account pages (dashboard, order history, order detail, address book CRUD). The customer auth guard, CustomerUserProvider, and per-store email uniqueness were configured in Phase 1. This phase builds the Livewire UI components and the CustomerService.

---

## Feature 1: Customer Registration

Source: Spec 06 S1.2, Spec 04 S10.2, Spec 05 S12.1, Roadmap Step 6.1-6.2

```gherkin
Feature: Customer Registration
  As a guest visitor
  I want to create a customer account
  So that I can track orders and save addresses

  Background:
    Given a store "Acme" exists with domain "acme.test"

  Scenario: Successful registration with valid data
    Given I am on the registration page "/account/register"
    When I fill in "Name" with "Jane Smith"
    And I fill in "Email" with "jane@example.com"
    And I fill in "Password" with "password123"
    And I fill in "Confirm password" with "password123"
    And I click "Create account"
    Then I should be redirected to "/account"
    And I should see "My Account"
    And I should be authenticated as customer "jane@example.com"
    And a customer record should exist with email "jane@example.com" for store "Acme"

  Scenario: Registration with marketing opt-in
    Given I am on the registration page "/account/register"
    When I fill in "Name" with "Jane Smith"
    And I fill in "Email" with "jane@example.com"
    And I fill in "Password" with "password123"
    And I fill in "Confirm password" with "password123"
    And I check "Subscribe to marketing emails"
    And I click "Create account"
    Then I should be redirected to "/account"
    And the customer "jane@example.com" should have marketing_opt_in enabled

  Scenario: Registration fails with duplicate email in the same store
    Given a customer "customer@acme.test" already exists in store "Acme"
    And I am on the registration page "/account/register"
    When I fill in "Name" with "Duplicate Customer"
    And I fill in "Email" with "customer@acme.test"
    And I fill in "Password" with "password123"
    And I fill in "Confirm password" with "password123"
    And I click "Create account"
    Then I should see "already been taken"
    And I should remain on the registration page

  Scenario: Registration succeeds with same email in a different store
    Given a customer "shared@example.com" exists in store "OtherStore"
    And I am on the registration page "/account/register" for store "Acme"
    When I fill in "Name" with "Same Email"
    And I fill in "Email" with "shared@example.com"
    And I fill in "Password" with "password123"
    And I fill in "Confirm password" with "password123"
    And I click "Create account"
    Then I should be redirected to "/account"
    And a customer "shared@example.com" should exist in both stores

  Scenario: Registration fails with mismatched passwords
    Given I am on the registration page "/account/register"
    When I fill in "Name" with "Test Customer"
    And I fill in "Email" with "mismatch@example.com"
    And I fill in "Password" with "password123"
    And I fill in "Confirm password" with "different456"
    And I click "Create account"
    Then I should see a validation error referencing "password"
    And I should remain on the registration page

  Scenario: Registration fails with password shorter than 8 characters
    Given I am on the registration page "/account/register"
    When I fill in "Name" with "Test Customer"
    And I fill in "Email" with "short@example.com"
    And I fill in "Password" with "short"
    And I fill in "Confirm password" with "short"
    And I click "Create account"
    Then I should see a validation error referencing "password"

  Scenario: Registration fails with missing required fields
    Given I am on the registration page "/account/register"
    When I click "Create account" without filling in any fields
    Then I should see validation errors for "name", "email", and "password"

  Scenario: Registration page shows link to login
    Given I am on the registration page "/account/register"
    Then I should see "Already have an account?"
    And I should see a "Log in" link pointing to "/account/login"
```

---

## Feature 2: Customer Login

Source: Spec 06 S1.2, Spec 04 S10.1, Spec 05 S12.2, Roadmap Step 6.1-6.2

```gherkin
Feature: Customer Login
  As a registered customer
  I want to log in to my account
  So that I can access my orders and addresses

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And a customer exists with email "customer@acme.test" and password "password" in store "Acme"

  Scenario: Successful login with valid credentials
    Given I am on the login page "/account/login"
    When I fill in "Email" with "customer@acme.test"
    And I fill in "Password" with "password"
    And I click "Log in"
    Then I should be redirected to "/account"
    And I should see "My Account"
    And I should be authenticated via the "customer" guard

  Scenario: Login fails with invalid password
    Given I am on the login page "/account/login"
    When I fill in "Email" with "customer@acme.test"
    And I fill in "Password" with "wrongpassword"
    And I click "Log in"
    Then I should see "Invalid credentials"
    And I should remain on the login page
    And I should not be authenticated

  Scenario: Login fails with non-existent email
    Given I am on the login page "/account/login"
    When I fill in "Email" with "nobody@acme.test"
    And I fill in "Password" with "password"
    And I click "Log in"
    Then I should see "Invalid credentials"
    And I should not be authenticated

  Scenario: Login is scoped to the current store
    Given a customer "shared@example.com" exists in store "OtherStore" with password "otherpass"
    And no customer "shared@example.com" exists in store "Acme"
    And I am on the login page "/account/login" for store "Acme"
    When I fill in "Email" with "shared@example.com"
    And I fill in "Password" with "otherpass"
    And I click "Log in"
    Then I should see "Invalid credentials"
    And I should not be authenticated

  Scenario: Login rate limiting after 5 failed attempts
    Given I am on the login page "/account/login"
    When I attempt login with invalid credentials 5 times
    And I attempt login a 6th time
    Then I should see a rate-limiting error
    And I should not be authenticated

  Scenario: Login redirects to intended URL after auth redirect
    Given I am not authenticated
    When I navigate to "/account/orders"
    Then I should be redirected to "/account/login"
    When I fill in "Email" with "customer@acme.test"
    And I fill in "Password" with "password"
    And I click "Log in"
    Then I should be redirected to "/account/orders"

  Scenario: Login page shows link to registration
    Given I am on the login page "/account/login"
    Then I should see "Don't have an account?"
    And I should see a "Create one" link pointing to "/account/register"

  Scenario: Login page shows forgot password link
    Given I am on the login page "/account/login"
    Then I should see a "Forgot password?" link
```

---

## Feature 3: Customer Logout

Source: Spec 04 S10.3, Roadmap Step 6.2

```gherkin
Feature: Customer Logout
  As an authenticated customer
  I want to log out
  So that my session is terminated securely

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test" in store "Acme"

  Scenario: Successful logout from the dashboard
    Given I am on the account dashboard "/account"
    When I click "Log out"
    Then my customer session should be invalidated
    And the CSRF token should be regenerated
    And I should be redirected to "/account/login"

  Scenario: Accessing protected pages after logout
    Given I have logged out
    When I navigate to "/account"
    Then I should be redirected to "/account/login"

  Scenario: Logout is a POST action (not a GET link)
    Then the "Log out" action should submit a POST request to "/account/logout"
```

---

## Feature 4: Unauthenticated Access Protection

Source: Spec 06 S5 (CustomerAuthenticate middleware), Spec 04 S10, Roadmap Step 6.1

```gherkin
Feature: Unauthenticated Access Protection
  As the system
  I want to redirect unauthenticated visitors away from account pages
  So that customer data is protected

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am not authenticated

  Scenario: Unauthenticated access to dashboard redirects to login
    When I navigate to "/account"
    Then I should be redirected to "/account/login"

  Scenario: Unauthenticated access to orders redirects to login
    When I navigate to "/account/orders"
    Then I should be redirected to "/account/login"

  Scenario: Unauthenticated access to order detail redirects to login
    When I navigate to "/account/orders/1001"
    Then I should be redirected to "/account/login"

  Scenario: Unauthenticated access to addresses redirects to login
    When I navigate to "/account/addresses"
    Then I should be redirected to "/account/login"

  Scenario: Login and register pages are accessible without auth
    When I navigate to "/account/login"
    Then I should see the login form
    When I navigate to "/account/register"
    Then I should see the registration form

  Scenario: Intended URL is preserved after redirect
    When I navigate to "/account/addresses"
    Then I should be redirected to "/account/login"
    And the intended URL "/account/addresses" should be stored in the session
```

---

## Feature 5: Account Dashboard

Source: Spec 04 S10.3, Roadmap Step 6.2

```gherkin
Feature: Account Dashboard
  As an authenticated customer
  I want to see an overview of my account
  So that I can quickly navigate to orders, addresses, or log out

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "John Doe" with email "customer@acme.test"

  Scenario: Dashboard displays welcome message with customer name
    When I navigate to "/account"
    Then I should see "Welcome back, John"
    And the page title should contain "My Account"

  Scenario: Dashboard displays quick-link cards
    When I navigate to "/account"
    Then I should see an "Order history" card linking to "/account/orders"
    And I should see an "Addresses" card linking to "/account/addresses"
    And I should see a "Log out" card that submits a logout form

  Scenario: Dashboard displays recent orders table
    Given the customer has the following orders:
      | order_number | created_at  | status | total_amount |
      | 1042         | 2026-01-15  | paid   | 9870         |
      | 1041         | 2026-01-10  | paid   | 4500         |
    When I navigate to "/account"
    Then I should see a "Recent Orders" section
    And I should see order "#1042" with status badge and total
    And I should see order "#1041" with status badge and total
    And each order row should have a "View" link

  Scenario: Dashboard shows at most 5 recent orders
    Given the customer has 8 orders
    When I navigate to "/account"
    Then I should see exactly 5 orders in the recent orders table

  Scenario: Dashboard with no orders
    Given the customer has no orders
    When I navigate to "/account"
    Then I should see the quick-link cards
    And the recent orders section should indicate no orders exist

  Scenario: Renders customer dashboard (Pest: CustomerAccountTest)
    Given I am acting as the customer
    When I send a GET request to "/account"
    Then the response status should be 200
    And I should see the customer's name in the response
```

---

## Feature 6: Order History

Source: Spec 04 S10.4, Roadmap Step 6.2

```gherkin
Feature: Order History
  As an authenticated customer
  I want to see a paginated list of my orders
  So that I can review my purchase history

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"

  Scenario: Order history lists all customer orders
    Given the customer has orders "#1001", "#1002", and "#1004"
    When I navigate to "/account/orders"
    Then I should see "Order History"
    And I should see order "#1001"
    And I should see order "#1002"
    And I should see order "#1004"

  Scenario: Order history displays correct columns
    Given the customer has at least one order
    When I navigate to "/account/orders"
    Then I should see columns for "Order", "Date", "Status", "Total", and "Action"
    And each order row should have a "View" link

  Scenario: Status badges use correct colors
    Given the customer has orders with statuses:
      | order_number | status     |
      | 2001         | pending    |
      | 2002         | paid       |
      | 2003         | fulfilled  |
      | 2004         | cancelled  |
      | 2005         | refunded   |
    When I navigate to "/account/orders"
    Then order "#2001" should have a yellow status badge
    And order "#2002" should have a green status badge
    And order "#2003" should have a blue status badge
    And order "#2004" should have a gray status badge
    And order "#2005" should have a red status badge

  Scenario: Order history is paginated
    Given the customer has 25 orders
    When I navigate to "/account/orders"
    Then I should see a pagination component
    And the first page should show the most recent orders

  Scenario: Customer can only see their own orders (Pest: CustomerAccountTest)
    Given customer A is logged in
    And customer B has order "#9999"
    When customer A navigates to "/account/orders/9999"
    Then the response status should be 404

  Scenario: Lists customer orders (Pest: CustomerAccountTest)
    Given the customer has 3 orders
    When I send a GET request to "/account/orders"
    Then I should see all 3 order numbers in the response
```

---

## Feature 7: Order Detail

Source: Spec 04 S10.5, Roadmap Step 6.2

```gherkin
Feature: Order Detail
  As an authenticated customer
  I want to view the details of a specific order
  So that I can review items, shipping, payment, and fulfillment status

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"

  Scenario: Order detail shows breadcrumbs
    Given the customer has order "#1001"
    When I navigate to "/account/orders/1001"
    Then I should see breadcrumbs "Account > Orders > #1001"

  Scenario: Order detail shows header with order number and status
    Given the customer has order "#1001" with financial_status "paid"
    When I navigate to "/account/orders/1001"
    Then I should see "Order #1001"
    And I should see a status badge for "paid"
    And I should see the order placement date

  Scenario: Order detail shows line items
    Given the customer has order "#1001" with the following items:
      | product_title       | variant_info | quantity | line_total |
      | Classic Cotton Tee  | Size: M      | 2        | 4998       |
    When I navigate to "/account/orders/1001"
    Then I should see "Classic Cotton Tee"
    And I should see "Size: M"
    And I should see quantity "2"
    And I should see the formatted line total

  Scenario: Order detail shows address and payment info
    Given the customer has order "#1001" with shipping and billing addresses
    When I navigate to "/account/orders/1001"
    Then I should see the shipping address
    And I should see the billing address or "Same as shipping"
    And I should see the payment method information

  Scenario: Order detail shows price breakdown
    Given the customer has order "#1001" with:
      | subtotal | shipping | tax  | discount | total |
      | 8497     | 799      | 574  | 0        | 9870  |
    When I navigate to "/account/orders/1001"
    Then I should see "Subtotal"
    And I should see "Shipping"
    And I should see "Tax"
    And I should see "Total"

  Scenario: Order detail shows fulfillment tracking when available
    Given the customer has order "#1001" with fulfillment:
      | carrier | tracking_number       | tracking_url                    |
      | UPS     | 1Z999AA10123456784    | https://ups.com/track/1Z999AA10123456784 |
    When I navigate to "/account/orders/1001"
    Then I should see "Shipped via UPS"
    And I should see tracking number "1Z999AA10123456784"
    And I should see a "Track shipment" link opening in a new tab

  Scenario: Order detail hides fulfillment section when no fulfillments
    Given the customer has order "#1001" with no fulfillments
    When I navigate to "/account/orders/1001"
    Then I should not see a fulfillment section

  Scenario: Shows order detail (Pest: CustomerAccountTest)
    Given the customer has order "#1001"
    When I send a GET request to "/account/orders/1001"
    Then I should see order items and totals in the response
```

---

## Feature 8: Address Book - Viewing

Source: Spec 04 S10.6, Roadmap Step 6.2

```gherkin
Feature: Address Book Viewing
  As an authenticated customer
  I want to view my saved addresses
  So that I can manage my address book

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"

  Scenario: Address book lists all saved addresses
    Given the customer has the following addresses:
      | label   | first_name | last_name | address1     | city      | zip   | country | is_default |
      | Home    | Jane       | Doe       | 123 Main St  | New York  | 10001 | US      | true       |
      | Work    | Jane       | Doe       | 456 Oak Ave  | Chicago   | 60601 | US      | false      |
    When I navigate to "/account/addresses"
    Then I should see "Your Addresses"
    And I should see the address "123 Main St"
    And I should see the address "456 Oak Ave"

  Scenario: Default address is visually distinguished
    Given the customer has a default address "123 Main St"
    When I navigate to "/account/addresses"
    Then the "123 Main St" card should have a "Default" badge
    And the "123 Main St" card should have a primary-colored border

  Scenario: Non-default addresses show "Set as default" action
    Given the customer has a non-default address "456 Oak Ave"
    When I navigate to "/account/addresses"
    Then the "456 Oak Ave" card should show "Edit", "Delete", and "Set as default" actions

  Scenario: Default address does not show "Set as default" action
    Given the customer has a default address "123 Main St"
    When I navigate to "/account/addresses"
    Then the "123 Main St" card should show "Edit" and "Delete" actions
    And the "123 Main St" card should not show "Set as default"

  Scenario: Address book with no addresses
    Given the customer has no saved addresses
    When I navigate to "/account/addresses"
    Then I should see "Your Addresses"
    And I should see an "Add new address" button

  Scenario: Address book shows "Add new address" button
    When I navigate to "/account/addresses"
    Then I should see an "Add new address" button

  Scenario: Lists saved addresses (Pest: AddressManagementTest)
    Given the customer has 2 addresses
    When I send a GET request to "/account/addresses"
    Then I should see both addresses in the response
```

---

## Feature 9: Address Book - Creating

Source: Spec 04 S10.6, Spec 01 (customer_addresses schema), Roadmap Step 6.2

```gherkin
Feature: Address Book Creating
  As an authenticated customer
  I want to add new addresses to my address book
  So that I can save addresses for future orders

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"
    And I am on the address book page "/account/addresses"

  Scenario: Successfully add a new address via modal
    When I click "Add new address"
    Then a modal with an address form should open
    When I fill in "First name" with "John"
    And I fill in "Last name" with "Doe"
    And I fill in "Address" with "New Street 42"
    And I fill in "City" with "Hamburg"
    And I fill in "Postal code" with "20095"
    And I fill in "Country" with "DE"
    And I click "Save"
    Then I should see "Address saved"
    And I should see "New Street 42" in the address list
    And I should see "Hamburg" in the address list
    And a customer_address record should be saved in the database

  Scenario: Add address with all optional fields
    When I click "Add new address"
    And I fill in all address fields including company, address2, province, and phone
    And I click "Save"
    Then the address should be saved with all fields

  Scenario: First address becomes default automatically
    Given the customer has no saved addresses
    When I add a new address
    Then the new address should be marked as default

  Scenario: Validation fails for missing required fields
    When I click "Add new address"
    And I click "Save" without filling in required fields
    Then I should see validation errors for required address fields

  Scenario: Creates a new address (Pest: AddressManagementTest)
    When I submit a new address form with valid data
    Then the address should be saved in the database
```

---

## Feature 10: Address Book - Editing

Source: Spec 04 S10.6, Roadmap Step 6.2

```gherkin
Feature: Address Book Editing
  As an authenticated customer
  I want to edit my saved addresses
  So that I can keep my address book up to date

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"
    And the customer has a saved address with city "New York"

  Scenario: Edit an existing address via modal
    Given I am on the address book page "/account/addresses"
    When I click "Edit" on the "New York" address
    Then a modal with the address form should open pre-filled
    When I change "City" to "Frankfurt"
    And I click "Save"
    Then I should see "Address saved"
    And I should see "Frankfurt" in the address list
    And I should not see "New York" in the address list

  Scenario: Updates an existing address (Pest: AddressManagementTest)
    When I change the city on an existing address
    Then the updated city should be persisted in the database
```

---

## Feature 11: Address Book - Deleting

Source: Spec 04 S10.6, Roadmap Step 6.2

```gherkin
Feature: Address Book Deleting
  As an authenticated customer
  I want to delete addresses from my address book
  So that I can remove outdated addresses

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"

  Scenario: Delete an address with confirmation
    Given the customer has an address "456 Oak Ave"
    And I am on the address book page "/account/addresses"
    When I click "Delete" on the "456 Oak Ave" address
    Then a confirmation dialog should appear
    When I confirm the deletion
    Then the address "456 Oak Ave" should be removed from the list
    And the address should be removed from the database

  Scenario: Cancel address deletion
    Given the customer has an address "456 Oak Ave"
    And I am on the address book page "/account/addresses"
    When I click "Delete" on the "456 Oak Ave" address
    And I cancel the confirmation dialog
    Then the address "456 Oak Ave" should still be visible

  Scenario: Deletes an address (Pest: AddressManagementTest)
    When I delete an existing address
    Then the address should be removed from the database
```

---

## Feature 12: Address Book - Setting Default

Source: Spec 04 S10.6, Roadmap Step 6.2

```gherkin
Feature: Address Book Setting Default
  As an authenticated customer
  I want to set a default address
  So that it is pre-selected during checkout

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "customer@acme.test"

  Scenario: Set a non-default address as default
    Given the customer has address "Home" marked as default
    And the customer has address "Work" not marked as default
    And I am on the address book page "/account/addresses"
    When I click "Set as default" on the "Work" address
    Then "Work" should be marked as default
    And "Home" should no longer be marked as default
    And only one address should have is_default=1

  Scenario: Sets a default address (Pest: AddressManagementTest)
    When I mark an address as default
    Then is_default should be 1 for that address
    And is_default should be 0 for all other addresses
```

---

## Feature 13: Address Authorization

Source: Spec 05 S12.3, Spec 06 S4 (CustomerPolicy), Roadmap Step 6.2

```gherkin
Feature: Address Authorization
  As the system
  I want to prevent customers from managing other customers' addresses
  So that address data is isolated per customer

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And customer A "customerA@acme.test" exists
    And customer B "customerB@acme.test" exists

  Scenario: Customer cannot edit another customer's address
    Given customer A is logged in
    And customer B has an address with id 99
    When customer A attempts to edit address 99
    Then the response should be 403 or 404

  Scenario: Customer cannot delete another customer's address
    Given customer A is logged in
    And customer B has an address with id 99
    When customer A attempts to delete address 99
    Then the response should be 403 or 404

  Scenario: Prevents managing another customer's addresses (Pest: AddressManagementTest)
    Given customer A is logged in
    When customer A attempts to edit customer B's address
    Then the response should be 403 or 404

  Scenario: Validates required address fields (Pest: AddressManagementTest)
    When I submit an address form with missing address1
    Then I should see a validation error for address1
```

---

## Feature 14: Customer Profile Update

Source: Spec 09 Roadmap (CustomerAccountTest), Spec 05 S12.1

```gherkin
Feature: Customer Profile Update
  As an authenticated customer
  I want to update my profile information
  So that my account details stay current

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And I am logged in as customer "John Doe" with email "customer@acme.test"

  Scenario: Updates customer profile (Pest: CustomerAccountTest)
    When I update my name to "Jane Doe" and enable marketing_opt_in
    Then the name "Jane Doe" should be persisted in the database
    And marketing_opt_in should be enabled in the database
```

---

## Feature 15: Guest Cart Merge on Login

Source: Spec 05 S5.1 (Cart merge on login)

```gherkin
Feature: Guest Cart Merge on Login
  As a guest visitor with items in my cart
  I want my cart to be preserved when I log in
  So that I do not lose my shopping progress

  Background:
    Given a store "Acme" exists with domain "acme.test"
    And a customer "customer@acme.test" exists in store "Acme"

  Scenario: Guest cart merges with existing customer cart on login
    Given I have a guest cart with variant A (quantity 2)
    And the customer has an existing cart with variant A (quantity 1) and variant B (quantity 3)
    When I log in as "customer@acme.test"
    Then the customer cart should contain variant A with quantity 2 (the higher quantity)
    And the customer cart should contain variant B with quantity 3
    And the guest cart should be marked as abandoned

  Scenario: Guest cart becomes the customer cart when no existing cart
    Given I have a guest cart with variant A (quantity 1)
    And the customer has no existing cart
    When I log in as "customer@acme.test"
    Then the customer cart should contain variant A with quantity 1
```

---

# Traceability Matrix

| Gherkin Scenario | Spec Source | Pest Test (Feature) | E2E Test (Browser) |
|---|---|---|---|
| F1: Successful registration | Spec 06 S1.2, Spec 04 S10.2, Spec 05 S12.1 | - | Test 10.1 |
| F1: Registration with marketing opt-in | Spec 04 S10.2, Spec 01 (customers.marketing_opt_in) | - | - |
| F1: Duplicate email same store | Spec 06 S1.2, Spec 01 (idx_customers_store_email) | - | Test 10.2 |
| F1: Same email different store | Spec 06 S1.2 (store scoping) | - | - |
| F1: Mismatched passwords | Spec 06 S7 (password validation) | - | Test 10.3 |
| F1: Short password | Spec 06 S7 (min 8 chars) | - | - |
| F1: Missing required fields | Spec 06 S7 (RegisterCustomerRequest) | - | - |
| F1: Link to login | Spec 04 S10.2 | - | - |
| F2: Successful login | Spec 06 S1.2, Spec 04 S10.1 | - | Test 10.4 |
| F2: Invalid password | Spec 06 S1.2 | - | Test 10.5 |
| F2: Non-existent email | Spec 06 S1.2 | - | - |
| F2: Store-scoped login | Spec 06 S1.2 (CustomerUserProvider) | - | - |
| F2: Rate limiting | Spec 06 S1.2 (5 attempts/minute) | - | - |
| F2: Intended URL redirect | Spec 06 S5 (CustomerAuthenticate) | CustomerAccountTest: redirects unauthenticated requests | Test 10.6 |
| F2: Link to registration | Spec 04 S10.1 | - | - |
| F2: Forgot password link | Spec 04 S10.1 | - | - |
| F3: Successful logout | Spec 04 S10.3, Roadmap Step 6.2 | - | Test 10.12 |
| F3: After logout redirect | Spec 06 S5 | - | - |
| F3: POST action | Roadmap Step 6.2 | - | - |
| F4: Redirect dashboard | Spec 06 S5 (CustomerAuthenticate) | CustomerAccountTest: redirects unauthenticated | Test 10.6 |
| F4: Redirect orders | Spec 06 S5 | CustomerAccountTest: redirects unauthenticated | - |
| F4: Redirect order detail | Spec 06 S5 | - | - |
| F4: Redirect addresses | Spec 06 S5 | - | - |
| F4: Public login/register | Spec 02 (auth routes) | - | - |
| F4: Intended URL preserved | Spec 06 S5 | - | - |
| F5: Dashboard welcome | Spec 04 S10.3 | CustomerAccountTest: renders dashboard | - |
| F5: Quick-link cards | Spec 04 S10.3 | - | - |
| F5: Recent orders table | Spec 04 S10.3 (last 5 orders) | - | - |
| F5: Max 5 recent orders | Spec 04 S10.3 | - | - |
| F5: No orders | Spec 04 S10.3 | - | - |
| F5: Pest dashboard render | Spec 09 (CustomerAccountTest) | CustomerAccountTest: renders dashboard | - |
| F6: Lists orders | Spec 04 S10.4 | CustomerAccountTest: lists orders | Test 10.7 |
| F6: Correct columns | Spec 04 S10.4 | - | - |
| F6: Status badge colors | Spec 04 S10.4 | - | - |
| F6: Pagination | Spec 04 S10.4 | - | - |
| F6: Cannot see other's orders | Spec 05 S12.3 | CustomerAccountTest: prevents accessing another's orders | - |
| F6: Pest lists orders | Spec 09 (CustomerAccountTest) | CustomerAccountTest: lists orders | - |
| F7: Breadcrumbs | Spec 04 S10.5 | - | - |
| F7: Header with status | Spec 04 S10.5 | - | Test 10.8 |
| F7: Line items | Spec 04 S10.5 | CustomerAccountTest: shows order detail | Test 10.8 |
| F7: Addresses and payment | Spec 04 S10.5 | - | - |
| F7: Price breakdown | Spec 04 S10.5 | CustomerAccountTest: shows order detail | Test 10.8 |
| F7: Fulfillment tracking | Spec 04 S10.5 | - | - |
| F7: No fulfillment section | Spec 04 S10.5 | - | - |
| F7: Pest order detail | Spec 09 (CustomerAccountTest) | CustomerAccountTest: shows order detail | - |
| F8: Lists addresses | Spec 04 S10.6 | AddressManagementTest: lists saved addresses | Test 10.9 |
| F8: Default badge | Spec 04 S10.6 | - | - |
| F8: Set as default action | Spec 04 S10.6 | - | - |
| F8: No set-default on default | Spec 04 S10.6 | - | - |
| F8: No addresses | Spec 04 S10.6 | - | - |
| F8: Add button | Spec 04 S10.6 | - | - |
| F8: Pest lists addresses | Spec 09 (AddressManagementTest) | AddressManagementTest: lists addresses | - |
| F9: Add address via modal | Spec 04 S10.6 | AddressManagementTest: creates address | Test 10.10 |
| F9: All optional fields | Spec 01 (address_json schema) | - | - |
| F9: First address default | Spec 04 S10.6 | - | - |
| F9: Validation fails | Spec 06 S7 | AddressManagementTest: validates required fields | - |
| F9: Pest creates address | Spec 09 (AddressManagementTest) | AddressManagementTest: creates address | - |
| F10: Edit address via modal | Spec 04 S10.6 | AddressManagementTest: updates address | Test 10.11 |
| F10: Pest updates address | Spec 09 (AddressManagementTest) | AddressManagementTest: updates address | - |
| F11: Delete with confirmation | Spec 04 S10.6 | AddressManagementTest: deletes address | - |
| F11: Cancel deletion | Spec 04 S10.6 | - | - |
| F11: Pest deletes address | Spec 09 (AddressManagementTest) | AddressManagementTest: deletes address | - |
| F12: Set as default | Spec 04 S10.6 | AddressManagementTest: sets default | - |
| F12: Pest sets default | Spec 09 (AddressManagementTest) | AddressManagementTest: sets default address | - |
| F13: Cannot edit other's address | Spec 05 S12.3, Spec 06 S4 | AddressManagementTest: prevents managing another's | - |
| F13: Cannot delete other's address | Spec 05 S12.3 | - | - |
| F13: Pest authorization | Spec 09 (AddressManagementTest) | AddressManagementTest: prevents managing another's | - |
| F13: Pest validation | Spec 09 (AddressManagementTest) | AddressManagementTest: validates required fields | - |
| F14: Profile update | Spec 09 (CustomerAccountTest) | CustomerAccountTest: updates profile | - |
| F15: Cart merge on login | Spec 05 S5.1 | - | - |

---

# Self-Assessment

## Coverage Analysis

- **Spec 09 (Implementation Roadmap) Steps 6.1-6.2:** Fully covered. All Livewire components and routes are represented.
- **Spec 04 (Storefront UI) Section 10:** All six subsections (Login, Register, Dashboard, Order History, Order Detail, Address Book) are covered with UI-level Gherkin scenarios.
- **Spec 06 (Auth & Security) Section 1.2:** Customer auth guard, store-scoped login, registration, rate limiting, and password reset link all covered.
- **Spec 05 (Business Logic) Section 12:** Registration fields, store isolation, data access rules, and guest-to-customer linking covered.
- **Spec 01 (Database Schema):** customers and customer_addresses table constraints (unique email per store, address_json structure, is_default) reflected in scenarios.
- **Spec 02 (API Routes):** All customer account routes covered.

## Pest Test Mapping

- **CustomerAccountTest (6 tests):** All 6 tests mapped to Gherkin scenarios (Features 5, 6, 7, 14, and Feature 4).
- **AddressManagementTest (7 tests):** All 7 tests mapped to Gherkin scenarios (Features 8-13).

## E2E Test Mapping

- **Browser Suite 10 (12 tests):** All 12 tests (10.1-10.12) mapped to corresponding Gherkin scenarios.

## Gaps and Notes

- **Password reset flow:** Spec 06 mentions customer password reset (separate broker, `customer_password_reset_tokens` table). The Gherkin specs reference the forgot-password link but do not fully specify the reset flow, as it is outside the scope of Phase 6 steps 6.1-6.2 in the roadmap.
- **Guest cart merge:** Included as Feature 15 since it is triggered by customer login (Spec 05 S5.1). This may already be implemented in the CartService from Phase 4; the Gherkin scenarios ensure the behavior is specified.
- **Mobile responsive layout:** Spec 04 mentions mobile card layout for orders and responsive grid for addresses. These are UI implementation details reflected in the spec references but not broken into separate Gherkin scenarios since they are presentational.
