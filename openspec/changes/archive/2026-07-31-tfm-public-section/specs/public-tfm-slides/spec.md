# Public TFM Slides Specification

## Purpose

Provide unauthenticated access to the two TFM presentation deliverables.

## Requirements

### Requirement: Public Slide Catalogue

The system MUST expose `/tfm/slides` without authentication and display exactly two presentation cards. Each card MUST provide an inline PDF preview, title, description, publication date, modification date, and a PPTX download destination.

#### Scenario: Visitor views the slide catalogue
- GIVEN an unauthenticated visitor requests `/tfm/slides`
- WHEN the catalogue is rendered
- THEN two presentation cards with their required metadata and PDF previews MUST be available
- AND each card MUST offer its PPTX download destination

#### Scenario: Visitor loads a PDF preview
- GIVEN a visitor views a presentation card
- WHEN the inline PDF preview is requested
- THEN the corresponding presentation PDF MUST be rendered inline

### Requirement: Approved PPTX Downloads

The system MUST provide public PPTX downloads only for the two approved TFM presentation filenames. A request for any other filename MUST return HTTP 404 and MUST NOT disclose or download a private file.

#### Scenario: Visitor downloads an approved presentation
- GIVEN a visitor requests the download destination for an approved filename
- WHEN the file is available
- THEN the system MUST return the corresponding PPTX download

#### Scenario: Visitor requests an unknown presentation
- GIVEN a visitor requests a download destination with an unapproved filename
- WHEN the request is processed
- THEN the system MUST return HTTP 404
