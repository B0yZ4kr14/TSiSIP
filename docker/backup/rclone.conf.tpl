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
