# Phase 1 Foundation - Browser Test Plan

## Environment
- URL: http://shop.test
- Admin login: /admin/login
- Admin credentials: test@example.com / password (seeded via DatabaseSeeder)
- Storefront domain: shop.test (seeded in StoreDomainSeeder)
- Date: 2026-03-16

## Test Cases

### TC-1: Admin Login Page Renders
**Steps:** Navigate to http://shop.test/admin/login
**Expected:** Login form renders with email field, password field, remember-me checkbox, and submit button.
**Status:** PASS
**Result:** Login form renders correctly. Shows "Admin Login" heading, "Sign in to your admin account" subtext, email input (placeholder: admin@example.com), password input, "Remember me" checkbox, and "Log in" button.
**Screenshot:** specs/screenshots/tc1-login-form.png

### TC-2: Admin Login with Valid Credentials
**Steps:** Fill email=test@example.com, password=password, click "Log in".
**Expected:** Redirect to /admin dashboard page.
**Status:** PASS
**Result:** Login succeeded. Redirected to http://shop.test/admin. Dashboard page renders with sidebar showing "Laravel Starter Kit" branding, "Dashboard" nav link, and user menu showing "TU Test User".
**Screenshot:** specs/screenshots/tc2-admin-dashboard.png

### TC-3: Admin Login with Invalid Credentials
**Steps:** Fill email=test@example.com, password=wrongpassword, click "Log in".
**Expected:** Generic error message shown, no redirect.
**Status:** PASS (with minor bug)
**Result:** Stayed on login page. Error message "Invalid credentials." displayed. User is not authenticated.
**Bug:** Error message is displayed twice - once via Flux alert component and once via the @error Blade directive. See `resources/views/livewire/admin/auth/login.blade.php` lines 11-13 where @error renders a second message below the Flux input's built-in validation display.
**Screenshot:** specs/screenshots/tc3-invalid-credentials.png

### TC-4: Storefront Root Page
**Steps:** Navigate to http://shop.test/
**Expected:** Welcome/storefront page renders or appropriate response.
**Status:** PASS
**Result:** Default Laravel welcome page renders with "Let's get started" content, "Log in" and "Register" links in the header. The storefront middleware resolves the store correctly (shop.test is seeded as the storefront domain). This is expected placeholder content for Phase 1.
**Screenshot:** specs/screenshots/tc5-after-logout.png (captured after logout redirect to /)

### TC-5: Admin Logout
**Steps:** After successful login, click user menu (bottom-left sidebar), click "Log Out".
**Expected:** Session ends, redirect to /admin/login.
**Status:** FAIL (functional logout works, but redirect is wrong)
**Result:** Session is successfully invalidated (user is logged out). However, the redirect goes to http://shop.test/ (storefront root) instead of http://shop.test/admin/login.
**Root Cause:** The sidebar user menu component (`resources/views/components/desktop-user-menu.blade.php` line 25) uses `route('logout')` which is the default Laravel Breeze logout route. It should use `route('admin.logout')` to hit the admin-specific logout handler that redirects to `/admin/login`.
**Screenshot:** specs/screenshots/tc5-after-logout.png

## Additional Finding: Auth Redirect Bug

**Issue:** Accessing /admin while unauthenticated redirects to /login (default Breeze login) instead of /admin/login.
**Steps to reproduce:** Log out, then navigate to http://shop.test/admin.
**Expected:** Redirect to http://shop.test/admin/login.
**Actual:** Redirect to http://shop.test/login (default Breeze login page, not the admin login).
**Root Cause:** The `auth` middleware's default redirect for unauthenticated users points to the default `login` route, not `admin.login`. This needs to be configured in `bootstrap/app.php` or via a custom middleware redirect.

## Bugs Found

| # | Severity | Description | File(s) |
|---|----------|-------------|---------|
| 1 | Low | Login error message displayed twice (Flux alert + @error directive) | `resources/views/livewire/admin/auth/login.blade.php` |
| 2 | Medium | Admin logout redirects to / instead of /admin/login | `resources/views/components/desktop-user-menu.blade.php` |
| 3 | Medium | Unauthenticated /admin access redirects to /login instead of /admin/login | `bootstrap/app.php` (auth middleware config) |

## Summary

| Test Case | Status | Notes |
|-----------|--------|-------|
| TC-1 | PASS | Login form renders correctly |
| TC-2 | PASS | Login succeeds, redirects to dashboard |
| TC-3 | PASS (minor bug) | Error shown but displayed twice |
| TC-4 | PASS | Storefront renders welcome page |
| TC-5 | FAIL | Logout works but redirects to wrong URL |
