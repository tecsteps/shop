# Phase 8: Search - Gherkin Specs

## Feature: Full-Text Search

### Scenario: Search returns matching products
```gherkin
Given a store with active products "Red Running Shoes" and "Blue Hiking Boots"
When I search for "running"
Then I should see "Red Running Shoes" in the results
And I should not see "Blue Hiking Boots" in the results
```

### Scenario: Search is scoped to the current store
```gherkin
Given store A has active product "Laptop Stand"
And store B has active product "Laptop Bag"
When I search for "laptop" in store A
Then I should see "Laptop Stand" in the results
And I should not see "Laptop Bag" in the results
```

### Scenario: Search returns empty for no matches
```gherkin
Given a store with active products "Red Running Shoes" and "Blue Hiking Boots"
When I search for "xyznonexistent"
Then I should see 0 results
```

### Scenario: Search logs the query
```gherkin
Given a store with products
When I search for "shoes"
Then a search_queries record should be created with query "shoes" and the result count
```

### Scenario: Search results are paginated
```gherkin
Given a store with 30 active products containing "widget" in the title
When I search for "widget" with 12 results per page
Then I should see 12 results on the first page
And the paginator should report 30 total results
```

## Feature: Autocomplete

### Scenario: Autocomplete returns prefix matches
```gherkin
Given a store with active products "Summer Dress", "Summer Shorts", "Winter Coat"
When I request autocomplete for prefix "sum"
Then I should receive suggestions including "Summer Dress" and "Summer Shorts"
And I should not receive "Winter Coat"
```

### Scenario: Autocomplete respects limit
```gherkin
Given a store with 10 active products starting with "A"
When I request autocomplete for prefix "a" with limit 5
Then I should receive at most 5 suggestions
```

### Scenario: Autocomplete rejects very short prefixes
```gherkin
Given a store with products
When I request autocomplete for prefix "a" (single character)
Then I should receive an empty collection
```

## Feature: FTS5 Index Sync

### Scenario: New product is synced to FTS5 index
```gherkin
Given a store
When I create a new product with title "Organic Coffee Beans"
Then the products_fts table should contain an entry for "Organic Coffee Beans"
```

### Scenario: Updated product syncs changes to FTS5 index
```gherkin
Given a product "Old Title" in the FTS5 index
When I update the product title to "New Title"
Then searching for "New Title" should find the product
And searching for "Old Title" should not find the product
```

### Scenario: Deleted product is removed from FTS5 index
```gherkin
Given a product "Discontinued Item" in the FTS5 index
When I delete the product
Then searching for "Discontinued Item" should return 0 results
```

## Feature: Search UI - Modal

### Scenario: Search modal autocomplete
```gherkin
Given I am on any storefront page
When I open the search modal and type "dre"
Then I should see autocomplete suggestions matching "dre"
And clicking a suggestion should navigate to that product
```

### Scenario: Search modal submits to search page
```gherkin
Given I have opened the search modal
When I type "shoes" and press Enter
Then I should be redirected to /search?q=shoes
```

## Feature: Search UI - Results Page

### Scenario: Full search results with filters
```gherkin
Given I navigate to /search?q=shoes
Then I should see search results matching "shoes"
And I should see filter options for vendor and price range
And I should see sort options (relevance, price low-to-high, price high-to-low, newest)
```

### Scenario: Filtering search results by vendor
```gherkin
Given search results for "shoes" include products from vendors "Nike" and "Adidas"
When I filter by vendor "Nike"
Then I should only see products from vendor "Nike"
```
