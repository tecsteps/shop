# Shop Progress

Last updated: 2026-02-14

## Iteration 1 - Foundation (Completed)

| Checkpoint | Status | Notes |
|---|---|---|
| Tooling baseline (`phpstan.neon`, `deptrac.yaml`, `pint.json`) | Completed | PHPStan and Deptrac configs added, Pint excludes `var/` cache output. |
| Core schema (Phase 1-5,7,8,10 migrations) | Completed | Full multi-tenant commerce schema created for foundation, catalog, checkout, orders, analytics, and apps/webhooks. |
| Enums | Completed | Domain enum set added under `app/Enums/*` for statuses, policies, and types. |
| Models + relationships | Completed | Store, catalog, checkout, order, analytics, and app models scaffolded with casts/relations. |
| Tenant middleware (`ResolveStore`) | Completed | Hostname -> store resolution, container binding, unknown host `404`, suspended store `503`. |
| Role middleware (`CheckStoreRole`) | Completed | Store membership + role gate enforced via `store_users`. |
| Store scoping primitives (`StoreScope`, `BelongsToStore`) | Completed | Global scope and automatic `store_id` assignment implemented. |
| Customer auth provider + guard wiring | Completed | Custom `customer` provider/guard/password broker integrated. |
| Foundation seeders/factories | Completed | Idempotent baseline seed data for org/store/domain/users and core commerce placeholders. |
| Foundation tests | Completed | Tenancy, store-scoping, admin/customer auth, and existing starter auth/settings tests all pass. |

## Iteration 1 Verification

- [x] `composer test`
- [x] `./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G`
- [x] `./vendor/bin/deptrac analyse --config-file=deptrac.yaml`

## Next Iteration

- Iteration 2: catalog services + storefront/admin APIs + cart/checkout core workflows + corresponding unit/feature tests.
