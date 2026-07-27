# Public Site Navigation Specification

## Purpose

Provide accessible public navigation that keeps event discovery primary and accurately introduces the platform.

## Requirements

### Requirement: Public Navigation Destinations

The system MUST expose the brand, Discover Events, Features, Docs, an external GitHub link, theme control, and the appropriate guest or authenticated action in public navigation. Discover Events MUST return to `/`; GitHub MUST target `https://github.com/aprendizcella/eventos`.

#### Scenario: Guest navigates on a desktop viewport
- GIVEN a guest viewing a public page on a desktop viewport
- WHEN public navigation is rendered
- THEN all public destinations and Login MUST be available
- AND the GitHub destination MUST be identified as external

#### Scenario: Authenticated user navigates
- GIVEN an authenticated user viewing a public page
- WHEN public navigation is rendered
- THEN Dashboard MUST be available instead of Login

### Requirement: Accessible Responsive Navigation

The system MUST provide equivalent public destinations on desktop and mobile viewports. The mobile menu MUST be keyboard operable, expose its state, and manage focus without obscuring the existing persisted light, dark, or system theme behavior.

#### Scenario: Keyboard user operates the mobile menu
- GIVEN a keyboard user on a mobile viewport
- WHEN the user opens and closes public navigation
- THEN its destinations MUST be reachable while open
- AND focus MUST return to the menu control when closed

#### Scenario: Visitor uses a persisted theme
- GIVEN a visitor with a saved theme preference
- WHEN public navigation is rendered on either viewport
- THEN the saved preference MUST remain effective

### Requirement: Verified Feature Communication

The system MUST provide Features content after discovery results, separated into attendee and organizer audiences. The content MUST describe only verified platform capabilities and MUST NOT imply marketplace positioning, native applications, integrations, or deferred documentation.

#### Scenario: Visitor reviews Features
- GIVEN a visitor reaches the Features destination
- WHEN the anchored content is shown
- THEN attendee and organizer capabilities MUST be distinguishable
- AND event discovery MUST remain the first substantive home interaction

### Requirement: Public Footer

The system MUST provide a public footer that offers access to the available public destinations without changing their behavior.

#### Scenario: Visitor uses the footer
- GIVEN a visitor reaches the end of a public page
- WHEN the footer is rendered
- THEN available public destinations MUST remain accessible
