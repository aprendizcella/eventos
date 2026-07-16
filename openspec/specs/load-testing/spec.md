# Load Testing Specification

## Purpose

Provide a repeatable Artisan benchmark for the public catalog search path.

## Requirements

### Requirement: Catalog Benchmark

The system MUST provide `php artisan catalog:benchmark {count=100}`. The command MUST reject non-positive counts, seed the requested number of published public events, execute the catalog search twice, and report timing for both calls.

#### Scenario: Benchmark completes
- GIVEN a positive event count
- WHEN the benchmark command runs
- THEN it MUST seed that number of benchmark events
- AND it MUST report first-call and second-call timings
- AND it MUST exit successfully

#### Scenario: Invalid benchmark count
- GIVEN a count of zero or less
- WHEN the benchmark command runs
- THEN it MUST report a validation error
- AND it MUST exit with failure

## Testing Notes

Tests validate command output and exit status. Absolute throughput and latency thresholds are intentionally not asserted because they depend on the execution environment.
