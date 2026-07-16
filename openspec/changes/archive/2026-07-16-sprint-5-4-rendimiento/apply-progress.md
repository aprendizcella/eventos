# Apply Progress: Sprint 5.4 — Performance & Scaling

## TDD Cycle Evidence

| Task | RED (Test Written) | GREEN (Implementation Passes) | REFACTOR |
|---|---|---|---|
| 1.1 Add dependencies | N/A (deps) | ✅ | ✅ |
| 1.2 Update .env.example, config/cache.php | N/A (config) | ✅ | ✅ |
| 1.3 Register health checks | ✅ | ✅ | ✅ |
| 2.1 Create migration for catalog indexes | ✅ | ✅ | ✅ |
| 2.2 Cache EventSearchService | ✅ | ✅ | ✅ |
| 2.3 Model flush hooks | ✅ | ✅ | ✅ |
| 2.4 Fix N+1 queries | ✅ | ✅ | ✅ |
| 2.5 Benchmark command | ✅ | ✅ | ✅ |
| 3.1 EventSearchCacheTest | ✅ | ✅ | ✅ |
| 3.2 HealthEndpointTest | ✅ | ✅ | ✅ |
| 3.3 CatalogBenchmarkCommandTest | ✅ | ✅ | ✅ |
| 4.1 Run tools and Pest suite | ✅ | ✅ | ✅ |
| 4.2 Verified alignment with SDD artifacts | ✅ | ✅ | ✅ |

## Phase 1: Foundation / Infrastructure
- [x] 1.1 Add dependencies
- [x] 1.2 Update .env.example, config/cache.php
- [x] 1.3 Register health checks

## Phase 2: Core Implementation
- [x] 2.1 Create migration for catalog indexes
- [x] 2.2 Cache EventSearchService
- [x] 2.3 Model flush hooks
- [x] 2.4 Fix N+1 queries
- [x] 2.5 Benchmark command

## Phase 3: Testing / Verification
- [x] 3.1 EventSearchCacheTest
- [x] 3.2 HealthEndpointTest
- [x] 3.3 CatalogBenchmarkCommandTest

## Phase 4: Cleanup / Documentation
- [x] 4.1 Run tools and Pest suite
- [x] 4.2 Verified alignment with SDD artifacts

## Remediation: Sprint 5.4 Alignment Gaps

- [x] Configure HTTP 503 for failed JSON health results while preserving `?fresh=1`.
- [x] Add reversible `down()` implementation to the health-history migration.
- [x] Strengthen cache-hit, benchmark-output, S3 fake-operation, and catalog-index tests.
- [x] Restore archived delta specs and synchronize global specifications.
- [x] Correct S3/MinIO, CDN deferral, cursor pagination deferral, and query-plan limitation documentation.
- [x] Run focused tests, full `composer qa`, and `git diff --check`.
