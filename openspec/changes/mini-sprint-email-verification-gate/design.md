# Design: Email Verification Gate

## Technical Approach

Use Laravel 12's built-in `MustVerifyEmail` flow already enabled on `App\Models\User`: add explicit verification routes, redirect registration to the notice page, and gate authenticated app areas with `verified`. Keep the current architecture: Volt for auth pages, controllers/actions for writes, Blade components/Tailwind for UI, Pest feature tests for route contracts.

## Architecture Decisions

| Option | Tradeoff | Decision |
|---|---|---|
| Laravel `EmailVerificationRequest` + `sendEmailVerificationNotification()` | Minimal custom code; depends on framework notification defaults | Chosen for notice, signed callback, and resend. |
| Controllers for verification endpoints vs route closures | More files but matches existing controller/action style | Create thin `VerifyEmailController` and `EmailVerificationNotificationController`. |
| Put `verified` on the outer `auth` group | Easy but causes notice/resend/logout loops | Keep verification/logout under `auth` only; nest app routes under `auth` + `verified`. |
| Factory verified by default with `unverified()` state | Tests stay mostly unchanged; explicit unverified scenarios remain clear | Keep current factory default and use `unverified()` for gate tests. |

## Data Flow

    POST /register -> RegisterController -> RegisterUserAction -> login user
           └-> redirect verification.notice
                  ├-> POST verification.send -> notification + back(status)
                  ├-> POST logout -> guest / (no verified middleware)
                  └-> GET signed verify -> fulfill() -> dashboard

    unverified authenticated app request -> verified middleware -> verification.notice
    verified authenticated app request   -> dashboard/account/organizers

## File Changes

| File | Action | Description |
|---|---|---|
| `routes/web.php` | Modify | Add `verification.notice`, `verification.verify`, `verification.send`; move dashboard/account/organizers into `['auth', 'verified']`; keep logout outside `verified`. |
| `app/Http/Controllers/Auth/RegisterController.php` | Modify | Redirect successful registration to `verification.notice` instead of `/`. |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | Create | Invoke `EmailVerificationRequest::fulfill()` and redirect to `dashboard` with status. |
| `app/Http/Controllers/Auth/EmailVerificationNotificationController.php` | Create | If already verified redirect dashboard; else send notification and return back with status. |
| `resources/views/livewire/auth/verify-email.blade.php` | Create | Volt auth-layout page with resend form and logout form, using existing `x-ui.*` components and Tailwind dark variants. |
| `database/factories/UserFactory.php` | Keep | Default remains verified; `unverified()` is the explicit test state. |
| `database/seeders/DatabaseSeeder.php` | Modify/Confirm | Ensure seeded login user is pre-verified via factory default or explicit `email_verified_at`. |
| `tests/Auth/AuthRouteRegistrar.php` | Modify | Register verification controller routes for isolated auth tests if still used. |
| `tests/Feature/Auth/EmailVerificationTest.php` | Create | Cover notice, callback, resend, throttle/security, logout availability. |
| `tests/Feature/Auth/RegisterTest.php` | Modify | Assert registration authenticates, leaves email unverified, redirects to notice, sends verification notification. |
| `tests/Feature/Account/*`, `tests/Feature/Organizers/*`, `tests/Feature/AdminLayoutTest.php` | Modify | Add/adjust route assertions for unverified redirects and verified access. |

## Interfaces / Contracts

Routes:

```php
GET  /email/verify              name: verification.notice middleware: auth
GET  /email/verify/{id}/{hash}  name: verification.verify middleware: auth,signed
POST /email/verification-notification name: verification.send middleware: auth,throttle:6,1
POST /logout                    name: logout middleware: web/auth only, not verified
```

Authenticated app routes requiring `verified`: `dashboard`, all `account.*`, all `organizers.*` including nested `organizers.team.*` with existing `organizer.detect` preserved inside the verified group.

## Testing Strategy

| Layer | What to Test | Approach |
|---|---|---|
| Feature | Unverified gate | `User::factory()->unverified()`, assert dashboard/account/organizers redirect to `verification.notice`. |
| Feature | Verified access | Default factory users access dashboard/account/organizers as existing role/policy setup allows. |
| Feature | Verification flows | `Notification::fake()`, registration/resend assert `VerifyEmail` notification; signed URL callback verifies user. |
| Feature | Security/edge cases | Invalid signature rejected, mismatched user cannot verify another account, resend throttles, already verified users redirect away from notice/resend. |
| Feature | Logout | Unverified authenticated users can `POST /logout` and become guests. |

## Migration / Rollout

No schema migration required. Existing unverified production users will be gated until they verify; seeded/admin users must be pre-verified to avoid lockout.

## Open Questions

- [ ] None.
