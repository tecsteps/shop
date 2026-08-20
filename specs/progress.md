# Implementation Progress

## Foundation tenancy slice

- [x] SQLite, cache, session, and queue defaults configured for the self-contained app.
- [x] Organization, store, domain, store-user, and store-settings schema/model relationships established.
- [x] Store status, domain type, and store-user role enums available.
- [x] `BelongsToStore` and `StoreScope` enforce current-store query and create boundaries.
- [x] `ResolveStore` supports hostname-based storefront resolution and session-based admin resolution.
- [x] Store-role helpers and policy scaffolding added for the specified admin resources.
- [x] Focused Pest coverage added for resolution, tenant isolation, and role authorization.

Catalog, cart, checkout, order, and storefront UI implementation remains outside this bounded slice.
