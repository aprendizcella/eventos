# Platform Admin Backoffice Architecture

This document describes the design, authorization model, and lifecycle management for the platform backoffice implemented in Sprint 6.1.

## Quick Overview

The platform backoffice provides global visibility and moderation capabilities for `super_admin` and `platform_admin` roles. It ensures strict isolation from tenant-level operations through a dedicated middleware and a hierarchical role matrix.

## Authorization and Global Context

| Role | Global Role Management | Organizer-Scoped Role Management | Read All Data |
|------|-------------------------|-----------------------------------|---------------|
| `super_admin` | ✅ Grant/revoke | ✅ (via existing team mgmt) | ✅ |
| `platform_admin` | ❌ 403 Forbidden | ✅ (via existing team mgmt) | ✅ |

### The `team_id: 0` Strategy
Spatie Laravel Permission is scoped by `team_id` (mapped to `organizer_id` in this project). To prevent ambient tenant leakage into admin actions:
- Global roles are assigned to `team_id: 0`.
- The `EnsureGlobalAdminContext` middleware (aliased as `global.admin`) intercepts admin requests, calls `setPermissionsTeamId(0)`, and restores the previous context in the `terminate()` phase.
- **Rule**: All `/admin` web routes and `/api/v1/admin` API routes must use this middleware.

## User Lifecycle and Deletion Deferral

User moderation is handled via a reversible suspension mechanism rather than hard deletion.

- **Suspend**: Deletes active Sanctum tokens, sets `suspended_at`, and logs the activity.
- **Restore**: Clears the `suspended_at` timestamp.
- **Protection**: The last active `super_admin` cannot be suspended or deleted.
- **GDPR Deferral**: Full GDPR deletion and anonymization are explicitly deferred/out of scope for Sprint 6.1. No API endpoints or actions exist for hard deletion, ensuring data retention policies can be properly designed in future sprints.

## Reversible Event Moderation

Events can be suspended by platform admins for terms of service violations.

### Mechanism
- When an event is suspended, its current status (`Draft`, `Published`, or `Paused`) is stored in the `previous_status` column.
- The status is set to `Suspended`.
- A mandatory `reason` is required and logged alongside the `causer_id` (the admin).
- Restoring the event moves it back to its `previous_status`.

### Side Effects
- **Visibility**: Suspended events are immediately excluded from public catalog scopes, search indexing (Scout), and organizer dashboards.
- **Financial**: Suspension does **not** automatically trigger refunds, alter existing payouts, or modify payment intents. Financial mediation remains a manual process or is handled by a separate billing engine.

## Platform Settings and Commission Fallback

A singleton `PlatformSetting` model manages global defaults (like commission rates) using a JSON schema and optimistic locking (`lock_version`).

### Commission Resolution Chain
When a payout is created, the commission rate is resolved in this exact order:
1. **Organizer Setting**: Specific rate negotiated with the organizer.
2. **Platform Setting**: Global rate defined in the backoffice.
3. **Hardcoded Default**: Fallback from `config('tickets.commission_default')`.

*(Note: Explicitly setting a rate to `0` at the organizer or platform level is honored and stops the fallback chain).*

### Historical Immutability
Payouts take a snapshot of the commission rate at creation time. Updating the platform settings only affects **future** payouts. Existing historical payouts remain immutable.

## Admin API Scope

The Admin API (`/api/v1/admin/*`) is a Sanctum-authenticated, rate-limited (`60,1`) JSON API.

- **Available endpoints**:
  - `GET /users` (paginated list)
  - `GET /users/{user}` (detail view)
  - `POST /users/{user}/suspend`
  - `POST /users/{user}/restore`
  - `GET /events` (paginated list across all organizers)
  - `POST /events/{event}/suspend`
  - `POST /events/{event}/restore`

*Dedicated audit log viewing and MFA management are deferred to future iterations.*
