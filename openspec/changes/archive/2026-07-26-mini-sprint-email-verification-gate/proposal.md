# Proposal: Email Verification Gate

## Intent

Turn existing non-blocking email-verification readiness into an enforced gate: users must verify email ownership before entering authenticated app areas, while keeping verification, resend, and logout flows available.

## Scope

### In Scope
- Email verification notice page for authenticated unverified users.
- Signed verification callback route and throttled resend route.
- Apply `verified` access to dashboard, organizers, account/profile, and account/password routes.
- Redirect newly registered unverified users to the notice page.
- Allow seeder/admin-created users to be pre-verified.
- Pest tests for unverified blocking, verified access, resend, logout availability, and registration redirect.

### Out of Scope
- Custom email templates beyond minimal Laravel notification behavior.
- Email change flow, MFA, and admin invitation flow.

## Capabilities

### New Capabilities
- `email-verification-gate`: Notice, callback, resend throttle, and verified-only access contract for authenticated app areas.

### Modified Capabilities
- `user-authentication`: Registration and verification change from non-blocking readiness to enforced app access gating.
- `account-ux`: Profile and password self-service require verified email; logout remains available.

## Approach

Use Laravel’s `MustVerifyEmail` infrastructure and `verified` middleware. Keep auth writes in controllers/actions, add thin verification views/routes, exempt notice/resend/callback/logout from the verified gate, and update tests that currently assert non-blocking access.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `routes/web.php` | Modified | Add verification routes and apply `verified` to app routes. |
| `app/Http/Controllers/Auth/` | New/Modified | Registration redirect plus resend/callback handling. |
| `resources/views/livewire/auth/` | New | Verification notice UI. |
| `database/seeders/`, `database/factories/UserFactory.php` | Modified | Support pre-verified seeded/admin users. |
| `tests/Feature/Auth/`, `tests/Feature/Account/`, `tests/Feature/Organizers/` | Modified | Cover gate behavior and updated registration flow. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Route loop for unverified users | Med | Keep notice/resend/callback/logout outside `verified`. |
| Existing tests expect non-blocking access | High | Replace assertions with explicit blocking/verified scenarios. |
| Seeded users locked out | Med | Mark seed/admin-created users verified where appropriate. |

## Rollback Plan

Remove verification routes/view, remove `verified` middleware from app routes, restore registration redirect, and revert updated tests/seed verification defaults.

## Dependencies

- Laravel email verification infrastructure and mail configuration for delivery outside tests.

## Success Criteria

- [ ] Unverified users can only access notice, resend, callback, and logout.
- [ ] Verified users can access dashboard, organizers, and account routes.
- [ ] Registration redirects unverified users to the verification notice.
- [ ] Resend is throttled and covered by tests.
