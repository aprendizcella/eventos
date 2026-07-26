# Design: Mini-Sprint Account UX

## Technical Approach

Keep account self-service in the existing Laravel Action/FormRequest/Controller style rather than moving business writes into Volt. Volt remains suitable for rendering auth pages already mounted in `routes/web.php`; profile and password updates should be plain authenticated routes because they mutate persistent user data and need explicit request validation. The topbar stays Blade/Alpine and reuses the existing `POST /logout` route.

## Architecture Decisions

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Controllers + FormRequests + Actions vs Volt-only account forms | More files, but matches current auth/organizer mutation patterns and keeps validation/test seams clean. | Use `AccountController` plus `UpdateProfileRequest`, `UpdatePasswordRequest`, and account actions. |
| Separate profile/password pages vs one combined account page | Separate pages reduce validation coupling and make auth boundaries clearer. | Create `/account/profile` and `/account/password`. |
| Resolve role/organizer in Blade vs a small view helper/service | Inline Blade can create N+1/fragile logic; a helper keeps display logic cheap. | Add an account context resolver that uses loaded/current relations and simple fallbacks. |
| Remember-me in request DTO vs controller-only boolean | DTO keeps `LoginUserAction` the single auth boundary. | Add `remember` to `LoginUserDto` and pass it to `StatefulGuard::attempt(..., $dto->remember)`. |

## Data Flow

```text
Login form -> LoginUserRequest -> LoginUserDto(remember) -> LoginUserAction -> StatefulGuard::attempt

Topbar Blade -> AccountContextResolver -> authenticated User / currentOrganizer() / roles -> Alpine dropdown

Profile form -> UpdateProfileRequest -> UpdateProfileAction -> users.name
Password form -> UpdatePasswordRequest(current_password) -> UpdatePasswordAction -> users.password hash
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `routes/web.php` | Modify | Add authenticated `account.profile.edit/update` and `account.password.edit/update` routes. |
| `app/Http/Controllers/Account/AccountController.php` | Create | Render account pages and delegate mutations. |
| `app/Http/Requests/Account/UpdateProfileRequest.php` | Create | Validate only `name`; do not accept `email`. |
| `app/Http/Requests/Account/UpdatePasswordRequest.php` | Create | Validate `current_password`, `password` confirmation, and password defaults/minimum. |
| `app/Actions/Account/UpdateProfileAction.php` | Create | Update name only. |
| `app/Actions/Account/UpdatePasswordAction.php` | Create | Update password with `Hash::make()` or model hashed cast; call `Hash::check()` via validation/current password rule. |
| `app/Support/Account/AccountContextResolver.php` | Create | Resolve display name, global role, current organizer, organizer role with fallbacks. |
| `resources/views/account/profile.blade.php` | Create | Authenticated profile form with editable name and read-only/disabled email. |
| `resources/views/account/password.blade.php` | Create | Password change form using reusable password components. |
| `resources/views/components/navigation/topbar.blade.php` | Modify | Add Alpine `x-data`, dropdown trigger, Profile link, role/organizer labels, and logout form. |
| `resources/views/livewire/auth/login.blade.php` | Modify | Add unchecked `x-form.checkbox` named `remember`. |
| `app/DataTransferObjects/Auth/LoginUserDto.php`, `LoginUserRequest.php`, `LoginUserAction.php` | Modify | Carry and apply remember-me boolean. |
| `tests/Feature/Account/AccountProfileTest.php`, `AccountPasswordTest.php`, `AccountTopbarTest.php` | Create | Cover boundaries and account UX. |
| `tests/Feature/Auth/LoginTest.php`, `AuthUiTest.php` | Modify | Cover remember-me behavior and checkbox rendering. |

## Interfaces / Contracts

```php
final readonly class AccountContext
{
    public function __construct(
        public string $roleLabel,
        public string $organizerLabel,
    ) {}
}
```

Resolver rules: prefer global Spatie role (`super_admin`, `platform_admin`, `attendee`) for role label; if a current organizer exists, display its name and pivot role name by `role_id`; otherwise use `No role assigned` and `No organizer selected`. Do not query all organizers from the topbar.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit/Feature | Profile name update and email immutability | Pest feature tests with authenticated user; assert DB unchanged for email spoof. |
| Unit/Feature | Password update security | Assert wrong current password fails; new password is hashed and authenticates. |
| Integration | Topbar/dropdown | Render dashboard as users with/without roles/organizer; assert labels, Profile link, logout form. |
| Integration | Remember me | Post login with and without `remember`; assert `remember_token` behavior/cookie presence where framework exposes it. |
| Boundary | Guest access | Assert account routes redirect to `/login`. |

## Migration / Rollout

No migration required. Roll out as authenticated-only routes and Blade changes. Existing logout and password reset flows remain unchanged.

## Open Questions

- [ ] Requested external skill files under `/Users/aprendizcella/.agents/skills/` were not present in this environment; design follows repository code and OpenSpec rules instead.
