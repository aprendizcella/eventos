# Tasks: Email Verification Gate

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 260-360 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | auto-forecast |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Ship the verification gate end-to-end | PR 1 | Base branch only; keep notice/resend/callback/logout + route gating together |
| 2 | Stabilize existing auth/account coverage | PR 1 | Fold in updated feature tests and seeded/admin user assertions |

## Phase 1: RED — Gate Contract Tests

- [x] 1.1 Add `tests/Feature/Auth/EmailVerificationTest.php` covering notice render, resend, callback, throttle, and logout access.
- [x] 1.2 Update `tests/Feature/Auth/RegisterTest.php` to expect redirect to `verification.notice` after registration.
- [x] 1.3 Update route tests in `tests/Feature/Account/*`, `tests/Feature/Organizers/*`, and `tests/Feature/AdminLayoutTest.php` for unverified redirects vs verified access.

## Phase 2: GREEN — Verification Flow Implementation

- [x] 2.1 Add `app/Http/Controllers/Auth/VerifyEmailController.php` and `EmailVerificationNotificationController.php` for fulfill/send/back behavior.
- [x] 2.2 Add `resources/views/livewire/auth/verify-email.blade.php` with resend form, logout form, and verification notice UI.
- [x] 2.3 Update `app/Http/Controllers/Auth/RegisterController.php` to redirect newly registered users to `verification.notice`.

## Phase 3: GREEN — Route and Auth Wiring

- [x] 3.1 Update `routes/web.php` to register `verification.notice`, `verification.verify`, `verification.send`, and keep `logout` outside `verified`.
- [x] 3.2 Move `dashboard`, `account.*`, and `organizers.*` under `['auth', 'verified']` while preserving existing organizer detection behavior.
- [x] 3.3 Ensure `database/seeders/DatabaseSeeder.php` creates pre-verified seeded/admin users and keep `UserFactory::unverified()` available for gate tests.

## Phase 4: REFACTOR — Verify and Align Coverage

- [x] 4.1 Adjust affected existing tests to reflect the new gate without weakening assertions or duplicating route setup.
- [x] 4.2 Run the full Pest suite and fix any regressions in auth, account, or organizer route expectations.
- [x] 4.3 Confirm no verified-only route leaks remain and document any test data assumptions inline.
