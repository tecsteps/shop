# Phase 2: Catalog -- Gherkin Specifications

> Products, Variants, Inventory, Collections, Media

---

## Table of Contents

1. [Step 2.1: Database Migrations](#step-21-database-migrations)
2. [Step 2.2: Models, Relationships, and Enums](#step-22-models-relationships-and-enums)
3. [Step 2.3: Product Service, Variant Matrix, Handle Generator](#step-23-product-service-variant-matrix-handle-generator)
4. [Step 2.4: Inventory Service](#step-24-inventory-service)
5. [Step 2.5: Media Upload](#step-25-media-upload)
6. [Traceability Table](#traceability-table)
7. [Self-Assessment](#self-assessment)

---

## Step 2.1: Database Migrations

### Feature: Products table migration

```gherkin
Feature: Products table migration
  The products table stores core product records scoped to a store.

  Scenario: Products table exists with all required columns
    Given the migrations have been run
    Then the "products" table should exist
    And it should have the following columns:
      | column           | type    | nullable | default  |
      | id               | INTEGER | No       | autoincrement |
      | store_id         | INTEGER | No       | -        |
      | title            | TEXT    | No       | -        |
      | handle           | TEXT    | No       | -        |
      | status           | TEXT    | No       | draft    |
      | description_html | TEXT    | Yes      | NULL     |
      | vendor           | TEXT    | Yes      | NULL     |
      | product_type     | TEXT    | Yes      | NULL     |
      | tags             | TEXT    | No       | []       |
      | published_at     | TEXT    | Yes      | NULL     |
      | created_at       | TEXT    | Yes      | NULL     |
      | updated_at       | TEXT    | Yes      | NULL     |

  Scenario: Products table has correct foreign keys
    Given the migrations have been run
    Then the "products" table should have a foreign key on "store_id" referencing "stores(id)" with ON DELETE CASCADE

  Scenario: Products table has correct indexes
    Given the migrations have been run
    Then the "products" table should have a unique index on ("store_id", "handle")
    And it should have an index on ("store_id")
    And it should have an index on ("store_id", "status")
    And it should have an index on ("store_id", "published_at")
    And it should have an index on ("store_id", "vendor")
    And it should have an index on ("store_id", "product_type")

  Scenario: Products status column has a check constraint
    Given the migrations have been run
    Then the "products.status" column should only allow values "draft", "active", "archived"
```

### Feature: Product options table migration

```gherkin
Feature: Product options table migration
  Named option dimensions for a product (e.g. Size, Color).

  Scenario: Product options table exists with all required columns
    Given the migrations have been run
    Then the "product_options" table should exist
    And it should have the following columns:
      | column     | type    | nullable | default |
      | id         | INTEGER | No       | autoincrement |
      | product_id | INTEGER | No       | -       |
      | name       | TEXT    | No       | -       |
      | position   | INTEGER | No       | 0       |

  Scenario: Product options table has correct foreign keys
    Given the migrations have been run
    Then the "product_options" table should have a foreign key on "product_id" referencing "products(id)" with ON DELETE CASCADE

  Scenario: Product options table has correct indexes
    Given the migrations have been run
    Then the "product_options" table should have an index on ("product_id")
    And it should have a unique index on ("product_id", "position")
```

### Feature: Product option values table migration

```gherkin
Feature: Product option values table migration
  Individual values within a product option (e.g. "Small", "Medium", "Large").

  Scenario: Product option values table exists with all required columns
    Given the migrations have been run
    Then the "product_option_values" table should exist
    And it should have the following columns:
      | column            | type    | nullable | default |
      | id                | INTEGER | No       | autoincrement |
      | product_option_id | INTEGER | No       | -       |
      | value             | TEXT    | No       | -       |
      | position          | INTEGER | No       | 0       |

  Scenario: Product option values table has correct foreign keys
    Given the migrations have been run
    Then the "product_option_values" table should have a foreign key on "product_option_id" referencing "product_options(id)" with ON DELETE CASCADE

  Scenario: Product option values table has correct indexes
    Given the migrations have been run
    Then the "product_option_values" table should have an index on ("product_option_id")
    And it should have a unique index on ("product_option_id", "position")
```

### Feature: Product variants table migration

```gherkin
Feature: Product variants table migration
  Purchasable SKU-level variant of a product.

  Scenario: Product variants table exists with all required columns
    Given the migrations have been run
    Then the "product_variants" table should exist
    And it should have the following columns:
      | column            | type    | nullable | default |
      | id                | INTEGER | No       | autoincrement |
      | product_id        | INTEGER | No       | -       |
      | sku               | TEXT    | Yes      | NULL    |
      | barcode           | TEXT    | Yes      | NULL    |
      | price_amount      | INTEGER | No       | 0       |
      | compare_at_amount | INTEGER | Yes      | NULL    |
      | currency          | TEXT    | No       | USD     |
      | weight_g          | INTEGER | Yes      | NULL    |
      | requires_shipping | INTEGER | No       | 1       |
      | is_default        | INTEGER | No       | 0       |
      | position          | INTEGER | No       | 0       |
      | status            | TEXT    | No       | active  |
      | created_at        | TEXT    | Yes      | NULL    |
      | updated_at        | TEXT    | Yes      | NULL    |

  Scenario: Product variants table has correct foreign keys
    Given the migrations have been run
    Then the "product_variants" table should have a foreign key on "product_id" referencing "products(id)" with ON DELETE CASCADE

  Scenario: Product variants table has correct indexes
    Given the migrations have been run
    Then the "product_variants" table should have an index on ("product_id")
    And it should have an index on ("sku")
    And it should have an index on ("barcode")
    And it should have an index on ("product_id", "position")
    And it should have an index on ("product_id", "is_default")

  Scenario: Product variants status column has a check constraint
    Given the migrations have been run
    Then the "product_variants.status" column should only allow values "active", "archived"
```

### Feature: Variant option values table migration

```gherkin
Feature: Variant option values table migration
  Junction table mapping variants to their chosen option values.

  Scenario: Variant option values table exists with composite primary key
    Given the migrations have been run
    Then the "variant_option_values" table should exist
    And it should have a composite primary key on ("variant_id", "product_option_value_id")

  Scenario: Variant option values table has correct foreign keys
    Given the migrations have been run
    Then the "variant_option_values" table should have a foreign key on "variant_id" referencing "product_variants(id)" with ON DELETE CASCADE
    And it should have a foreign key on "product_option_value_id" referencing "product_option_values(id)" with ON DELETE CASCADE

  Scenario: Variant option values table has correct indexes
    Given the migrations have been run
    Then the "variant_option_values" table should have an index on ("product_option_value_id")
```

### Feature: Inventory items table migration

```gherkin
Feature: Inventory items table migration
  Stock tracking per variant per store.

  Scenario: Inventory items table exists with all required columns
    Given the migrations have been run
    Then the "inventory_items" table should exist
    And it should have the following columns:
      | column            | type    | nullable | default |
      | id                | INTEGER | No       | autoincrement |
      | store_id          | INTEGER | No       | -       |
      | variant_id        | INTEGER | No       | -       |
      | quantity_on_hand  | INTEGER | No       | 0       |
      | quantity_reserved | INTEGER | No       | 0       |
      | policy            | TEXT    | No       | deny    |

  Scenario: Inventory items table has correct foreign keys
    Given the migrations have been run
    Then the "inventory_items" table should have a foreign key on "store_id" referencing "stores(id)" with ON DELETE CASCADE
    And it should have a foreign key on "variant_id" referencing "product_variants(id)" with ON DELETE CASCADE

  Scenario: Inventory items table has correct indexes
    Given the migrations have been run
    Then the "inventory_items" table should have a unique index on ("variant_id")
    And it should have an index on ("store_id")

  Scenario: Inventory items policy column has a check constraint
    Given the migrations have been run
    Then the "inventory_items.policy" column should only allow values "deny", "continue"
```

### Feature: Collections table migration

```gherkin
Feature: Collections table migration
  Curated or rule-based product groupings.

  Scenario: Collections table exists with all required columns
    Given the migrations have been run
    Then the "collections" table should exist
    And it should have the following columns:
      | column           | type    | nullable | default |
      | id               | INTEGER | No       | autoincrement |
      | store_id         | INTEGER | No       | -       |
      | title            | TEXT    | No       | -       |
      | handle           | TEXT    | No       | -       |
      | description_html | TEXT    | Yes      | NULL    |
      | type             | TEXT    | No       | manual  |
      | status           | TEXT    | No       | active  |
      | created_at       | TEXT    | Yes      | NULL    |
      | updated_at       | TEXT    | Yes      | NULL    |

  Scenario: Collections table has correct foreign keys
    Given the migrations have been run
    Then the "collections" table should have a foreign key on "store_id" referencing "stores(id)" with ON DELETE CASCADE

  Scenario: Collections table has correct indexes
    Given the migrations have been run
    Then the "collections" table should have a unique index on ("store_id", "handle")
    And it should have an index on ("store_id")
    And it should have an index on ("store_id", "status")

  Scenario: Collections status column has a check constraint
    Given the migrations have been run
    Then the "collections.status" column should only allow values "draft", "active", "archived"

  Scenario: Collections type column has a check constraint
    Given the migrations have been run
    Then the "collections.type" column should only allow values "manual", "automated"
```

### Feature: Collection products table migration

```gherkin
Feature: Collection products table migration
  Pivot linking products to collections with ordering.

  Scenario: Collection products table exists with composite primary key
    Given the migrations have been run
    Then the "collection_products" table should exist
    And it should have a composite primary key on ("collection_id", "product_id")
    And it should have a "position" column of type INTEGER with default 0

  Scenario: Collection products table has correct foreign keys
    Given the migrations have been run
    Then the "collection_products" table should have a foreign key on "collection_id" referencing "collections(id)" with ON DELETE CASCADE
    And it should have a foreign key on "product_id" referencing "products(id)" with ON DELETE CASCADE

  Scenario: Collection products table has correct indexes
    Given the migrations have been run
    Then the "collection_products" table should have an index on ("product_id")
    And it should have an index on ("collection_id", "position")
```

### Feature: Product media table migration

```gherkin
Feature: Product media table migration
  Images and videos attached to a product.

  Scenario: Product media table exists with all required columns
    Given the migrations have been run
    Then the "product_media" table should exist
    And it should have the following columns:
      | column      | type    | nullable | default    |
      | id          | INTEGER | No       | autoincrement |
      | product_id  | INTEGER | No       | -          |
      | type        | TEXT    | No       | image      |
      | storage_key | TEXT    | No       | -          |
      | alt_text    | TEXT    | Yes      | NULL       |
      | width       | INTEGER | Yes      | NULL       |
      | height      | INTEGER | Yes      | NULL       |
      | mime_type   | TEXT    | Yes      | NULL       |
      | byte_size   | INTEGER | Yes      | NULL       |
      | position    | INTEGER | No       | 0          |
      | status      | TEXT    | No       | processing |
      | created_at  | TEXT    | Yes      | NULL       |

  Scenario: Product media table has correct foreign keys
    Given the migrations have been run
    Then the "product_media" table should have a foreign key on "product_id" referencing "products(id)" with ON DELETE CASCADE

  Scenario: Product media table has correct indexes
    Given the migrations have been run
    Then the "product_media" table should have an index on ("product_id")
    And it should have an index on ("product_id", "position")
    And it should have an index on ("status")

  Scenario: Product media type column has a check constraint
    Given the migrations have been run
    Then the "product_media.type" column should only allow values "image", "video"

  Scenario: Product media status column has a check constraint
    Given the migrations have been run
    Then the "product_media.status" column should only allow values "processing", "ready", "failed"
```

---

## Step 2.2: Models, Relationships, and Enums

### Feature: Product model and relationships

```gherkin
Feature: Product model
  The Product model represents a core product entity scoped to a store.

  Scenario: Product belongs to a store
    Given a store exists
    And a product exists for that store
    When I access the product's store relationship
    Then it should return the owning store

  Scenario: Product has many variants
    Given a product exists
    And 3 product variants exist for that product
    When I access the product's variants relationship
    Then it should return 3 ProductVariant models

  Scenario: Product has many options
    Given a product exists
    And 2 product options exist for that product (e.g. "Size", "Color")
    When I access the product's options relationship
    Then it should return 2 ProductOption models

  Scenario: Product has many media
    Given a product exists
    And 4 product media records exist for that product
    When I access the product's media relationship
    Then it should return 4 ProductMedia models

  Scenario: Product belongs to many collections
    Given a product exists
    And the product is assigned to 2 collections via the collection_products pivot
    When I access the product's collections relationship
    Then it should return 2 Collection models

  Scenario: Product uses BelongsToStore trait for tenant scoping
    Given store A has 3 products
    And store B has 5 products
    And the current store is set to store A
    When I query Product::all()
    Then it should return 3 products

  Scenario: Product casts status to ProductStatus enum
    Given a product exists with status "draft"
    When I access the product's status attribute
    Then it should be an instance of ProductStatus::Draft

  Scenario: Product casts tags to array
    Given a product exists with tags '["summer","sale"]'
    When I access the product's tags attribute
    Then it should be a PHP array containing "summer" and "sale"
```

### Feature: ProductOption model and relationships

```gherkin
Feature: ProductOption model
  Named option dimensions for a product (e.g. Size, Color).

  Scenario: ProductOption belongs to a product
    Given a product exists
    And a product option "Size" exists for that product
    When I access the option's product relationship
    Then it should return the parent product

  Scenario: ProductOption has many values
    Given a product option "Size" exists
    And 3 option values exist for it ("S", "M", "L")
    When I access the option's values relationship
    Then it should return 3 ProductOptionValue models
```

### Feature: ProductOptionValue model and relationships

```gherkin
Feature: ProductOptionValue model
  Individual values within a product option.

  Scenario: ProductOptionValue belongs to a product option
    Given a product option "Color" exists
    And a product option value "Red" exists for that option
    When I access the value's productOption relationship
    Then it should return the parent ProductOption
```

### Feature: ProductVariant model and relationships

```gherkin
Feature: ProductVariant model
  Purchasable SKU-level variant of a product.

  Scenario: ProductVariant belongs to a product
    Given a product exists
    And a variant exists for that product
    When I access the variant's product relationship
    Then it should return the parent product

  Scenario: ProductVariant has one inventory item
    Given a variant exists
    And an inventory item exists for that variant
    When I access the variant's inventoryItem relationship
    Then it should return the InventoryItem model

  Scenario: ProductVariant belongs to many option values
    Given a product with option "Size" (values: "S", "M") and option "Color" (values: "Red")
    And a variant exists mapped to "S" and "Red" via variant_option_values pivot
    When I access the variant's optionValues relationship
    Then it should return 2 ProductOptionValue models ("S" and "Red")

  Scenario: ProductVariant casts status to VariantStatus enum
    Given a variant exists with status "active"
    When I access the variant's status attribute
    Then it should be an instance of VariantStatus::Active

  Scenario: ProductVariant stores price in minor units
    Given a variant exists with price_amount 2999
    When I access the variant's price_amount attribute
    Then it should return the integer 2999 (representing $29.99)
```

### Feature: InventoryItem model and relationships

```gherkin
Feature: InventoryItem model
  Stock tracking per variant per store.

  Scenario: InventoryItem belongs to a product variant
    Given a variant exists
    And an inventory item exists for that variant
    When I access the inventory item's variant relationship
    Then it should return the parent ProductVariant

  Scenario: InventoryItem uses BelongsToStore trait for tenant scoping
    Given store A has 3 inventory items
    And store B has 2 inventory items
    And the current store is set to store A
    When I query InventoryItem::all()
    Then it should return 3 inventory items

  Scenario: InventoryItem casts policy to InventoryPolicy enum
    Given an inventory item exists with policy "deny"
    When I access the inventory item's policy attribute
    Then it should be an instance of InventoryPolicy::Deny
```

### Feature: Collection model and relationships

```gherkin
Feature: Collection model
  Curated or rule-based product groupings.

  Scenario: Collection belongs to many products
    Given a collection exists
    And 3 products are assigned to that collection via collection_products pivot
    When I access the collection's products relationship
    Then it should return 3 Product models

  Scenario: Collection uses BelongsToStore trait for tenant scoping
    Given store A has 2 collections
    And store B has 4 collections
    And the current store is set to store A
    When I query Collection::all()
    Then it should return 2 collections

  Scenario: Collection casts status to CollectionStatus enum
    Given a collection exists with status "active"
    When I access the collection's status attribute
    Then it should be an instance of CollectionStatus::Active

  Scenario: Collection products pivot includes position
    Given a collection exists
    And products are assigned with positions 0, 1, 2
    When I access the collection's products relationship
    Then the pivot should include the position column
```

### Feature: ProductMedia model and relationships

```gherkin
Feature: ProductMedia model
  Images and videos attached to a product.

  Scenario: ProductMedia belongs to a product
    Given a product exists
    And a product media record exists for that product
    When I access the media's product relationship
    Then it should return the parent product

  Scenario: ProductMedia casts type to MediaType enum
    Given a product media record exists with type "image"
    When I access the media's type attribute
    Then it should be an instance of MediaType::Image

  Scenario: ProductMedia casts status to MediaStatus enum
    Given a product media record exists with status "processing"
    When I access the media's status attribute
    Then it should be an instance of MediaStatus::Processing
```

### Feature: Enum definitions

```gherkin
Feature: ProductStatus enum
  Product lifecycle states.

  Scenario Outline: ProductStatus enum contains required values
    Then the ProductStatus enum should have a case "<case>" with value "<value>"

    Examples:
      | case     | value    |
      | Draft    | draft    |
      | Active   | active   |
      | Archived | archived |

Feature: VariantStatus enum
  Variant lifecycle states.

  Scenario Outline: VariantStatus enum contains required values
    Then the VariantStatus enum should have a case "<case>" with value "<value>"

    Examples:
      | case     | value    |
      | Active   | active   |
      | Archived | archived |

Feature: CollectionStatus enum
  Collection lifecycle states.

  Scenario Outline: CollectionStatus enum contains required values
    Then the CollectionStatus enum should have a case "<case>" with value "<value>"

    Examples:
      | case     | value    |
      | Draft    | draft    |
      | Active   | active   |
      | Archived | archived |

Feature: MediaType enum
  Media content types.

  Scenario Outline: MediaType enum contains required values
    Then the MediaType enum should have a case "<case>" with value "<value>"

    Examples:
      | case  | value |
      | Image | image |
      | Video | video |

Feature: MediaStatus enum
  Media processing states.

  Scenario Outline: MediaStatus enum contains required values
    Then the MediaStatus enum should have a case "<case>" with value "<value>"

    Examples:
      | case       | value      |
      | Processing | processing |
      | Ready      | ready      |
      | Failed     | failed     |

Feature: InventoryPolicy enum
  Oversell policy for inventory.

  Scenario Outline: InventoryPolicy enum contains required values
    Then the InventoryPolicy enum should have a case "<case>" with value "<value>"

    Examples:
      | case     | value    |
      | Deny     | deny     |
      | Continue | continue |
```

---

## Step 2.3: Product Service, Variant Matrix, Handle Generator

### Feature: ProductService - create

```gherkin
Feature: ProductService create
  Creating products with nested variants and options.

  Scenario: Creates a product with a default variant
    Given a store exists
    When I call ProductService::create with title "Summer T-Shirt" and description "A cool shirt"
    Then a product should exist in the database with title "Summer T-Shirt" and status "draft"
    And the product should have exactly 1 variant with is_default = 1
    And an inventory_item should be created for that variant with quantity_on_hand = 0 and quantity_reserved = 0

  Scenario: Generates a unique handle from the title
    Given a store exists
    When I call ProductService::create with title "Summer T-Shirt"
    Then the product's handle should be "summer-t-shirt"

  Scenario: Appends suffix when handle collides
    Given a store exists
    And a product with handle "t-shirt" already exists in that store
    When I call ProductService::create with title "T-Shirt"
    Then the product's handle should be "t-shirt-1"
```

### Feature: ProductService - update

```gherkin
Feature: ProductService update
  Updating existing products.

  Scenario: Updates a product title and description
    Given a product exists with title "Old Title"
    When I call ProductService::update with title "New Title" and description "New description"
    Then the product's title should be "New Title"
    And the product's description_html should be "New description"
```

### Feature: ProductService - transitionStatus (state machine)

```gherkin
Feature: ProductService status transitions
  Product status transitions follow a strict state machine.

  Scenario: Transitions product from draft to active
    Given a product exists with status "draft"
    And the product has at least one variant with price_amount > 0
    And the product's title is not empty
    When I call ProductService::transitionStatus to ProductStatus::Active
    Then the product's status should be "active"
    And the product's published_at should be set to the current time

  Scenario: Rejects draft to active without a priced variant
    Given a product exists with status "draft"
    And the product's only variant has price_amount = 0
    When I call ProductService::transitionStatus to ProductStatus::Active
    Then an InvalidProductTransitionException should be thrown
    And the product's status should remain "draft"

  Scenario: Rejects draft to active when title is empty
    Given a product exists with status "draft" and title ""
    And the product has a variant with price_amount > 0
    When I call ProductService::transitionStatus to ProductStatus::Active
    Then an InvalidProductTransitionException should be thrown

  Scenario: Transitions product from draft to archived
    Given a product exists with status "draft"
    When I call ProductService::transitionStatus to ProductStatus::Archived
    Then the product's status should be "archived"

  Scenario: Transitions product from active to archived
    Given a product exists with status "active"
    When I call ProductService::transitionStatus to ProductStatus::Archived
    Then the product's status should be "archived"

  Scenario: Transitions product from archived to active
    Given a product exists with status "archived"
    And the product has at least one variant with price_amount > 0
    And the product's title is not empty
    When I call ProductService::transitionStatus to ProductStatus::Active
    Then the product's status should be "active"

  Scenario: Prevents active to draft when order lines exist
    Given a product exists with status "active"
    And order_lines reference one of the product's variants
    When I call ProductService::transitionStatus to ProductStatus::Draft
    Then an InvalidProductTransitionException should be thrown
    And the product's status should remain "active"

  Scenario: Prevents archived to draft when order lines exist
    Given a product exists with status "archived"
    And order_lines reference one of the product's variants
    When I call ProductService::transitionStatus to ProductStatus::Draft
    Then an InvalidProductTransitionException should be thrown
    And the product's status should remain "archived"

  Scenario: Allows active to draft when no order lines exist
    Given a product exists with status "active"
    And no order_lines reference any of the product's variants
    When I call ProductService::transitionStatus to ProductStatus::Draft
    Then the product's status should be "draft"

  Scenario: Dispatches ProductStatusChanged event after successful transition
    Given a product exists with status "draft"
    And the product has at least one variant with price_amount > 0
    When I call ProductService::transitionStatus to ProductStatus::Active
    Then a ProductStatusChanged event should be dispatched

  Scenario: Does not set published_at if already set on re-activation
    Given a product exists with status "archived"
    And the product's published_at is already set to "2026-01-01T00:00:00Z"
    And the product has a variant with price_amount > 0
    When I call ProductService::transitionStatus to ProductStatus::Active
    Then the product's published_at should remain "2026-01-01T00:00:00Z"
```

### Feature: ProductService - delete

```gherkin
Feature: ProductService delete
  Hard deleting products.

  Scenario: Hard deletes a draft product with no order references
    Given a product exists with status "draft"
    And no order_lines reference any of the product's variants
    When I call ProductService::delete
    Then the product should be removed from the database

  Scenario: Prevents deletion of product with order references
    Given a product exists with status "draft"
    And order_lines reference one of the product's variants
    When I call ProductService::delete
    Then an exception should be thrown
    And the product should still exist in the database

  Scenario: Prevents deletion of non-draft products
    Given a product exists with status "active"
    And no order_lines reference any of the product's variants
    When I call ProductService::delete
    Then an exception should be thrown
    And the product should still exist in the database
```

### Feature: VariantMatrixService - rebuildMatrix

```gherkin
Feature: VariantMatrixService rebuildMatrix
  Computes cartesian product of option values, creates missing variants, handles orphaned ones.

  Scenario: Creates variants from option matrix
    Given a product exists
    And the product has option "Size" with values ["S", "M", "L"]
    And the product has option "Color" with values ["Red", "Blue"]
    When I call VariantMatrixService::rebuildMatrix
    Then 6 variants should be created (3 sizes x 2 colors)
    And each variant should be linked to its corresponding option values via the variant_option_values pivot

  Scenario: Preserves existing variants when adding an option value
    Given a product exists
    And the product has option "Size" with values ["S", "M"]
    And 2 variants exist from a previous matrix build, each with price_amount = 1999
    When I add value "L" to the "Size" option
    And I call VariantMatrixService::rebuildMatrix
    Then 3 variants should exist in total
    And the original 2 variants should be unchanged with price_amount = 1999

  Scenario: New variants inherit default pricing from first existing variant
    Given a product exists
    And the product has option "Size" with values ["S", "M"]
    And 2 variants exist, the first with price_amount = 2999
    When I add value "L" to the "Size" option
    And I call VariantMatrixService::rebuildMatrix
    Then the new variant for "L" should have price_amount = 2999

  Scenario: Archives orphaned variants with order references
    Given a product exists with option "Size" with values ["S", "M", "L"]
    And 3 variants exist from a previous matrix build
    And order_lines reference the variant for "L"
    When I remove the value "L" from the "Size" option
    And I call VariantMatrixService::rebuildMatrix
    Then 2 variants should remain active
    And the variant for "L" should have status "archived"

  Scenario: Deletes orphaned variants without order references
    Given a product exists with option "Size" with values ["S", "M", "L"]
    And 3 variants exist from a previous matrix build
    And no order_lines reference the variant for "L"
    When I remove the value "L" from the "Size" option
    And I call VariantMatrixService::rebuildMatrix
    Then 2 variants should remain
    And the variant for "L" should be deleted from the database

  Scenario: Auto-creates default variant for products without options
    Given a product exists with no options defined
    When I call VariantMatrixService::rebuildMatrix
    Then exactly 1 variant should exist with is_default = 1

  Scenario: Handles three options (3-way cartesian product)
    Given a product exists
    And the product has option "Size" with values ["S", "M"]
    And the product has option "Color" with values ["Red", "Blue"]
    And the product has option "Material" with values ["Cotton", "Poly"]
    When I call VariantMatrixService::rebuildMatrix
    Then 8 variants should be created (2 x 2 x 2)
```

### Feature: HandleGenerator

```gherkin
Feature: HandleGenerator
  Generates unique URL-friendly slugs scoped to a store.

  Scenario: Generates a slug from title
    Given a store exists with no products
    When I call HandleGenerator::generate with title "My Amazing Product" for the "products" table
    Then the result should be "my-amazing-product"

  Scenario: Appends suffix on collision
    Given a store exists
    And a product with handle "t-shirt" exists in that store
    When I call HandleGenerator::generate with title "T-Shirt" for the "products" table
    Then the result should be "t-shirt-1"

  Scenario: Increments suffix on multiple collisions
    Given a store exists
    And products with handles "t-shirt" and "t-shirt-1" exist in that store
    When I call HandleGenerator::generate with title "T-Shirt" for the "products" table
    Then the result should be "t-shirt-2"

  Scenario: Handles special characters
    Given a store exists
    When I call HandleGenerator::generate with title "Loewe's Fall/Winter 2026" for the "products" table
    Then the result should be a valid URL slug (lowercase, hyphens, no special characters)

  Scenario: Excludes current record id from collision check
    Given a store exists
    And a product with id 5 and handle "t-shirt" exists in that store
    When I call HandleGenerator::generate with title "T-Shirt" for the "products" table, excluding id 5
    Then the result should be "t-shirt" (no suffix needed)

  Scenario: Scopes uniqueness check to store
    Given store A has a product with handle "t-shirt"
    And store B has no products
    When I call HandleGenerator::generate with title "T-Shirt" for the "products" table in store B
    Then the result should be "t-shirt" (no suffix needed)

  Scenario: Works for collections table
    Given a store exists
    And a collection with handle "summer-sale" exists in that store
    When I call HandleGenerator::generate with title "Summer Sale" for the "collections" table
    Then the result should be "summer-sale-1"
```

---

## Step 2.4: Inventory Service

### Feature: InventoryService - checkAvailability

```gherkin
Feature: InventoryService checkAvailability
  Checking if sufficient available inventory exists.

  Scenario: Returns true when available inventory is sufficient
    Given an inventory item exists with quantity_on_hand = 10 and quantity_reserved = 3
    When I call InventoryService::checkAvailability with quantity 7
    Then it should return true

  Scenario: Returns false when available inventory is insufficient (deny policy)
    Given an inventory item exists with quantity_on_hand = 10, quantity_reserved = 8, and policy = "deny"
    When I call InventoryService::checkAvailability with quantity 5
    Then it should return false

  Scenario: Available is computed as on_hand minus reserved
    Given an inventory item exists with quantity_on_hand = 10 and quantity_reserved = 3
    When I call InventoryService::checkAvailability with quantity 7
    Then it should return true (available = 10 - 3 = 7)

  Scenario: Returns true with continue policy even if available is insufficient
    Given an inventory item exists with quantity_on_hand = 2, quantity_reserved = 0, and policy = "continue"
    When I call InventoryService::checkAvailability with quantity 5
    Then it should return true (continue policy allows overselling)
```

### Feature: InventoryService - reserve

```gherkin
Feature: InventoryService reserve
  Reserving inventory for a checkout.

  Scenario: Reserves inventory successfully
    Given an inventory item exists with quantity_on_hand = 10 and quantity_reserved = 0
    When I call InventoryService::reserve with quantity 3
    Then the inventory item's quantity_reserved should be 3
    And the quantity_on_hand should remain 10

  Scenario: Throws InsufficientInventoryException when reserving more than available with deny policy
    Given an inventory item exists with quantity_on_hand = 5, quantity_reserved = 3, and policy = "deny"
    When I call InventoryService::reserve with quantity 3
    Then an InsufficientInventoryException should be thrown
    And the quantity_reserved should remain 3

  Scenario: Allows overselling with continue policy
    Given an inventory item exists with quantity_on_hand = 2, quantity_reserved = 0, and policy = "continue"
    When I call InventoryService::reserve with quantity 5
    Then the inventory item's quantity_reserved should be 5
    And no exception should be thrown

  Scenario: Reserve operation is wrapped in a database transaction
    Given an inventory item exists
    When I call InventoryService::reserve
    Then the operation should execute within a database transaction
```

### Feature: InventoryService - release

```gherkin
Feature: InventoryService release
  Releasing previously reserved inventory.

  Scenario: Releases reserved inventory
    Given an inventory item exists with quantity_on_hand = 10 and quantity_reserved = 5
    When I call InventoryService::release with quantity 3
    Then the inventory item's quantity_reserved should be 2
    And the quantity_on_hand should remain 10

  Scenario: Release operation is wrapped in a database transaction
    Given an inventory item exists
    When I call InventoryService::release
    Then the operation should execute within a database transaction
```

### Feature: InventoryService - commit

```gherkin
Feature: InventoryService commit
  Committing inventory after payment is confirmed.

  Scenario: Commits inventory on order completion
    Given an inventory item exists with quantity_on_hand = 10 and quantity_reserved = 3
    When I call InventoryService::commit with quantity 3
    Then the inventory item's quantity_on_hand should be 7
    And the inventory item's quantity_reserved should be 0

  Scenario: Commit decrements both on_hand and reserved
    Given an inventory item exists with quantity_on_hand = 20 and quantity_reserved = 5
    When I call InventoryService::commit with quantity 5
    Then the inventory item's quantity_on_hand should be 15
    And the inventory item's quantity_reserved should be 0

  Scenario: Commit operation is wrapped in a database transaction
    Given an inventory item exists
    When I call InventoryService::commit
    Then the operation should execute within a database transaction
```

### Feature: InventoryService - restock

```gherkin
Feature: InventoryService restock
  Restocking inventory after refund or restocking.

  Scenario: Restocks inventory
    Given an inventory item exists with quantity_on_hand = 5
    When I call InventoryService::restock with quantity 10
    Then the inventory item's quantity_on_hand should be 15
    And the quantity_reserved should remain unchanged

  Scenario: Restock operation is wrapped in a database transaction
    Given an inventory item exists
    When I call InventoryService::restock
    Then the operation should execute within a database transaction
```

### Feature: Inventory item auto-creation

```gherkin
Feature: Inventory item auto-creation
  An inventory item is automatically created when a variant is created.

  Scenario: Creates inventory item when variant is created
    Given a product exists
    When a new variant is created for that product
    Then an inventory_item should be created automatically
    And the inventory_item should have quantity_on_hand = 0
    And the inventory_item should have quantity_reserved = 0
    And the inventory_item should have policy = "deny"
```

---

## Step 2.5: Media Upload

### Feature: Media upload via Livewire

```gherkin
Feature: Media upload via Livewire
  Uploading product images via Livewire file upload.

  Scenario: Uploads an image for a product
    Given a product exists
    When I upload a JPEG image via Livewire file upload
    Then a product_media row should be created with status "processing"
    And the media type should be "image"
    And the file should be stored on the local public disk

  Scenario: Rejects non-image file types
    Given a product exists
    When I attempt to upload a .txt file via Livewire file upload
    Then a validation error should be returned
    And no product_media row should be created

  Scenario: Sets alt text on media
    Given a product exists with an uploaded image
    When I update the media's alt_text to "Product front view"
    Then the alt_text should be persisted as "Product front view"

  Scenario: Reorders media positions
    Given a product exists with 3 uploaded images at positions 0, 1, 2
    When I reorder the media to positions 2, 0, 1
    Then the position values should be updated accordingly

  Scenario: Deletes media and removes file from storage
    Given a product exists with an uploaded image stored on disk
    When I delete the product media record
    Then the product_media row should be removed from the database
    And the file should be removed from disk
```

### Feature: ProcessMediaUpload job

```gherkin
Feature: ProcessMediaUpload job
  Background job that processes uploaded media files.

  Scenario: Processes uploaded image and generates size variants
    Given a product media record exists with status "processing"
    And the original image file exists on disk
    When the ProcessMediaUpload job is dispatched and executed
    Then the media status should be updated to "ready"
    And a thumbnail image (150x150) should exist on disk
    And a medium image (600x600) should exist on disk
    And a large image (1200x1200) should exist on disk

  Scenario: Sets media status to failed on processing error
    Given a product media record exists with status "processing"
    And the original image file is corrupted or missing
    When the ProcessMediaUpload job is dispatched and executed
    Then the media status should be updated to "failed"

  Scenario: Updates width and height metadata after processing
    Given a product media record exists with status "processing"
    And the original image is 2000x1500 pixels
    When the ProcessMediaUpload job is dispatched and executed
    Then the media's width should be set
    And the media's height should be set
    And the media's byte_size should be set
    And the media's mime_type should be set
```

---

## Step 2.3 (continued): SKU Uniqueness

### Feature: SKU uniqueness

```gherkin
Feature: SKU uniqueness within a store
  The combination of store_id (via product) and SKU must be unique.

  Scenario: Validates SKU uniqueness within store
    Given a store exists
    And a variant exists with SKU "TSH-001" in that store
    When I attempt to create another variant with SKU "TSH-001" in the same store
    Then a validation error should be returned

  Scenario: Allows duplicate SKU across different stores
    Given store A has a variant with SKU "TSH-001"
    When I create a variant with SKU "TSH-001" in store B
    Then the variant should be created successfully

  Scenario: Allows null SKUs
    Given a store exists
    When I create two variants with null SKU values in the same store
    Then both variants should be created successfully

  Scenario: Allows empty string SKUs
    Given a store exists
    When I create two variants with empty string SKU values in the same store
    Then both variants should be created successfully
```

---

## Step 2.3 & 2.4 (continued): Product CRUD feature tests

### Feature: Product CRUD operations

```gherkin
Feature: Product CRUD operations
  Admin-facing product management.

  Scenario: Lists products for the current store
    Given a store context exists with an authenticated admin user
    And 5 products exist for the current store
    When I visit GET /admin/products
    Then I should receive a 200 response
    And I should see all 5 product titles

  Scenario: Filters products by status
    Given a store context exists with an authenticated admin user
    And 3 active products, 2 draft products, and 1 archived product exist
    When I filter products by status "active"
    Then I should see 3 results

  Scenario: Searches products by title
    Given a store context exists with an authenticated admin user
    And a product titled "Organic Cotton Hoodie" exists
    When I search for "cotton"
    Then the product should appear in the search results
```

### Feature: Collection CRUD operations

```gherkin
Feature: Collection operations
  Managing product collections.

  Scenario: Creates a collection with a unique handle
    Given a store context exists
    When I create a collection with title "Summer Sale"
    Then a collection should exist in the database with handle "summer-sale"

  Scenario: Adds products to a collection
    Given a collection exists
    And 3 products exist
    When I add all 3 products to the collection
    Then the collection_products pivot should have 3 rows

  Scenario: Removes products from a collection
    Given a collection exists with 3 products
    When I remove 1 product from the collection
    Then 2 products should remain in the collection

  Scenario: Reorders products within a collection
    Given a collection exists with 3 products at positions 0, 1, 2
    When I reorder the products to positions 2, 0, 1
    Then the position values should be updated correctly

  Scenario: Transitions collection from draft to active
    Given a collection exists with status "draft"
    When I transition the collection to status "active"
    Then the collection's status should be "active"

  Scenario: Lists collections with product count
    Given collection A exists with 5 products
    And collection B exists with 3 products
    When I list all collections
    Then collection A should show a product count of 5
    And collection B should show a product count of 3

  Scenario: Scopes collections to current store
    Given store A has 2 collections
    And store B has 4 collections
    And the current store is set to store A
    When I query Collection::count()
    Then the result should be 2
```

---

## Traceability Table

| Spec Requirement | Gherkin Scenario(s) |
|---|---|
| **Step 2.1: create_products_table** | Products table exists with all required columns; Products table has correct foreign keys; Products table has correct indexes; Products status column has a check constraint |
| **Step 2.1: create_product_options_table** | Product options table exists with all required columns; Product options table has correct foreign keys; Product options table has correct indexes |
| **Step 2.1: create_product_option_values_table** | Product option values table exists with all required columns; Product option values table has correct foreign keys; Product option values table has correct indexes |
| **Step 2.1: create_product_variants_table** | Product variants table exists with all required columns; Product variants table has correct foreign keys; Product variants table has correct indexes; Product variants status column has a check constraint |
| **Step 2.1: create_variant_option_values_table** | Variant option values table exists with composite primary key; Variant option values table has correct foreign keys; Variant option values table has correct indexes |
| **Step 2.1: create_inventory_items_table** | Inventory items table exists with all required columns; Inventory items table has correct foreign keys; Inventory items table has correct indexes; Inventory items policy column has a check constraint |
| **Step 2.1: create_collections_table** | Collections table exists with all required columns; Collections table has correct foreign keys; Collections table has correct indexes; Collections status/type check constraints |
| **Step 2.1: create_collection_products_table** | Collection products table exists with composite primary key; Collection products table has correct foreign keys; Collection products table has correct indexes |
| **Step 2.1: create_product_media_table** | Product media table exists with all required columns; Product media table has correct foreign keys; Product media table has correct indexes; Product media type/status check constraints |
| **Step 2.2: Product model relationships** | Product belongs to a store; Product has many variants; Product has many options; Product has many media; Product belongs to many collections; Product uses BelongsToStore trait |
| **Step 2.2: ProductOption model relationships** | ProductOption belongs to a product; ProductOption has many values |
| **Step 2.2: ProductOptionValue model relationships** | ProductOptionValue belongs to a product option |
| **Step 2.2: ProductVariant model relationships** | ProductVariant belongs to a product; ProductVariant has one inventory item; ProductVariant belongs to many option values |
| **Step 2.2: InventoryItem model relationships** | InventoryItem belongs to a product variant; InventoryItem uses BelongsToStore trait |
| **Step 2.2: Collection model relationships** | Collection belongs to many products; Collection uses BelongsToStore trait |
| **Step 2.2: ProductMedia model relationships** | ProductMedia belongs to a product |
| **Step 2.2: ProductStatus enum** | ProductStatus enum contains required values (Draft, Active, Archived) |
| **Step 2.2: VariantStatus enum** | VariantStatus enum contains required values (Active, Archived) |
| **Step 2.2: CollectionStatus enum** | CollectionStatus enum contains required values (Draft, Active, Archived) |
| **Step 2.2: MediaType enum** | MediaType enum contains required values (Image, Video) |
| **Step 2.2: MediaStatus enum** | MediaStatus enum contains required values (Processing, Ready, Failed) |
| **Step 2.2: InventoryPolicy enum** | InventoryPolicy enum contains required values (Deny, Continue) |
| **Step 2.3: ProductService::create** | Creates a product with a default variant; Generates a unique handle from the title; Appends suffix when handle collides |
| **Step 2.3: ProductService::update** | Updates a product title and description |
| **Step 2.3: ProductService::transitionStatus** | Transitions draft to active; Rejects draft to active without priced variant; Rejects draft to active when title is empty; Transitions draft to archived; Transitions active to archived; Transitions archived to active; Prevents active to draft when order lines exist; Prevents archived to draft when order lines exist; Allows active to draft when no order lines exist; Dispatches ProductStatusChanged event; Does not reset published_at on re-activation |
| **Step 2.3: ProductService::delete** | Hard deletes draft product with no order references; Prevents deletion of product with order references; Prevents deletion of non-draft products |
| **Step 2.3: VariantMatrixService::rebuildMatrix** | Creates variants from option matrix; Preserves existing variants when adding option value; New variants inherit default pricing; Archives orphaned variants with order references; Deletes orphaned variants without order references; Auto-creates default variant for products without options; Handles three options |
| **Step 2.3: HandleGenerator::generate** | Generates a slug from title; Appends suffix on collision; Increments suffix on multiple collisions; Handles special characters; Excludes current record id from collision check; Scopes uniqueness check to store; Works for collections table |
| **Step 2.3: SKU uniqueness** | Validates SKU uniqueness within store; Allows duplicate SKU across different stores; Allows null SKUs; Allows empty string SKUs |
| **Step 2.4: InventoryService::checkAvailability** | Returns true when available is sufficient; Returns false when available is insufficient (deny); Available computed as on_hand minus reserved; Returns true with continue policy even if insufficient |
| **Step 2.4: InventoryService::reserve** | Reserves inventory successfully; Throws InsufficientInventoryException with deny policy; Allows overselling with continue policy; Wrapped in database transaction |
| **Step 2.4: InventoryService::release** | Releases reserved inventory; Wrapped in database transaction |
| **Step 2.4: InventoryService::commit** | Commits inventory on order completion; Decrements both on_hand and reserved; Wrapped in database transaction |
| **Step 2.4: InventoryService::restock** | Restocks inventory; Wrapped in database transaction |
| **Step 2.4: Inventory auto-creation** | Creates inventory item when variant is created |
| **Step 2.5: Livewire file upload** | Uploads an image for a product; Rejects non-image file types; Sets alt text on media; Reorders media positions; Deletes media and removes file from storage |
| **Step 2.5: ProcessMediaUpload job** | Processes uploaded image and generates size variants (150x150, 600x600, 1200x1200); Sets status to failed on error; Updates width/height/byte_size/mime_type metadata |
| **Pest: ProductCrudTest** | Lists products for current store; Creates product with default variant; Generates unique handle; Appends suffix on collision; Updates a product; Transitions draft to active; Rejects draft to active without priced variant; Transitions active to archived; Prevents active to draft with order lines; Hard deletes draft product; Prevents deletion with order references; Filters by status; Searches by title |
| **Pest: VariantTest** | Creates variants from option matrix; Preserves existing variants; Archives orphaned variants with order refs; Deletes orphaned variants without refs; Auto-creates default variant; Validates SKU uniqueness within store; Allows duplicate SKU across stores; Allows null SKUs |
| **Pest: InventoryTest** | Creates inventory item when variant created; Checks availability correctly; Reserves inventory; Throws InsufficientInventoryException; Allows overselling with continue; Releases reserved inventory; Commits inventory; Restocks inventory |
| **Pest: CollectionTest** | Creates collection with unique handle; Adds products; Removes products; Reorders products; Transitions draft to active; Lists with product count; Scopes to current store |
| **Pest: MediaUploadTest** | Uploads image; Processes and generates variants; Rejects non-image types; Sets alt text; Reorders positions; Deletes media and removes file |
| **Pest: HandleGeneratorTest** | Generates slug from title; Appends suffix on collision; Increments suffix; Handles special characters; Excludes current record id; Scopes to store |

---

## Self-Assessment

### Coverage Completeness

- **Migrations (Step 2.1):** All 9 tables are covered (products, product_options, product_option_values, product_variants, variant_option_values, inventory_items, collections, collection_products, product_media). Every column, foreign key, index, and check constraint from `specs/01-DATABASE-SCHEMA.md` is represented in Gherkin scenarios.

- **Models and Relationships (Step 2.2):** All 7 models are covered with their relationship definitions matching the spec exactly: Product (4 relationships + BelongsToStore), ProductOption (2), ProductOptionValue (1), ProductVariant (3), InventoryItem (1 + BelongsToStore), Collection (1 + BelongsToStore), ProductMedia (1). All 6 enums are covered with every case value.

- **Product Service (Step 2.3):** All 4 methods (create, update, transitionStatus, delete) are covered. The state machine has scenarios for all 7 valid transitions plus all blocked transitions. Edge cases include published_at preservation on re-activation and event dispatching.

- **Variant Matrix Service (Step 2.3):** The rebuildMatrix method has 7 scenarios covering cartesian product generation, variant preservation, default pricing inheritance, orphan archival vs. deletion, default variant auto-creation, and 3-way cartesian products.

- **Handle Generator (Step 2.3):** All 6 test cases from the Pest spec are mapped to Gherkin scenarios, plus an additional scenario for collections table applicability.

- **SKU Uniqueness (Step 2.3):** All edge cases covered: uniqueness within store, cross-store duplicates, null SKUs, empty SKUs.

- **Inventory Service (Step 2.4):** All 5 methods are covered. Transaction wrapping is verified. The InsufficientInventoryException is tested for deny policy, and overselling is tested for continue policy.

- **Media Upload (Step 2.5):** Both Livewire upload and ProcessMediaUpload job are covered with all scenarios from the Pest test spec (upload, processing, rejection, alt text, reordering, deletion).

### Pest Test Alignment

Every test case listed in `specs/09-IMPLEMENTATION-ROADMAP.md` Section 3 for the following test files has a corresponding Gherkin scenario:

- `tests/Unit/HandleGeneratorTest.php` (6 tests)
- `tests/Feature/Products/ProductCrudTest.php` (13 tests)
- `tests/Feature/Products/VariantTest.php` (8 tests)
- `tests/Feature/Products/InventoryTest.php` (8 tests)
- `tests/Feature/Products/CollectionTest.php` (7 tests)
- `tests/Feature/Products/MediaUploadTest.php` (6 tests)

### Gaps or Risks

- **No explicit Gherkin for the `product_media.updated_at` column:** The schema spec does not list `updated_at` on `product_media` (only `created_at`). This is intentional per the spec -- the Gherkin reflects the schema as-is.
- **Collection status transitions:** The spec defines CollectionStatus enum (Draft, Active, Archived) but does not specify a full state machine like products. The Gherkin includes a basic draft-to-active transition as specified in the Pest test plan.
- **ProcessMediaUpload job failure scenarios:** The spec mentions status moves to "failed" but does not detail specific failure triggers beyond "corrupted or missing" file. The Gherkin keeps this at the spec's level of detail.
