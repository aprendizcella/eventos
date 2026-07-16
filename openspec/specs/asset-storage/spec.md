# Asset Storage Specification

## Purpose

Enable scalable object storage through an S3-compatible adapter while keeping local development usable.

## Requirements

### Requirement: S3-Compatible Object Storage

The system MUST include the Flysystem S3 adapter and configure an S3-compatible disk that supports MinIO locally and S3 in production. Local storage MUST remain the default unless explicitly configured otherwise.

#### Scenario: Local development uses local storage
- GIVEN no S3 disk is selected
- WHEN the application resolves its default filesystem
- THEN it MUST use the local disk

#### Scenario: S3-compatible storage is selected
- GIVEN the S3 disk is selected
- WHEN an asset is written and read through that disk
- THEN the operation MUST use the configured S3-compatible adapter

## Testing Notes

Tests use Laravel's storage fake to verify the disk operation without requiring a live MinIO or S3 service. CDN delivery is not part of this capability.
