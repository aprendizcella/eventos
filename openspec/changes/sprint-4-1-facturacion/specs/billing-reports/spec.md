# Billing Reports Specification

## Purpose

Provide read-only billing summaries for income, taxes, and platform fees with export support.

## Requirements

### Requirement: Income Summary

The system MUST display a revenue summary grouped by date and currency.

#### Scenario: Organizer opens income report

- GIVEN an organizer with paid orders
- WHEN the income report is opened
- THEN the system MUST show the summary table

### Requirement: Tax Summary

The system MUST display tax totals collected for the selected scope.

#### Scenario: Tax report is filtered

- GIVEN a date range filter
- WHEN the tax report is requested
- THEN the system MUST return the filtered tax totals

### Requirement: Platform Fee Summary

The system MUST display platform-fee totals and allow CSV export.

#### Scenario: Export platform-fee report

- GIVEN a populated fee report
- WHEN the user clicks export
- THEN the system MUST return a CSV file
