-- TSiSIP Stock OpenSIPS 3.6 PostgreSQL Schema
-- Derived from official OpenSIPS 3.6 database schema requirements.
-- This schema provides the baseline tables required by the canonical module configuration.

-- subscriber: required by auth_db module
CREATE TABLE IF NOT EXISTS subscriber (
    id SERIAL PRIMARY KEY,
    username VARCHAR(64) NOT NULL DEFAULT '',
    domain VARCHAR(64) NOT NULL DEFAULT '',
    password VARCHAR(64) NOT NULL DEFAULT '',
    ha1 VARCHAR(128) NOT NULL DEFAULT '',
    ha1_sha256 VARCHAR(256) NOT NULL DEFAULT '',
    ha1_sha512t256 VARCHAR(256) NOT NULL DEFAULT '',
    email_address VARCHAR(128) NOT NULL DEFAULT '',
    rpid VARCHAR(128) DEFAULT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS subscriber_idx
    ON subscriber (username, domain);

-- dispatcher: required by dispatcher module
CREATE TABLE IF NOT EXISTS dispatcher (
    id SERIAL PRIMARY KEY,
    setid INTEGER NOT NULL DEFAULT 0,
    destination VARCHAR(192) NOT NULL DEFAULT '',
    socket VARCHAR(128) DEFAULT NULL,
    state INTEGER NOT NULL DEFAULT 0,
    probe_mode INTEGER NOT NULL DEFAULT 0,
    weight VARCHAR(64) NOT NULL DEFAULT '1',
    priority INTEGER NOT NULL DEFAULT 0,
    attrs VARCHAR(128) DEFAULT NULL,
    description VARCHAR(64) DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS dispatcher_setid_idx
    ON dispatcher (setid);

-- address: required by permissions module for trusted gateway IP whitelist (FR-008)
CREATE TABLE IF NOT EXISTS address (
    id SERIAL PRIMARY KEY,
    grp SMALLINT NOT NULL DEFAULT 0,
    ip VARCHAR(50) NOT NULL,
    mask SMALLINT NOT NULL DEFAULT 32,
    port SMALLINT NOT NULL DEFAULT 0,
    proto VARCHAR(4) NOT NULL DEFAULT 'any',
    pattern VARCHAR(64) DEFAULT NULL,
    context_info VARCHAR(32) DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS address_grp_ip_idx
    ON address (grp, ip);

-- version: required by db_postgres for schema compatibility checks
CREATE TABLE IF NOT EXISTS version (
    table_name VARCHAR(32) NOT NULL,
    table_version INTEGER NOT NULL DEFAULT 0,
    CONSTRAINT version_idx UNIQUE (table_name)
);

INSERT INTO version (table_name, table_version) VALUES
    ('subscriber', 8),
    ('dispatcher', 9),
    ('address', 5)
ON CONFLICT (table_name) DO UPDATE SET table_version = EXCLUDED.table_version;
