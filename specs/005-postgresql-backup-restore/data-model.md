# Data Model: Automated PostgreSQL Backup and Point-in-Time Recovery

## Entity: BackupArtifact
- **artifact_id**: UUID
- **artifact_type**: enum (logical, physical, wal)
- **filename**: string
- **size_bytes**: integer
- **checksum_md5**: string
- **checksum_sha256**: string
- **created_at**: timestamp
- **encrypted**: boolean
- **encryption_key_id**: string (nullable)
- **retention_until**: timestamp

## Entity: BackupJob
- **job_id**: UUID
- **job_type**: enum (scheduled, manual, validation)
- **status**: enum (running, completed, failed)
- **started_at**: timestamp
- **completed_at**: timestamp (nullable)
- **error_message**: string (nullable)
- **artifact_id**: UUID (FK to BackupArtifact)

## Entity: WALSegment
- **segment_id**: UUID
- **segment_name**: string (e.g., 000000010000000000000001)
- **timeline**: integer
- **start_lsn**: string
- **end_lsn**: string
- **size_bytes**: integer
- **archived_at**: timestamp
- **retention_until**: timestamp

## Entity: RestorePoint
- **point_id**: UUID
- **point_type**: enum (full, pitr)
- **target_timestamp**: timestamp (nullable, for PITR)
- **artifact_id**: UUID (FK to BackupArtifact)
- **status**: enum (requested, in_progress, completed, failed)
- **started_at**: timestamp
- **completed_at**: timestamp (nullable)
- **validated**: boolean

## Entity: ValidationResult
- **result_id**: UUID
- **restore_point_id**: UUID (FK to RestorePoint)
- **table_name**: string
- **validation_type**: enum (row_count, checksum, uniqueness, integrity)
- **expected_value**: string
- **actual_value**: string
- **passed**: boolean
- **checked_at**: timestamp

## Entity: OffsiteReplication
- **replication_id**: UUID
- **artifact_id**: UUID (FK to BackupArtifact)
- **remote_location**: string (e.g., s3://bucket/path)
- **status**: enum (pending, in_progress, completed, failed)
- **started_at**: timestamp
- **completed_at**: timestamp (nullable)
- **checksum_verified**: boolean

## Relationships
- BackupArtifact (1) -> (*) BackupJob (one artifact has many jobs)
- BackupArtifact (1) -> (*) WALSegment (logical backup references WAL)
- BackupArtifact (1) -> (*) RestorePoint (restores use artifacts)
- RestorePoint (1) -> (*) ValidationResult (validations per restore)
- BackupArtifact (1) -> (*) OffsiteReplication (replications per artifact)
