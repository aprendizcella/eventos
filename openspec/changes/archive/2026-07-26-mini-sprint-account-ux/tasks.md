# Tasks: Mini-Sprint Account UX

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 260-380 |
| 400-line budget risk | Medium |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | auto-chain |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Account UX foundation and auth wiring | PR 1 | Routes, DTO/request plumbing, resolver, tests included |
| 2 | Account self-service UI and validation | PR 1 | Topbar, profile/password pages, login checkbox, tests included |

## Phase 1: Foundation / Test Seams

- [x] 1.1 Add failing Pest tests for guest redirect and authenticated access to `account.profile.edit` / `account.password.edit` in `tests/Feature/Account/`.
- [x] 1.2 Add failing feature tests for topbar dropdown rendering, fallback labels, and logout link in `tests/Feature/Account/AccountTopbarTest.php`.
- [x] 1.3 Add failing login tests for unchecked-by-default remember me and checked remember-me submission in `tests/Feature/Auth/LoginTest.php`.

## Phase 2: Core Account Mutations

- [x] 2.1 Create `app/Support/Account/AccountContextResolver.php` and `AccountContext` to resolve display name, role label, and organizer label with fallbacks.
- [x] 2.2 Create `app/Http/Controllers/Account/AccountController.php` plus `UpdateProfileRequest` and `UpdatePasswordRequest` for authenticated profile/password routes.
- [x] 2.3 Create `app/Actions/Account/UpdateProfileAction.php` to update only `users.name`; keep `email` out of the write path.
- [x] 2.4 Create `app/Actions/Account/UpdatePasswordAction.php` to hash the new password after current-password validation.

## Phase 3: UI Wiring / Test-First Completion

- [x] 3.1 Update `routes/web.php` with `/account/profile` and `/account/password` auth-only routes and success redirects/messages.
- [x] 3.2 Update `resources/views/components/navigation/topbar.blade.php` with the account dropdown, Profile link, role/organizer labels, and `POST /logout` form.
- [x] 3.3 Update `resources/views/livewire/auth/login.blade.php` and auth DTO/request/action files to pass `remember` only when checked.
- [x] 3.4 Create `resources/views/account/profile.blade.php` and `resources/views/account/password.blade.php` with read-only email, name edit, and password confirmation fields.

## Phase 4: Verification / Cleanup

- [x] 4.1 Add/finish profile tests for name update success, validation failure, and email immutability in `tests/Feature/Account/AccountProfileTest.php`.
- [x] 4.2 Add/finish password tests for current-password failure, confirmation mismatch, minimum rules, and successful update in `tests/Feature/Account/AccountPasswordTest.php`.
- [x] 4.3 Add/finish login UI tests for remember-me checkbox rendering and preserved state after validation failure in `tests/Feature/Auth/AuthUiTest.php`.
- [x] 4.4 Run `vendor/bin/sail composer run test`, then `vendor/bin/sail composer run pint -- --test` and `vendor/bin/sail composer run phpstan` to verify the slice.
