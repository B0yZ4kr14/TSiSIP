# TSiSIP rclone Configuration — S3-Compatible Storage
# Rendered from template at container startup via envsubst
#
# Socratic Decision Log:
# Q: Is S3 the right target, or should we support SFTP/RSYNC?
# A: S3-compatible (MinIO) is canonical for TSiSIP because it provides
#    API-driven offsite storage with encryption-at-rest. SFTP/RSYNC are
#    valid but require persistent SSH connections and key management.
# Hypothesis: "rclone can sync encrypted backups to S3/MinIO"
# Falsification test: rclone ls remote:bucket shows backup files
#
# Required environment variables:
#   S3_ENDPOINT        - URL of the S3-compatible API
#                        (default: http://minio.tsihomelab.tailscale:9000)
#   S3_ACCESS_KEY      - Access key ID for the S3 service
#   S3_SECRET_KEY      - Secret access key for the S3 service
#   S3_BUCKET          - Target bucket name (default: tsisip-backups)
#   S3_REGION          - Region identifier (default: us-east-1)
#   S3_PROVIDER        - rclone S3 provider name (default: Minio)
#
# Optional environment variables:
#   RCLONE_S3_LOCATION_CONSTRAINT - Location constraint for bucket creation
#
# Security notes:
# - This file is rendered inside the container; do NOT commit credentials.
# - Use Docker secrets or environment injection only.
# - ACL is set to private; bucket policies should enforce encryption-at-rest.

[remote]
type = s3
provider = ${RCLONE_S3_PROVIDER:-Minio}
env_auth = false
access_key_id = ${RCLONE_S3_ACCESS_KEY:-}
secret_access_key = ${RCLONE_S3_SECRET_KEY:-}
region = ${RCLONE_S3_REGION:-us-east-1}
endpoint = ${RCLONE_S3_ENDPOINT:-http://minio.tsihomelab.tailscale:9000}
location_constraint = ${RCLONE_S3_LOCATION_CONSTRAINT:-}
acl = private
