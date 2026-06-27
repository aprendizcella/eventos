# Proposal: Mini-Sprint Account UX

## Intent

Make account self-service visible and safe: users can find account actions from the topbar, update their name, change their password, sign out, and opt into long-lived sessions only when they explicitly choose “Remember me”.

## Scope

### In Scope
- Add an authenticated topbar account dropdown with current user, role, organizer, Profile link, and Sign out action.
- Add a Profile page with editable name and read-only email.
- Add an authenticated change-password form with validation and successful update feedback.
- Add an opt-in Remember me checkbox to login; unchecked remains the default.
- Add Pest coverage for routes, auth/permission boundaries, validation, successful updates, logout menu rendering, and remember-me behavior.

### Out of Scope
- Email change flow; future email changes must trigger re-verification.
- Avatar upload, except as a future-ready UI extension point.
- Notification preferences, multi-factor auth, and account deletion.

## Capabilities

### New Capabilities
- `account-ux`: account dropdown, profile self-service, password change, and visible logout UX for authenticated users.
- `session-authentication`: explicit remember-me behavior during web login.

### Modified Capabilities
- None; no active `openspec/specs/` capability specs are present.

## Approach

Use existing Laravel/Volt routes and reusable Blade form components. Keep implementation strings in English. Add authenticated routes/controllers/actions or Volt views for profile/password updates, reuse `POST /logout`, and extend login to pass remember-me only when the checkbox is checked.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `routes/web.php` | Modified | Add authenticated account/profile/password routes. |
| `resources/views/components/navigation/topbar.blade.php` | Modified | Add accessible dropdown and logout form. |
| `resources/views/livewire/auth/login.blade.php` | Modified | Add unchecked Remember me checkbox. |
| `app/Http/Controllers/Account/`, `app/Actions/Account/` | New | Handle profile and password updates. |
| `resources/views/account/` or Volt account views | New | Profile and password UI. |
| `tests/Feature/` | New/Modified | Cover account UX behavior and auth boundaries. |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Email appears editable by mistake | Med | Render as read-only text/disabled field and test it. |
| Remember me defaults to long-lived sessions | Med | Default checkbox to false and test unchecked login. |
| Role/organizer context is ambiguous | Med | Display current resolved context only; use fallback labels when absent. |

## Rollback Plan

Revert the mini-sprint commits: remove account routes, views, actions/controllers, login checkbox changes, topbar dropdown changes, and related tests. Existing password reset and `POST /logout` remain intact.

## Dependencies

- Existing auth, roles, organizer context, and reusable form components.
- Product assumption: account pages are authenticated-only and email stays immutable in this slice.

## Success Criteria

- [ ] Authenticated users can open the topbar menu, see name/role/organizer, navigate to Profile, and sign out.
- [ ] Users can update name but cannot edit email.
- [ ] Users can change password after validation.
- [ ] Remember me is opt-in only.
- [ ] Pest coverage proves routes, auth, validation, successful updates, and menu rendering.
