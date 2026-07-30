# Delta for Public Site Navigation

## MODIFIED Requirements

### Requirement: Public Navigation Destinations

The system MUST expose the brand, Discover Events, Features, Docs, a TFM destination with Slides and Videos sub-items, an external GitHub link, theme control, and the appropriate guest or authenticated action in public navigation. Discover Events MUST return to `/`; GitHub MUST target `https://github.com/aprendizcella/eventos`; the TFM destination MUST appear between Docs and GitHub.
(Previously: Public navigation exposed Docs followed directly by the external GitHub link, with no TFM destination.)

#### Scenario: Guest navigates on a desktop viewport
- GIVEN a guest viewing a public page on a desktop viewport
- WHEN public navigation is rendered
- THEN all public destinations, TFM Slides, TFM Videos, and Login MUST be available
- AND the GitHub destination MUST be identified as external

#### Scenario: Authenticated user navigates
- GIVEN an authenticated user viewing a public page
- WHEN public navigation is rendered
- THEN Dashboard MUST be available instead of Login

### Requirement: Accessible Responsive Navigation

The system MUST provide equivalent public destinations, including TFM Slides and TFM Videos, on desktop and mobile viewports. The mobile menu MUST be keyboard operable, expose its state, and manage focus without obscuring the existing persisted light, dark, or system theme behavior.
(Previously: Equivalent destinations did not include the TFM sub-items.)

#### Scenario: Keyboard user operates the mobile menu
- GIVEN a keyboard user on a mobile viewport
- WHEN the user opens and closes public navigation
- THEN its destinations, including TFM Slides and TFM Videos, MUST be reachable while open
- AND focus MUST return to the menu control when closed

#### Scenario: Visitor uses a persisted theme
- GIVEN a visitor with a saved theme preference
- WHEN public navigation is rendered on either viewport
- THEN the saved preference MUST remain effective

### Requirement: Public Footer

The system MUST provide a public footer that offers access to the available public destinations, including TFM Slides and TFM Videos, without changing their behavior.
(Previously: The footer did not offer TFM destination links.)

#### Scenario: Visitor uses the footer
- GIVEN a visitor reaches the end of a public page
- WHEN the footer is rendered
- THEN available public destinations, including TFM Slides and TFM Videos, MUST remain accessible
