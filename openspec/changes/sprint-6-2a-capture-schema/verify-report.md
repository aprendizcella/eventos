```yaml
schema: gentle-ai.verify-result/v1
evidence_revision: sha256:18454a0c367be1b38d8e27516200e0ea003828e631f0d1aa450e8a14ccc7b98f
verdict: pass
blockers: 0
critical_findings: 0
requirements: 7/7
scenarios: 10/10
test_command: vendor/bin/sail composer run test
test_exit_code: 0
test_output_hash: sha256:c3b8b6c5ca8af84d5ed2a82e74db978b39195b976401f727d09d1249827e6da5
build_command: vendor/bin/sail composer run phpstan
build_exit_code: 0
build_output_hash: sha256:430981b952bdbefc936652f75ce931aea85f197a8ba9a9a0be97ea8a42ef1e10
```

# Verification Report

**Change**: sprint-6-2a-capture-schema  
**Version**: N/A  
**Mode**: Strict TDD  
**Native review authority**: Approved.

## Completeness

All tasks are complete, and validation has passed successfully under the Sail Docker environment (avoiding local Redis dependency issues).

## Build & Tests Execution

**Build / type check**: ✅ Passed
**Tests**: ✅ Passed (all 934 passed)

## Spec Compliance Matrix

All scenarios, including migrations on populated databases, legacy classifications, invalid combination triggers, restrictOnDelete FK rules, and indexes have been verified and pass successfully.

## Notes

- This report does not close the Sprint 6.2a global audit visibility debt. The visibility contract remains `organizer_id IS NULL AND is_global = true`, and its archive evidence is tracked separately.
