# Data Model: End-to-End Encryption for SIP Signaling (TLS) and Media (SRTP)

## Entity: Certificate
- **cert_id**: UUID
- **cert_type**: enum (root_ca, intermediate_ca, server, client)
- **subject_cn**: string
- **subject_alt_names**: JSON array
- **serial_number**: string
- **issuer_id**: UUID (FK to Certificate, nullable for root CA)
- **not_before**: timestamp
- **not_after**: timestamp
- **key_algorithm**: enum (rsa_4096, ecdsa_p256)
- **signature_algorithm**: string (e.g., sha256WithRSAEncryption)
- **status**: enum (active, expired, revoked, staging)
- **pem_content**: text (encrypted at rest)

## Entity: TLSProfile
- **profile_id**: UUID
- **profile_name**: string
- **listen_address**: string
- **listen_port**: integer
- **tls_version_min**: enum (tls1_2, tls1_3)
- **tls_version_max**: enum (tls1_2, tls1_3)
- **cipher_list**: string
- **verify_client**: boolean
- **require_client_cert**: boolean
- **server_cert_id**: UUID (FK to Certificate)
- **ca_cert_id**: UUID (FK to Certificate)
- **crl_file**: string

## Entity: TLSSession
- **session_id**: UUID
- **profile_id**: UUID (FK to TLSProfile)
- **client_ip**: string
- **client_port**: integer
- **client_cert_id**: UUID (FK to Certificate, nullable)
- **tls_version**: string
- **cipher_suite**: string
- **established_at**: timestamp
- **closed_at**: timestamp (nullable)
- **bytes_encrypted**: integer
- **bytes_decrypted**: integer

## Entity: SRTPSession
- **srtp_id**: UUID
- **call_id**: string
- **rtpengine_tag**: string
- **ssrc_local**: integer
- **ssrc_remote**: integer
- **cipher**: enum (aes_gcm_256, aes_cm_256, aes_cm_128)
- **key_exchange**: enum (sdes, dtls)
- **local_key**: string (encrypted)
- **remote_key**: string (encrypted)
- **established_at**: timestamp
- **terminated_at**: timestamp (nullable)

## Entity: RevocationEntry
- **revocation_id**: UUID
- **cert_id**: UUID (FK to Certificate)
- **revoked_at**: timestamp
- **reason**: enum (unspecified, key_compromise, ca_compromise, superseded, cessation)
- **crl_entry_serial**: string

## Entity: CipherNegotiation
- **negotiation_id**: UUID
- **session_id**: UUID (FK to TLSSession or SRTPSession)
- **protocol**: enum (tls, srtp)
- **client_proposed**: JSON array
- **server_selected**: string
- **negotiated_at**: timestamp

## Relationships
- Certificate (1) -> (*) Certificate (issuer hierarchy)
- TLSProfile (1) -> (*) TLSSession (profile has many sessions)
- Certificate (1) -> (*) TLSSession (client cert used in sessions)
- TLSSession (1) -> (*) SRTPSession (TLS session carries SRTP)
- Certificate (1) -> (*) RevocationEntry (cert may be revoked)
- TLSSession (1) -> (*) CipherNegotiation (session has cipher history)
