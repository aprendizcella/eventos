# Sprint 1.2 — Tasks Breakdown

## Phase 1: Database & Configuration (PR 1 - COMPLETE)
- [x] 1.1 Create organizers table migration
- [x] 1.2 Create organizer_user pivot table migration
- [x] 1.3 Add Spatie teams columns migration
- [x] 1.4 Enable Spatie teams in config/permission.php
- [x] 1.5 Update RoleSeeder to remove global organizer_* roles

## Phase 2: Models & Relationships (PR 1 - COMPLETE)
- [x] 2.1 Create Organizer model with relationships, scopes, casts
- [x] 2.2 Update User model with organizers() relationship and currentOrganizer()

## Phase 3: Actions & DTOs (PR 2 - CURRENT)
- [ ] 3.1 Create Organizer Actions and DTOs
  - CreateOrganizerAction + CreateOrganizerDto
  - UpdateOrganizerAction + UpdateOrganizerDto
  - DeleteOrganizerAction
  - AddTeamMemberAction + AddTeamMemberDto
  - RemoveTeamMemberAction
  - ChangeTeamMemberRoleAction + ChangeTeamMemberRoleDto
  - All actions transactional with activity logging (organizer_id)
  - Last-admin guard in RemoveTeamMember and ChangeTeamMemberRole

## Phase 4: FormRequests & Controllers (PR 2 - CURRENT)
- [ ] 4.1 Create FormRequests and Controllers
  - CreateOrganizerRequest, UpdateOrganizerRequest
  - AddTeamMemberRequest, ChangeTeamMemberRoleRequest
  - OrganizerController (index, create, store, edit, update, destroy)
  - TeamController (index, store, update, destroy)
  - Thin controllers delegate to actions

## Phase 5: Middleware & Policies (PR 2 - CURRENT)
- [ ] 5.1 Create Middleware and Policies
  - DetectCurrentOrganizer middleware
  - OrganizerPolicy (view, create, update, delete, manageTeam)
  - Register middleware alias in bootstrap/app.php

## Phase 6: UI Components (PR 3 - DEFERRED)
- [ ] 6.1 Livewire/Volt components for organizer CRUD
- [ ] 6.2 Livewire/Volt components for team management
- [ ] 6.3 Sidebar integration and navigation

## Phase 7: Integration Tests (PR 3 - DEFERRED)
- [ ] 7.1 End-to-end feature tests
- [ ] 7.2 Authorization matrix tests
- [ ] 7.3 Cross-organizer isolation tests

## Phase 8: Migration & Rollout (PR 3 - DEFERRED)
- [ ] 8.1 Legacy role migration command
- [ ] 8.2 Rollout documentation
