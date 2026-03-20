# Phase 1: Foundation - Gherkin Specifications

> Acceptance criteria for Steps 1.1 through 1.8 of the Implementation Roadmap, expressed as Gherkin scenarios.

---

## Feature: Environment and Configuration (Step 1.1)

```gherkin
Feature: Environment and Configuration
  The application must be configured with SQLite, file-based cache and sessions,
  synchronous queue, log-based mail, and a custom customer auth guard.

  Scenario: SQLite database is configured and functional
    Given the application environment is loaded
    When the database connection is resolved
    Then the default connection should be "sqlite"
    And the database file should be at "database/database.sqlite"
    And WAL mode should be enabled
    And foreign keys should be enabled
    And busy_timeout should be set to 5000
    And synchronous mode should be set to "normal"

  Scenario: File-based cache is configured
    Given the application environment is loaded
    When the default cache store is resolved
    Then the cache driver should be "file"

  Scenario: File-based sessions are configured
    Given the application environment is loaded
    When the session configuration is resolved
    Then the session driver should be "file"
    And the session lifetime should be 120 minutes

  Scenario: Synchronous queue is configured
    Given the application environment is loaded
    When the queue configuration is resolved
    Then the default queue connection should be "sync"

  Scenario: Log-based mail is configured
    Given the application environment is loaded
    When the mail configuration is resolved
    Then the default mailer should be "log"

  Scenario: Customer auth guard is configured
    Given the application environment is loaded
    When the auth configuration is resolved
    Then a guard named "customer" should exist with driver "session" and provider "customers"
    And a provider named "customers" should exist pointing to the Customer model
    And a password broker named "customers" should exist using the "customer_password_reset_tokens" table

  Scenario: Structured JSON logging channel is configured
    Given the application environment is loaded
    When the logging configuration is resolved
    Then a structured JSON logging channel should be available

  Scenario: Local filesystem is configured for media storage
    Given the application environment is loaded
    When the filesystem configuration is resolved
    Then the default disk should be "local"
    And a "public" disk should be configured for local storage
```

---

## Feature: Core Migrations (Step 1.2)

```gherkin
Feature: Core Migrations
  The database must contain all foundation tables with correct columns,
  constraints, indexes, and foreign keys.

  Scenario: Organizations table exists with correct schema
    Given migrations have been run
    When I inspect the "organizations" table
    Then it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "name" text column
    And it should have a non-nullable "billing_email" text column
    And it should have nullable "created_at" and "updated_at" text columns
    And it should have an index on "billing_email"

  Scenario: Stores table exists with correct schema
    Given migrations have been run
    When I inspect the "stores" table
    Then it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "organization_id" foreign key referencing "organizations(id)" with ON DELETE CASCADE
    And it should have a non-nullable "name" text column
    And it should have a non-nullable "handle" text column with a UNIQUE index
    And it should have a non-nullable "status" text column defaulting to "active" with a CHECK constraint for "active" and "suspended"
    And it should have a non-nullable "default_currency" text column defaulting to "USD"
    And it should have a non-nullable "default_locale" text column defaulting to "en"
    And it should have a non-nullable "timezone" text column defaulting to "UTC"
    And it should have nullable "created_at" and "updated_at" text columns
    And it should have indexes on "organization_id" and "status"

  Scenario: Store domains table exists with correct schema
    Given migrations have been run
    When I inspect the "store_domains" table
    Then it should have an auto-incrementing "id" primary key
    And it should have a non-nullable "store_id" foreign key referencing "stores(id)" with ON DELETE CASCADE
    And it should have a non-nullable "hostname" text column with a UNIQUE index
    And it should have a non-nullable "type" text column defaulting to "storefront" with a CHECK constraint for "storefront", "admin", and "api"
    And it should have a non-nullable "is_primary" integer column defaulting to 0
    And it should have a non-nullable "tls_mode" text column defaulting to "managed" with a CHECK constraint for "managed" and "bring_your_own"
    And it should have a nullable "created_at" text column
    And it should have an index on "store_id"
    And it should have a composite index on "store_id" and "is_primary"

  Scenario: Users table is modified with additional columns
    Given migrations have been run
    When I inspect the "users" table
    Then it should have a "status" text column defaulting to "active" with a CHECK constraint for "active" and "disabled"
    And it should have a nullable "last_login_at" text column
    And it should have nullable "two_factor_secret", "two_factor_recovery_codes", and "two_factor_confirmed_at" columns
    And it should have a "password_hash" column that maps to Laravel's password field
    And it should have an index on "status"

  Scenario: Store users pivot table exists with composite primary key
    Given migrations have been run
    When I inspect the "store_users" table
    Then it should have a composite primary key on "store_id" and "user_id"
    And "store_id" should be a foreign key referencing "stores(id)" with ON DELETE CASCADE
    And "user_id" should be a foreign key referencing "users(id)" with ON DELETE CASCADE
    And it should have a non-nullable "role" text column defaulting to "staff" with a CHECK constraint for "owner", "admin", "staff", and "support"
    And it should have a nullable "created_at" text column
    And it should have an index on "user_id"
    And it should have a composite index on "store_id" and "role"

  Scenario: Store settings table exists with store_id as primary key
    Given migrations have been run
    When I inspect the "store_settings" table
    Then it should have "store_id" as the primary key (not auto-incrementing)
    And "store_id" should be a foreign key referencing "stores(id)" with ON DELETE CASCADE
    And it should have a non-nullable "settings_json" text column defaulting to "{}"
    And it should have a nullable "updated_at" text column

  Scenario: Monetary amounts are stored as integers in minor units
    Given migrations have been run
    When I inspect any column ending with "_amount"
    Then it should be of type INTEGER

  Scenario: Enum columns use text with CHECK constraints
    Given migrations have been run
    When I inspect columns that represent enums (status, role, type, tls_mode)
    Then each should be of type TEXT with an appropriate CHECK constraint

  Scenario: Foreign key cascading deletes work correctly
    Given an organization with a store exists
    When I delete the organization
    Then the store should be deleted
    And any store_domains for that store should be deleted
    And any store_users for that store should be deleted
    And any store_settings for that store should be deleted
```

---

## Feature: Core Models (Step 1.3)

```gherkin
Feature: Core Models
  All foundation models must have proper relationships, fillable/guarded arrays,
  casts, factories, and seeders.

  # Organization Model

  Scenario: Organization has many stores
    Given an organization exists
    When I create two stores for the organization
    Then the organization's "stores" relationship should return both stores

  Scenario: Organization factory creates valid records
    Given I use the Organization factory
    When I create an organization
    Then it should have a non-empty "name"
    And it should have a valid "billing_email"

  # Store Model

  Scenario: Store belongs to an organization
    Given a store exists with an organization
    When I access the store's "organization" relationship
    Then it should return the parent organization

  Scenario: Store has many store domains
    Given a store exists
    When I create two domains for the store
    Then the store's "domains" relationship should return both domains

  Scenario: Store belongs to many users through store_users
    Given a store exists
    And two users are assigned to the store with roles
    When I access the store's "users" relationship
    Then it should return both users with their pivot role attribute

  Scenario: Store has one store settings record
    Given a store exists with settings
    When I access the store's "settings" relationship
    Then it should return the StoreSettings record

  Scenario: Store factory creates valid records with all defaults
    Given I use the Store factory
    When I create a store
    Then it should have a non-empty "name"
    And it should have a unique "handle"
    And its "status" should be "active"
    And its "default_currency" should be "USD"
    And its "default_locale" should be "en"
    And its "timezone" should be "UTC"

  Scenario: Store model casts status to StoreStatus enum
    Given a store exists with status "active"
    When I access the store's "status" attribute
    Then it should be an instance of the StoreStatus enum

  # StoreDomain Model

  Scenario: StoreDomain belongs to a store
    Given a store domain exists
    When I access the domain's "store" relationship
    Then it should return the parent store

  Scenario: StoreDomain factory creates valid records
    Given I use the StoreDomain factory
    When I create a store domain
    Then it should have a non-empty "hostname"
    And its "type" should be one of "storefront", "admin", or "api"

  Scenario: StoreDomain model casts type to StoreDomainType enum
    Given a store domain with type "storefront" exists
    When I access the domain's "type" attribute
    Then it should be an instance of the StoreDomainType enum

  # StoreUser Pivot Model

  Scenario: StoreUser is a custom Pivot class with role attribute
    Given a user is assigned to a store with role "admin"
    When I access the user's stores relationship
    Then the pivot should be an instance of StoreUser
    And the pivot's "role" should be the StoreUserRole Admin enum value

  # StoreSettings Model

  Scenario: StoreSettings belongs to a store
    Given store settings exist for a store
    When I access the settings' "store" relationship
    Then it should return the parent store

  Scenario: StoreSettings casts settings_json to array
    Given store settings exist with JSON data
    When I access the "settings_json" attribute
    Then it should be a PHP array

  Scenario: StoreSettings factory creates valid records
    Given I use the StoreSettings factory
    When I create store settings
    Then it should have a valid "settings_json" value

  # User Model

  Scenario: User belongs to many stores through store_users
    Given a user is assigned to two different stores
    When I access the user's "stores" relationship
    Then it should return both stores with their pivot role attribute

  # Seeders

  Scenario: Running seeders creates sample foundation data
    Given the database is freshly migrated
    When I run the database seeders
    Then at least one organization should exist
    And at least one store should exist
    And at least one store domain should exist
    And at least one user should be linked to a store
    And at least one store settings record should exist

  # Explicit return types and guarded/fillable

  Scenario: All models have explicit return type declarations
    Given I inspect the source code of Organization, Store, StoreDomain, StoreUser, and StoreSettings models
    Then every method should have an explicit return type declaration

  Scenario: All models have proper fillable or guarded arrays
    Given I inspect the source code of Organization, Store, StoreDomain, and StoreSettings models
    Then each model should have either a $fillable or $guarded array defined
```

---

## Feature: Enums (Step 1.4)

```gherkin
Feature: Enums
  All foundation enums must exist as backed string enums with the correct cases.

  Scenario: StoreStatus enum has the correct cases
    Given the StoreStatus enum exists at "app/Enums/StoreStatus.php"
    Then it should be a backed string enum
    And it should have a case "Active" with value "active"
    And it should have a case "Suspended" with value "suspended"

  Scenario: StoreUserRole enum has the correct cases
    Given the StoreUserRole enum exists at "app/Enums/StoreUserRole.php"
    Then it should be a backed string enum
    And it should have a case "Owner" with value "owner"
    And it should have a case "Admin" with value "admin"
    And it should have a case "Staff" with value "staff"
    And it should have a case "Support" with value "support"

  Scenario: StoreDomainType enum has the correct cases
    Given the StoreDomainType enum exists at "app/Enums/StoreDomainType.php"
    Then it should be a backed string enum
    And it should have a case "Storefront" with value "storefront"
    And it should have a case "Admin" with value "admin"
    And it should have a case "Api" with value "api"
```

---

## Feature: Tenant Resolution Middleware (Step 1.5)

```gherkin
Feature: Tenant Resolution Middleware
  The ResolveStore middleware resolves the current store from the request hostname
  (storefront) or session (admin) and binds it to the service container.

  # Storefront hostname resolution

  Scenario: Storefront request with a known hostname resolves the store
    Given a store "Acme Fashion" exists with domain "acme-fashion.test" of type "storefront"
    When a storefront request is made with hostname "acme-fashion.test"
    Then the store "Acme Fashion" should be bound in the container as "current_store"
    And the store should be shared with all Blade views as "currentStore"

  Scenario: Storefront request with an unknown hostname returns 404
    Given no store domain exists for hostname "unknown-shop.test"
    When a storefront request is made with hostname "unknown-shop.test"
    Then the response should be HTTP 404

  Scenario: Storefront request for a suspended store returns 503
    Given a store exists with domain "suspended-shop.test" and status "suspended"
    When a storefront request is made with hostname "suspended-shop.test"
    Then the response should be HTTP 503
    And the response should contain "This store is currently unavailable"

  Scenario: Hostname-to-store mapping is cached for 5 minutes
    Given a store exists with domain "cached-shop.test"
    When a storefront request is made with hostname "cached-shop.test"
    Then the hostname-to-store_id mapping should be cached
    And subsequent lookups for "cached-shop.test" should use the cache

  # Admin session-based resolution

  Scenario: Admin request resolves store from session
    Given a user is authenticated and has "current_store_id" set in session to store "Acme Fashion"
    And the user has a store_users record for "Acme Fashion"
    When an admin request is made
    Then the store "Acme Fashion" should be bound in the container as "current_store"
    And the store should be shared with all Blade views as "currentStore"

  Scenario: Admin request without store_users record returns 403
    Given a user is authenticated and has "current_store_id" set in session
    But the user has no store_users record for that store
    When an admin request is made
    Then the response should be HTTP 403

  # Registration

  Scenario: ResolveStore is registered in the storefront middleware group
    Given the application middleware configuration in bootstrap/app.php
    Then a "storefront" middleware group should exist containing ResolveStore

  Scenario: ResolveStore is registered in the admin middleware group
    Given the application middleware configuration in bootstrap/app.php
    Then an "admin" middleware group should exist containing ResolveStore

  # Middleware alias registration

  Scenario: Custom middleware aliases are registered in bootstrap/app.php
    Given the application middleware configuration in bootstrap/app.php
    Then the alias "store.resolve" should map to App\Http\Middleware\ResolveStore
    And the alias "role.check" should map to App\Http\Middleware\CheckStoreRole
    And the alias "auth:customer" should map to App\Http\Middleware\CustomerAuthenticate
```

---

## Feature: CustomerAuthenticate Middleware (Step 1.5 / 1.7)

```gherkin
Feature: CustomerAuthenticate Middleware
  The CustomerAuthenticate middleware ensures that only authenticated customers
  can access protected storefront account pages.

  Scenario: Unauthenticated customer is redirected to login
    Given a store is the current store
    And no customer is authenticated via the "customer" guard
    When the customer requests a protected account page
    Then the customer should be redirected to "/account/login"

  Scenario: Intended URL is saved for redirect after login
    Given a store is the current store
    And no customer is authenticated via the "customer" guard
    When the customer requests "/account/orders"
    Then the intended URL "/account/orders" should be saved in the session
    And the customer should be redirected to "/account/login"

  Scenario: Authenticated customer passes through the middleware
    Given a store is the current store
    And a customer is authenticated via the "customer" guard
    When the customer requests a protected account page
    Then the request should proceed to the controller
```

---

## Feature: BelongsToStore Trait and Global Scope (Step 1.6)

```gherkin
Feature: BelongsToStore Trait and Global Scope
  The BelongsToStore trait automatically scopes queries to the current store
  and auto-sets store_id when creating new records.

  Scenario: StoreScope filters queries by current store
    Given a store "Store A" is bound as the current store
    And a product exists for "Store A"
    And a product exists for "Store B"
    When I query all products using the model (with StoreScope applied)
    Then only the product for "Store A" should be returned

  Scenario: BelongsToStore auto-sets store_id on creating
    Given a store "Store A" is bound as the current store
    When I create a new model that uses BelongsToStore without explicitly setting store_id
    Then the model's store_id should be automatically set to Store A's id

  Scenario: StoreScope does not interfere when no current store is bound
    Given no store is bound in the container
    When I query a model that uses BelongsToStore without a global scope
    Then the query should not filter by store_id

  Scenario: BelongsToStore trait is applied to all tenant-scoped models
    Given the following models use the BelongsToStore trait:
      | Model              |
      | Product            |
      | Collection         |
      | Customer           |
      | Order              |
      | Cart               |
      | Checkout           |
      | Discount           |
      | ShippingZone       |
      | Theme              |
      | Page               |
      | NavigationMenu     |
      | AnalyticsEvent     |
      | AnalyticsDaily     |
      | WebhookSubscription|
      | InventoryItem      |
      | SearchQuery        |
    Then each model should have StoreScope applied automatically
    And each model should auto-set store_id on creating
```

---

## Feature: Admin Authentication (Step 1.7)

```gherkin
Feature: Admin Authentication
  Admin users authenticate via standard Laravel session auth using the web guard,
  with rate limiting and password reset support.

  # Admin Login

  Scenario: Admin login with valid credentials succeeds
    Given an admin user exists with email "admin@example.com" and password "password123"
    When the user submits the login form with email "admin@example.com" and password "password123"
    Then the user should be authenticated via the "web" guard
    And the session should be regenerated
    And the user should be redirected to "/admin"

  Scenario: Admin login with invalid credentials fails
    Given an admin user exists with email "admin@example.com"
    When the user submits the login form with email "admin@example.com" and password "wrong-password"
    Then the user should not be authenticated
    And the flash message should say "Invalid credentials"
    And the message should not reveal whether the email or password was wrong

  Scenario: Admin login with non-existent email fails with generic message
    Given no user exists with email "nobody@example.com"
    When the user submits the login form with email "nobody@example.com" and password "anything"
    Then the user should not be authenticated
    And the flash message should say "Invalid credentials"

  Scenario: Admin login is rate-limited to 5 attempts per minute per IP
    Given an admin user exists
    When 5 failed login attempts are made within one minute from the same IP
    Then the 6th attempt should be rejected with a "Too many attempts" message
    And the message should include the number of seconds until the next attempt is allowed

  Scenario: Admin login with "Remember me" sets a long-lived token
    Given an admin user exists with email "admin@example.com" and password "password123"
    When the user submits the login form with remember me checked
    Then the user should be authenticated
    And a remember token cookie should be set

  # Admin Logout

  Scenario: Admin logout invalidates the session
    Given an admin user is authenticated
    When the user performs the logout action via POST /admin/logout
    Then the entire session should be invalidated
    And the CSRF token should be regenerated
    And the user should be redirected to "/admin/login"

  # Admin Password Reset

  Scenario: Requesting a password reset sends an email for existing users
    Given an admin user exists with email "admin@example.com"
    When a password reset is requested for "admin@example.com"
    Then a reset token should be stored in the "password_reset_tokens" table
    And a reset email should be sent (logged via log mailer)
    And the response message should be generic: "If that email exists, we sent a reset link."

  Scenario: Requesting a password reset for non-existent email gives the same generic response
    Given no user exists with email "nobody@example.com"
    When a password reset is requested for "nobody@example.com"
    Then no reset email should be sent
    And the response message should be generic: "If that email exists, we sent a reset link."

  Scenario: Password reset with valid token succeeds
    Given an admin user has a valid password reset token
    When the user submits a new password with the valid token
    Then the user's password should be updated
    And the token should be deleted from "password_reset_tokens"
    And the user should be redirected to "/admin/login" with a success flash

  Scenario: Password reset with expired token fails
    Given an admin user has a password reset token that is older than 60 minutes
    When the user submits a new password with the expired token
    Then the password should not be updated
    And an error message should be shown

  Scenario: Password reset email is throttled to one per 60 seconds
    Given an admin user exists with email "admin@example.com"
    And a password reset was requested less than 60 seconds ago for that email
    When another password reset is requested for "admin@example.com"
    Then the request should be throttled
    And a message should indicate to wait before requesting again

  # Rate Limiter Registration

  Scenario: Login rate limiter is registered in AppServiceProvider
    Given the application is booted
    Then a rate limiter named "login" should be registered
    And it should allow a maximum of 5 attempts per minute per IP
```

---

## Feature: Customer Authentication (Step 1.7)

```gherkin
Feature: Customer Authentication
  Customers authenticate via the custom "customer" guard with store-scoped email
  uniqueness. The CustomerUserProvider injects store_id into credential queries.

  # Customer Guard and Provider

  Scenario: Customer guard uses the customer provider
    Given the auth configuration is loaded
    Then the "customer" guard should use the "session" driver
    And the "customer" guard should use the "customers" provider
    And the "customers" provider should reference the Customer model

  Scenario: CustomerUserProvider scopes credential queries by store_id
    Given a store "Store A" is the current store
    And a customer exists with email "customer@example.com" in "Store A"
    And a customer exists with email "customer@example.com" in "Store B"
    When the CustomerUserProvider retrieves credentials for "customer@example.com"
    Then only the customer from "Store A" should be returned

  # Customer Login

  Scenario: Customer login with valid credentials succeeds
    Given a store "Acme" is the current store
    And a customer exists in "Acme" with email "buyer@example.com" and password "secret123"
    When the customer submits the login form with email "buyer@example.com" and password "secret123"
    Then the customer should be authenticated via the "customer" guard
    And the session should be regenerated
    And the customer should be redirected to "/account"

  Scenario: Customer login redirects to intended URL if present
    Given a store is the current store
    And a customer exists with valid credentials
    And the customer was redirected to login from "/account/orders"
    When the customer logs in successfully
    Then the customer should be redirected to "/account/orders"

  Scenario: Customer login with invalid credentials fails with generic message
    Given a store is the current store
    And a customer exists in the store
    When the customer submits incorrect credentials
    Then the customer should not be authenticated
    And the flash message should say "Invalid credentials"

  Scenario: Customer login is rate-limited to 5 attempts per minute per IP
    Given a store is the current store
    When 5 failed customer login attempts are made within one minute from the same IP
    Then the 6th attempt should be rejected with a "Too many attempts" message

  # Customer Registration

  Scenario: Customer registration with valid data succeeds
    Given a store "Acme" is the current store
    When a visitor submits the registration form with:
      | field                 | value              |
      | name                  | Jane Doe           |
      | email                 | jane@example.com   |
      | password              | securepass1        |
      | password_confirmation | securepass1        |
    Then a customer record should be created in "Acme" with email "jane@example.com"
    And the customer should be auto-logged in via the "customer" guard
    And the customer should be redirected to "/account"

  Scenario: Customer registration requires name, email, password, and password_confirmation
    Given a store is the current store
    When a visitor submits the registration form with missing required fields
    Then validation errors should be returned for the missing fields

  Scenario: Customer registration enforces minimum password length of 8
    Given a store is the current store
    When a visitor submits the registration form with a password shorter than 8 characters
    Then a validation error should be returned for the password field

  Scenario: Customer registration enforces password confirmation match
    Given a store is the current store
    When a visitor submits the registration form with mismatched password and confirmation
    Then a validation error should be returned for the password field

  Scenario: Customer email must be unique per store
    Given a store "Acme" is the current store
    And a customer already exists in "Acme" with email "existing@example.com"
    When a visitor tries to register with email "existing@example.com" in "Acme"
    Then a validation error should indicate the email is already taken

  Scenario: Same email can register in different stores
    Given a customer exists in "Store A" with email "shared@example.com"
    And "Store B" is the current store
    When a visitor registers with email "shared@example.com" in "Store B"
    Then the registration should succeed
    And a separate customer record should exist in "Store B"

  Scenario: Customer registration supports optional marketing_opt_in
    Given a store is the current store
    When a visitor submits the registration form with marketing_opt_in set to true
    Then the customer's marketing_opt_in should be true

  Scenario: Customer registration defaults marketing_opt_in to false
    Given a store is the current store
    When a visitor submits the registration form without marketing_opt_in
    Then the customer's marketing_opt_in should be false

  # Customer Password Reset

  Scenario: Customer password reset uses a separate broker and token table
    Given a store is the current store
    And a customer exists in the store
    When a customer requests a password reset
    Then the token should be stored in the "customer_password_reset_tokens" table
    And the token record should include the store_id

  Scenario: Customer password reset tokens expire after 60 minutes
    Given a customer has a password reset token older than 60 minutes
    When the customer attempts to reset using the expired token
    Then the reset should fail

  Scenario: Customer password reset email is throttled to one per 60 seconds
    Given a store is the current store
    And a customer has already requested a reset less than 60 seconds ago
    When another reset is requested
    Then the request should be throttled

  Scenario: Customer forgot-password form gives a generic response
    Given a store is the current store
    When a password reset is requested for any email address
    Then the response should not reveal whether the email exists in the store
```

---

## Feature: Authorization - Role Checking Trait (Step 1.8)

```gherkin
Feature: Role Checking Trait
  The ChecksStoreRole trait provides reusable role-checking helper methods
  used by all policies and gate definitions.

  Scenario: getStoreRole returns the user's role for a store
    Given a user has the role "admin" in store 1
    When I call getStoreRole for the user and store 1
    Then it should return StoreUserRole::Admin

  Scenario: getStoreRole returns null when user has no role in the store
    Given a user has no role in store 1
    When I call getStoreRole for the user and store 1
    Then it should return null

  Scenario: hasRole returns true when the user's role matches one in the list
    Given a user has the role "staff" in store 1
    When I call hasRole with roles [Owner, Admin, Staff]
    Then it should return true

  Scenario: hasRole returns false when the user's role does not match any in the list
    Given a user has the role "support" in store 1
    When I call hasRole with roles [Owner, Admin, Staff]
    Then it should return false

  Scenario: hasRole returns false when the user has no role in the store
    Given a user has no role in store 1
    When I call hasRole with roles [Owner]
    Then it should return false

  Scenario: isOwnerOrAdmin returns true for Owner
    Given a user has the role "owner" in store 1
    When I call isOwnerOrAdmin
    Then it should return true

  Scenario: isOwnerOrAdmin returns true for Admin
    Given a user has the role "admin" in store 1
    When I call isOwnerOrAdmin
    Then it should return true

  Scenario: isOwnerOrAdmin returns false for Staff
    Given a user has the role "staff" in store 1
    When I call isOwnerOrAdmin
    Then it should return false

  Scenario: isOwnerAdminOrStaff returns true for Staff
    Given a user has the role "staff" in store 1
    When I call isOwnerAdminOrStaff
    Then it should return true

  Scenario: isOwnerAdminOrStaff returns false for Support
    Given a user has the role "support" in store 1
    When I call isOwnerAdminOrStaff
    Then it should return false

  Scenario: isAnyRole returns true for any valid role
    Given a user has the role "support" in store 1
    When I call isAnyRole
    Then it should return true

  Scenario: isAnyRole returns false when user has no role
    Given a user has no role in store 1
    When I call isAnyRole
    Then it should return false

  Scenario: User.roleForStore returns the role for a given store
    Given a user has the role "admin" in a specific store
    When I call user.roleForStore(store)
    Then it should return StoreUserRole::Admin

  Scenario: User.roleForStore returns null when user has no role
    Given a user has no assignment to a specific store
    When I call user.roleForStore(store)
    Then it should return null

  Scenario: A user can have different roles in different stores
    Given a user has the role "owner" in store 1
    And the same user has the role "staff" in store 2
    When I call roleForStore for store 1
    Then it should return StoreUserRole::Owner
    When I call roleForStore for store 2
    Then it should return StoreUserRole::Staff
```

---

## Feature: Authorization - ProductPolicy (Step 1.8)

```gherkin
Feature: ProductPolicy Authorization
  The ProductPolicy authorizes product operations based on the user's store role.

  Scenario Outline: viewAny - any role can list products
    Given a user has the role "<role>" in the current store
    When authorization is checked for "viewAny" on Product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | granted |

  Scenario: viewAny - user with no store role is denied
    Given a user has no role in the current store
    When authorization is checked for "viewAny" on Product
    Then it should be denied

  Scenario Outline: view - any role can view a product
    Given a user has the role "<role>" in the store that owns the product
    When authorization is checked for "view" on that product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | granted |

  Scenario Outline: create - owner, admin, and staff can create products
    Given a user has the role "<role>" in the current store
    When authorization is checked for "create" on Product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: update - owner, admin, and staff can update products
    Given a user has the role "<role>" in the store that owns the product
    When authorization is checked for "update" on that product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: delete - only owner and admin can delete products
    Given a user has the role "<role>" in the store that owns the product
    When authorization is checked for "delete" on that product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: archive - only owner and admin can archive products
    Given a user has the role "<role>" in the store that owns the product
    When authorization is checked for "archive" on that product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: restore - only owner and admin can restore products
    Given a user has the role "<role>" in the store that owns the product
    When authorization is checked for "restore" on that product
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - OrderPolicy (Step 1.8)

```gherkin
Feature: OrderPolicy Authorization
  The OrderPolicy authorizes order operations based on the user's store role.

  Scenario Outline: viewAny / view - any role can list or view orders
    Given a user has the role "<role>" in the current store
    When authorization is checked for "viewAny" on Order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | granted |

  Scenario Outline: update - owner, admin, and staff can update orders
    Given a user has the role "<role>" in the store that owns the order
    When authorization is checked for "update" on that order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: cancel - only owner and admin can cancel orders
    Given a user has the role "<role>" in the store that owns the order
    When authorization is checked for "cancel" on that order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: createFulfillment - owner, admin, and staff can create fulfillments
    Given a user has the role "<role>" in the store that owns the order
    When authorization is checked for "createFulfillment" on that order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: createRefund - only owner and admin can create refunds
    Given a user has the role "<role>" in the store that owns the order
    When authorization is checked for "createRefund" on that order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - CollectionPolicy (Step 1.8)

```gherkin
Feature: CollectionPolicy Authorization
  The CollectionPolicy authorizes collection operations based on the user's store role.

  Scenario Outline: viewAny / view - any role can list or view collections
    Given a user has the role "<role>" in the current store
    When authorization is checked for "viewAny" on Collection
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | granted |

  Scenario Outline: create / update - owner, admin, and staff can create or update collections
    Given a user has the role "<role>" in the current store
    When authorization is checked for "create" on Collection
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: delete - only owner and admin can delete collections
    Given a user has the role "<role>" in the store that owns the collection
    When authorization is checked for "delete" on that collection
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - DiscountPolicy (Step 1.8)

```gherkin
Feature: DiscountPolicy Authorization
  The DiscountPolicy authorizes discount operations based on the user's store role.

  Scenario Outline: viewAny / view - any role can list or view discounts
    Given a user has the role "<role>" in the current store
    When authorization is checked for "viewAny" on Discount
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | granted |

  Scenario Outline: create / update - owner, admin, and staff can manage discounts
    Given a user has the role "<role>" in the current store
    When authorization is checked for "create" on Discount
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: delete - only owner and admin can delete discounts
    Given a user has the role "<role>" in the store that owns the discount
    When authorization is checked for "delete" on that discount
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - CustomerPolicy (Step 1.8)

```gherkin
Feature: CustomerPolicy Authorization
  The CustomerPolicy authorizes customer operations based on the user's store role.

  Scenario Outline: viewAny / view - any role can list or view customers
    Given a user has the role "<role>" in the current store
    When authorization is checked for "viewAny" on Customer
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | granted |

  Scenario Outline: update - owner, admin, and staff can update customers
    Given a user has the role "<role>" in the store that owns the customer
    When authorization is checked for "update" on that customer
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |
```

---

## Feature: Authorization - StorePolicy (Step 1.8)

```gherkin
Feature: StorePolicy Authorization
  The StorePolicy authorizes store-level operations based on the user's store role.

  Scenario Outline: viewSettings / updateSettings - only owner and admin
    Given a user has the role "<role>" in the store
    When authorization is checked for "viewSettings" on the store
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: delete - only owner can delete a store
    Given a user has the role "<role>" in the store
    When authorization is checked for "delete" on the store
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | denied  |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - ThemePolicy (Step 1.8)

```gherkin
Feature: ThemePolicy Authorization
  The ThemePolicy authorizes theme operations. Only owner and admin have access.

  Scenario Outline: viewAny / view / create / update / delete / publish - only owner and admin
    Given a user has the role "<role>" in the current store
    When authorization is checked for "<action>" on Theme
    Then it should be <result>

    Examples:
      | role    | action   | result  |
      | owner   | viewAny  | granted |
      | admin   | viewAny  | granted |
      | staff   | viewAny  | denied  |
      | support | viewAny  | denied  |
      | owner   | create   | granted |
      | admin   | create   | granted |
      | staff   | create   | denied  |
      | support | create   | denied  |
      | owner   | update   | granted |
      | admin   | update   | granted |
      | staff   | update   | denied  |
      | support | update   | denied  |
      | owner   | delete   | granted |
      | admin   | delete   | granted |
      | staff   | delete   | denied  |
      | support | delete   | denied  |
      | owner   | publish  | granted |
      | admin   | publish  | granted |
      | staff   | publish  | denied  |
      | support | publish  | denied  |
```

---

## Feature: Authorization - PagePolicy (Step 1.8)

```gherkin
Feature: PagePolicy Authorization
  The PagePolicy authorizes page operations based on the user's store role.

  Scenario Outline: viewAny / view / create / update - owner, admin, and staff
    Given a user has the role "<role>" in the current store
    When authorization is checked for "<action>" on Page
    Then it should be <result>

    Examples:
      | role    | action  | result  |
      | owner   | viewAny | granted |
      | admin   | viewAny | granted |
      | staff   | viewAny | granted |
      | support | viewAny | denied  |
      | owner   | create  | granted |
      | admin   | create  | granted |
      | staff   | create  | granted |
      | support | create  | denied  |

  Scenario Outline: delete - only owner and admin can delete pages
    Given a user has the role "<role>" in the store that owns the page
    When authorization is checked for "delete" on that page
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - FulfillmentPolicy (Step 1.8)

```gherkin
Feature: FulfillmentPolicy Authorization
  The FulfillmentPolicy authorizes fulfillment operations. The create method
  receives the parent Order since the Fulfillment does not exist yet.

  Scenario Outline: create (receives Order) - owner, admin, and staff
    Given a user has the role "<role>" in the store that owns the order
    When authorization is checked for "create" fulfillment on that order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: update / cancel - owner, admin, and staff
    Given a user has the role "<role>" in the store (resolved via fulfillment -> order -> store_id)
    When authorization is checked for "update" on the fulfillment
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |
```

---

## Feature: Authorization - RefundPolicy (Step 1.8)

```gherkin
Feature: RefundPolicy Authorization
  The RefundPolicy authorizes refund creation. Only owner and admin can process refunds.
  The create method receives the parent Order.

  Scenario Outline: create (receives Order) - only owner and admin
    Given a user has the role "<role>" in the store that owns the order
    When authorization is checked for "create" refund on that order
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - NavigationMenuPolicy (Step 1.8)

```gherkin
Feature: NavigationMenuPolicy Authorization
  The NavigationMenuPolicy authorizes navigation menu operations.

  Scenario Outline: viewAny - owner, admin, and staff can view navigation
    Given a user has the role "<role>" in the current store
    When authorization is checked for "viewAny" on NavigationMenu
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: manage - only owner and admin can manage navigation
    Given a user has the role "<role>" in the current store
    When authorization is checked for "manage" navigation
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |
```

---

## Feature: Authorization - Gates (Step 1.8)

```gherkin
Feature: Authorization Gates
  Gates handle non-model authorization for store-level operations. Each gate resolves
  the current store from the container and checks the user's role.

  Scenario Outline: manage-store-settings gate
    Given a user has the role "<role>" in the current store
    When the "manage-store-settings" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: manage-staff gate
    Given a user has the role "<role>" in the current store
    When the "manage-staff" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: manage-developers gate
    Given a user has the role "<role>" in the current store
    When the "manage-developers" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: view-analytics gate
    Given a user has the role "<role>" in the current store
    When the "view-analytics" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | granted |
      | support | denied  |

  Scenario Outline: manage-shipping gate
    Given a user has the role "<role>" in the current store
    When the "manage-shipping" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: manage-taxes gate
    Given a user has the role "<role>" in the current store
    When the "manage-taxes" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: manage-search-settings gate
    Given a user has the role "<role>" in the current store
    When the "manage-search-settings" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: manage-navigation gate
    Given a user has the role "<role>" in the current store
    When the "manage-navigation" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario Outline: manage-apps gate
    Given a user has the role "<role>" in the current store
    When the "manage-apps" gate is checked
    Then it should be <result>

    Examples:
      | role    | result  |
      | owner   | granted |
      | admin   | granted |
      | staff   | denied  |
      | support | denied  |

  Scenario: Gate returns false when user has no role in the store
    Given a user has no role in the current store
    When any gate is checked
    Then it should be denied

  Scenario: Gate resolution follows the standard pattern
    Given the gate resolution pattern is implemented
    Then each gate should resolve the current store from the container
    And look up the user's store_users record for that store
    And return false if no record exists
    And return true only if the user's role is in the gate's required roles list
```

---

## Feature: Authorization - CheckStoreRole Middleware (Step 1.8)

```gherkin
Feature: CheckStoreRole Middleware
  The CheckStoreRole middleware verifies the authenticated user has one of the
  specified roles for the current store before allowing the request to proceed.

  Scenario: User with allowed role passes the middleware
    Given a user has the role "admin" in the current store
    And the middleware is configured with roles "owner,admin"
    When the request passes through CheckStoreRole
    Then the request should proceed
    And the store_user pivot record should be attached to the request attributes

  Scenario: User with disallowed role is rejected with 403
    Given a user has the role "support" in the current store
    And the middleware is configured with roles "owner,admin"
    When the request passes through CheckStoreRole
    Then the response should be HTTP 403
    And the message should be "Insufficient permissions"

  Scenario: User with no store role is rejected with 403
    Given a user has no role in the current store
    When the request passes through CheckStoreRole
    Then the response should be HTTP 403
    And the message should be "You do not have access to this store"
```

---

## Feature: Authorization - Ownership Constraints (Step 1.8)

```gherkin
Feature: Store Ownership Constraints
  Each store must have exactly one Owner. Ownership transfer is a dedicated action,
  not a simple role change.

  Scenario: Each store has exactly one owner
    Given a store exists
    And one user is assigned as "owner" of the store
    Then the store's owner count should be exactly 1

  Scenario: Ownership cannot be changed via simple role update
    Given a user is the "owner" of a store
    When an attempt is made to change their role directly to "admin"
    Then the operation should be prevented or handled through a dedicated ownership transfer action
```

---

## Traceability Table

| Spec Requirement (Step.Description) | Gherkin Feature / Scenario(s) |
|--------------------------------------|-------------------------------|
| **1.1: SQLite database config** | Environment and Configuration / "SQLite database is configured and functional" |
| **1.1: File cache** | Environment and Configuration / "File-based cache is configured" |
| **1.1: File sessions** | Environment and Configuration / "File-based sessions are configured" |
| **1.1: Sync queue** | Environment and Configuration / "Synchronous queue is configured" |
| **1.1: Log mail** | Environment and Configuration / "Log-based mail is configured" |
| **1.1: Customer guard config** | Environment and Configuration / "Customer auth guard is configured" |
| **1.1: Structured JSON logging** | Environment and Configuration / "Structured JSON logging channel is configured" |
| **1.1: Local filesystem** | Environment and Configuration / "Local filesystem is configured for media storage" |
| **1.2: organizations table** | Core Migrations / "Organizations table exists with correct schema" |
| **1.2: stores table** | Core Migrations / "Stores table exists with correct schema" |
| **1.2: store_domains table** | Core Migrations / "Store domains table exists with correct schema" |
| **1.2: users modification** | Core Migrations / "Users table is modified with additional columns" |
| **1.2: store_users table** | Core Migrations / "Store users pivot table exists with composite primary key" |
| **1.2: store_settings table** | Core Migrations / "Store settings table exists with store_id as primary key" |
| **1.2: Monetary amounts as INTEGER** | Core Migrations / "Monetary amounts are stored as integers in minor units" |
| **1.2: Enum CHECK constraints** | Core Migrations / "Enum columns use text with CHECK constraints" |
| **1.2: FK cascading deletes** | Core Migrations / "Foreign key cascading deletes work correctly" |
| **1.3: Organization model + relationships** | Core Models / "Organization has many stores", "Organization factory creates valid records" |
| **1.3: Store model + relationships** | Core Models / "Store belongs to an organization", "Store has many store domains", "Store belongs to many users", "Store has one store settings record", "Store factory creates valid records", "Store model casts status" |
| **1.3: StoreDomain model** | Core Models / "StoreDomain belongs to a store", "StoreDomain factory creates valid records", "StoreDomain model casts type" |
| **1.3: StoreUser pivot model** | Core Models / "StoreUser is a custom Pivot class with role attribute" |
| **1.3: StoreSettings model** | Core Models / "StoreSettings belongs to a store", "StoreSettings casts settings_json", "StoreSettings factory creates valid records" |
| **1.3: User model relationships** | Core Models / "User belongs to many stores through store_users" |
| **1.3: Seeders** | Core Models / "Running seeders creates sample foundation data" |
| **1.3: Explicit return types** | Core Models / "All models have explicit return type declarations" |
| **1.3: Fillable/guarded** | Core Models / "All models have proper fillable or guarded arrays" |
| **1.4: StoreStatus enum** | Enums / "StoreStatus enum has the correct cases" |
| **1.4: StoreUserRole enum** | Enums / "StoreUserRole enum has the correct cases" |
| **1.4: StoreDomainType enum** | Enums / "StoreDomainType enum has the correct cases" |
| **1.5: Storefront hostname resolution** | Tenant Resolution Middleware / "Storefront request with a known hostname resolves the store" |
| **1.5: Unknown hostname 404** | Tenant Resolution Middleware / "Storefront request with an unknown hostname returns 404" |
| **1.5: Suspended store 503** | Tenant Resolution Middleware / "Storefront request for a suspended store returns 503" |
| **1.5: Hostname caching** | Tenant Resolution Middleware / "Hostname-to-store mapping is cached for 5 minutes" |
| **1.5: Admin session resolution** | Tenant Resolution Middleware / "Admin request resolves store from session" |
| **1.5: Admin without store_users 403** | Tenant Resolution Middleware / "Admin request without store_users record returns 403" |
| **1.5: Middleware group registration** | Tenant Resolution Middleware / "ResolveStore is registered in the storefront middleware group", "ResolveStore is registered in the admin middleware group" |
| **1.5: Middleware alias registration** | Tenant Resolution Middleware / "Custom middleware aliases are registered in bootstrap/app.php" |
| **1.5/1.7: CustomerAuthenticate middleware** | CustomerAuthenticate Middleware / "Unauthenticated customer is redirected to login", "Intended URL is saved for redirect after login", "Authenticated customer passes through the middleware" |
| **1.6: StoreScope global scope** | BelongsToStore / "StoreScope filters queries by current store" |
| **1.6: Auto-set store_id on creating** | BelongsToStore / "BelongsToStore auto-sets store_id on creating" |
| **1.6: Behavior without bound store** | BelongsToStore / "StoreScope does not interfere when no current store is bound" |
| **1.6: Applied on all tenant-scoped models** | BelongsToStore / "BelongsToStore trait is applied to all tenant-scoped models" |
| **1.7: Admin login success** | Admin Authentication / "Admin login with valid credentials succeeds" |
| **1.7: Admin login failure (generic msg)** | Admin Authentication / "Admin login with invalid credentials fails", "Admin login with non-existent email fails with generic message" |
| **1.7: Admin login rate limiting** | Admin Authentication / "Admin login is rate-limited to 5 attempts per minute per IP" |
| **1.7: Admin remember me** | Admin Authentication / "Admin login with Remember me sets a long-lived token" |
| **1.7: Admin logout** | Admin Authentication / "Admin logout invalidates the session" |
| **1.7: Admin password reset (exists)** | Admin Authentication / "Requesting a password reset sends an email for existing users" |
| **1.7: Admin password reset (not exists)** | Admin Authentication / "Requesting a password reset for non-existent email gives the same generic response" |
| **1.7: Admin password reset (valid token)** | Admin Authentication / "Password reset with valid token succeeds" |
| **1.7: Admin password reset (expired token)** | Admin Authentication / "Password reset with expired token fails" |
| **1.7: Admin password reset throttle** | Admin Authentication / "Password reset email is throttled to one per 60 seconds" |
| **1.7: Rate limiter registration** | Admin Authentication / "Login rate limiter is registered in AppServiceProvider" |
| **1.7: Customer guard and provider** | Customer Authentication / "Customer guard uses the customer provider" |
| **1.7: CustomerUserProvider store scoping** | Customer Authentication / "CustomerUserProvider scopes credential queries by store_id" |
| **1.7: Customer login success** | Customer Authentication / "Customer login with valid credentials succeeds" |
| **1.7: Customer login redirect to intended URL** | Customer Authentication / "Customer login redirects to intended URL if present" |
| **1.7: Customer login failure** | Customer Authentication / "Customer login with invalid credentials fails with generic message" |
| **1.7: Customer login rate limiting** | Customer Authentication / "Customer login is rate-limited to 5 attempts per minute per IP" |
| **1.7: Customer registration success** | Customer Authentication / "Customer registration with valid data succeeds" |
| **1.7: Customer registration validation** | Customer Authentication / "Customer registration requires name, email, password, and password_confirmation", "Customer registration enforces minimum password length", "Customer registration enforces password confirmation match" |
| **1.7: Customer email unique per store** | Customer Authentication / "Customer email must be unique per store" |
| **1.7: Same email across stores** | Customer Authentication / "Same email can register in different stores" |
| **1.7: Customer marketing_opt_in** | Customer Authentication / "Customer registration supports optional marketing_opt_in", "Customer registration defaults marketing_opt_in to false" |
| **1.7: Customer password reset broker** | Customer Authentication / "Customer password reset uses a separate broker and token table" |
| **1.7: Customer password reset token expiry** | Customer Authentication / "Customer password reset tokens expire after 60 minutes" |
| **1.7: Customer password reset throttle** | Customer Authentication / "Customer password reset email is throttled to one per 60 seconds" |
| **1.7: Customer forgot-password generic response** | Customer Authentication / "Customer forgot-password form gives a generic response" |
| **1.8: ChecksStoreRole trait - getStoreRole** | Role Checking Trait / "getStoreRole returns the user's role", "getStoreRole returns null" |
| **1.8: ChecksStoreRole trait - hasRole** | Role Checking Trait / "hasRole returns true/false" scenarios |
| **1.8: ChecksStoreRole trait - isOwnerOrAdmin** | Role Checking Trait / "isOwnerOrAdmin" scenarios |
| **1.8: ChecksStoreRole trait - isOwnerAdminOrStaff** | Role Checking Trait / "isOwnerAdminOrStaff" scenarios |
| **1.8: ChecksStoreRole trait - isAnyRole** | Role Checking Trait / "isAnyRole" scenarios |
| **1.8: User.roleForStore helper** | Role Checking Trait / "User.roleForStore" scenarios, "A user can have different roles" |
| **1.8: ProductPolicy** | ProductPolicy Authorization / all Scenario Outlines (viewAny, view, create, update, delete, archive, restore) |
| **1.8: OrderPolicy** | OrderPolicy Authorization / all Scenario Outlines (viewAny, view, update, cancel, createFulfillment, createRefund) |
| **1.8: CollectionPolicy** | CollectionPolicy Authorization / all Scenario Outlines (viewAny, view, create, update, delete) |
| **1.8: DiscountPolicy** | DiscountPolicy Authorization / all Scenario Outlines (viewAny, view, create, update, delete) |
| **1.8: CustomerPolicy** | CustomerPolicy Authorization / all Scenario Outlines (viewAny, view, update) |
| **1.8: StorePolicy** | StorePolicy Authorization / all Scenario Outlines (viewSettings, updateSettings, delete) |
| **1.8: ThemePolicy** | ThemePolicy Authorization / all Scenario Outlines (viewAny, view, create, update, delete, publish) |
| **1.8: PagePolicy** | PagePolicy Authorization / all Scenario Outlines (viewAny, view, create, update, delete) |
| **1.8: FulfillmentPolicy** | FulfillmentPolicy Authorization / all Scenario Outlines (create, update, cancel) |
| **1.8: RefundPolicy** | RefundPolicy Authorization / all Scenario Outlines (create) |
| **1.8: NavigationMenuPolicy** | NavigationMenuPolicy Authorization / all Scenario Outlines (viewAny, manage) |
| **1.8: Gates** | Authorization Gates / all gate Scenario Outlines and resolution pattern |
| **1.8: CheckStoreRole middleware** | CheckStoreRole Middleware / allowed role, disallowed role, no role scenarios |
| **1.8: Ownership constraints** | Store Ownership Constraints / "Each store has exactly one owner", "Ownership cannot be changed via simple role update" |
| **1.8: Policy auto-discovery** | (Covered implicitly by policy structure; Laravel 12 auto-discovers by convention) |

---

## Self-Assessment

### Coverage

All 8 steps of Phase 1 have been translated into Gherkin features and scenarios. The traceability table maps every identified requirement to at least one scenario. I systematically walked through the roadmap (Steps 1.1-1.8), the auth spec (Sections 1.1, 1.2, 2.1-2.6, 3.1-3.3), and the database schema (Epic 1) to extract requirements.

### Ambiguities and Interpretations

1. **Session regeneration on customer login**: The spec explicitly requires session regeneration for admin login (to prevent session fixation). The customer login flow diagram also shows "regenerate session." I treated this as a hard requirement for both flows. The spec could be clearer about whether session regeneration is a requirement or just an implementation detail shown in the diagram.

2. **StoreScope behavior when no store is bound**: The spec says StoreScope applies `where('store_id', app('current_store')->id)`, but does not specify what happens during artisan commands, queue jobs, or tests where no store has been bound. I added a scenario for this edge case, interpreting it as "the scope should not apply if no current_store is bound" -- but the spec does not explicitly state this. An error being thrown is equally valid.

3. **Admin route suspended store behavior**: The spec says storefront routes return 503 for suspended stores. For admin routes, the middleware flow diagram (Section 3.3) mentions "Abort 403 for mutations instead." I did not create a separate scenario for "admin mutations on suspended stores return 403" because this distinction is subtle and the roadmap step 1.5 only says "Return 503 for suspended stores on storefront routes." The full middleware behavior for admin routes on suspended stores could use explicit clarification.

4. **"Manage orders" for Support = Read-only**: The roadmap permission matrix says Support gets "Read-only" access to orders. This is different from other "N" entries. I interpreted this as: Support can viewAny and view orders (covered by AnyRole) but cannot update, cancel, create fulfillments, or create refunds. The distinction between "Read-only" and "N" could be stated more explicitly in the policy definitions.

5. **Store handle uniqueness**: The spec says the handle is UNIQUE per table (globally unique), not per organization. This is clear from the schema but worth noting since it differs from patterns like product handles which are unique per store.

6. **password_hash column mapping**: The spec says the column is named `password_hash` but "maps to Laravel's `password` field internally." This likely means using `$authPasswordName` or attribute casting. The Gherkin scenario notes this mapping requirement but the exact implementation mechanism is ambiguous.

7. **Customer password reset store_id scoping**: The spec says token records include `store_id`, but the standard Laravel password broker does not support additional columns. This will likely require a custom password broker or token repository. I captured the requirement (token must include store_id) without specifying how.

8. **NavigationMenuPolicy not in roadmap but in auth spec**: The roadmap's Step 1.8 lists 10 policies but does not include NavigationMenuPolicy. However, the auth spec (Section 2.4) defines it. I included it for completeness since the roadmap references "all policies" and the auth spec is the authoritative source for policy definitions.

9. **Rate limiter sharing between admin and customer**: The roadmap says both admin and customer login use "the `login` rate limiter" with 5 attempts per minute per IP. Whether they share the same rate limiter key space (meaning 5 attempts total across both login forms) or are independent is not fully specified. I treated them as using the same named limiter, which by default keys by IP, meaning attempts are pooled.

10. **Ownership transfer mechanism**: The spec states "Ownership transfer is a dedicated action (not a simple role change)" and "Each store must have exactly one Owner." However, no specific ownership transfer endpoint, service method, or flow is defined in Phase 1. I included scenarios for the constraint but not for the transfer mechanism itself, as it appears to be deferred.
