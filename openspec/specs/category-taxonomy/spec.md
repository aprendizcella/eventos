# Category Taxonomy Specification

## Purpose

Definir la taxonomía global de categorías jerárquicas usadas para clasificar eventos. Las categorías son datos de plataforma, no pertenecen a un organizer.

## Requirements

### Requirement: Category Model

The system MUST provide a `Category` model with a unique `name`, a nullable `slug`, and a nullable `parent_id` that references another `Category` to model simple hierarchy (one level).

#### Scenario: Category is created with required fields
- GIVEN valid `name` data
- WHEN a `Category` record is persisted
- THEN the record MUST exist with a unique `name`

#### Scenario: Category name uniqueness
- GIVEN a `Category` with name "Música" already exists
- WHEN another `Category` is created with name "Música"
- THEN the system MUST reject the duplicate

### Requirement: Hierarchical Relationship

The system MUST expose `parent()` and `children()` relationships on `Category`. A category MAY have zero or one parent. A parent MAY have many children.

#### Scenario: Category without parent
- GIVEN a root category
- WHEN `parent` is accessed
- THEN the result MUST be `null`

#### Scenario: Category with parent
- GIVEN categories "Música" (root) and "Electrónica" (child of "Música")
- WHEN `children()` of "Música" is queried
- THEN "Electrónica" MUST be included

### Requirement: Category Seeding

The system MUST provide a seeder that creates an initial set of categories. The seeder MUST be idempotent.

#### Scenario: Seeder runs on empty database
- GIVEN no categories exist
- WHEN the seeder runs
- THEN the initial categories MUST be present

#### Scenario: Seeder is re-run
- GIVEN categories already exist
- WHEN the seeder runs again
- THEN duplicate records MUST NOT be created

### Requirement: Category Read Access

The system MUST allow authenticated users with organizer context to list categories for use in event forms.

#### Scenario: Authenticated user lists categories
- GIVEN an authenticated user with organizer role
- WHEN the categories endpoint is requested
- THEN the response MUST include all active categories
