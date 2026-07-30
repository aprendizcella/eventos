# Public TFM Videos Specification

## Purpose

Provide unauthenticated access to the TFM presentation video.

## Requirements

### Requirement: Public Video Catalogue

The system MUST expose `/tfm/videos` without authentication and display one TFM video card with its title and description. The card MUST render the video through an embedded `youtube-nocookie.com` player.

#### Scenario: Visitor views the video catalogue
- GIVEN an unauthenticated visitor requests `/tfm/videos`
- WHEN the catalogue is rendered
- THEN one video card with its title, description, and embedded player MUST be available

#### Scenario: Visitor loads the embedded video
- GIVEN a visitor views the video card
- WHEN the player is rendered
- THEN its embed source MUST use `youtube-nocookie.com`
