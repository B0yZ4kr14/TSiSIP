-- TSiSIP OCP Tools Schema Extension
-- Feature 020: OCP Critical Tool Gap Closure
-- Tables: dialplan, domain, dialog (for viewer)

-- Dialplan table for OpenSIPS dialplan module
-- Schema aligned with OpenSIPS 3.6 stock postgres schema (version 5)
CREATE TABLE IF NOT EXISTS dialplan (
    id          SERIAL PRIMARY KEY NOT NULL,
    dpid        INTEGER NOT NULL,
    pr          INTEGER DEFAULT 0 NOT NULL,
    match_op    INTEGER NOT NULL,
    match_exp   VARCHAR(64) NOT NULL,
    match_flags INTEGER DEFAULT 0 NOT NULL,
    subst_exp   VARCHAR(64) DEFAULT NULL,
    repl_exp    VARCHAR(32) DEFAULT NULL,
    timerec     VARCHAR(255) DEFAULT NULL,
    disabled    INTEGER DEFAULT 0 NOT NULL,
    attrs       VARCHAR(255) DEFAULT NULL
);

ALTER SEQUENCE dialplan_id_seq MAXVALUE 2147483647 CYCLE;

CREATE INDEX IF NOT EXISTS idx_dialplan_pr ON dialplan(pr);

-- Domain table for OpenSIPS domain module
CREATE TABLE IF NOT EXISTS domain (
    id            SERIAL PRIMARY KEY,
    domain        VARCHAR(64) NOT NULL DEFAULT '',
    did           VARCHAR(64) NOT NULL DEFAULT '',
    last_modified TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_domain_domain ON domain(domain);

-- Dialog table for OpenSIPS dialog module (optional, if dialog module enabled)
-- Note: OpenSIPS dialog module can use this table for persistent dialog storage.
-- For the TSiSIP viewer, we primarily use MI dlg_list; this table is for persistence.
CREATE TABLE IF NOT EXISTS dialog (
    hash_entry  INT NOT NULL,
    hash_id     INT NOT NULL,
    callid      VARCHAR(255) NOT NULL DEFAULT '',
    from_uri    VARCHAR(255) NOT NULL DEFAULT '',
    from_tag    VARCHAR(128) NOT NULL DEFAULT '',
    to_uri      VARCHAR(255) NOT NULL DEFAULT '',
    to_tag      VARCHAR(128) NOT NULL DEFAULT '',
    state       INT NOT NULL DEFAULT 0,
    start_time  BIGINT NOT NULL DEFAULT 0,
    timeout     BIGINT NOT NULL DEFAULT 0,
    sflags      INT NOT NULL DEFAULT 0,
    iflags      INT NOT NULL DEFAULT 0,
    toroute_name VARCHAR(128) NOT NULL DEFAULT '',
    req_uri     VARCHAR(255) NOT NULL DEFAULT '',
    xdata       TEXT,
    PRIMARY KEY (hash_entry, hash_id)
);

CREATE INDEX IF NOT EXISTS idx_dialog_callid ON dialog(callid);

-- Version tracking for schema changes
INSERT INTO version (table_name, table_version) VALUES ('dialplan', 5)
    ON CONFLICT (table_name) DO UPDATE SET table_version = EXCLUDED.table_version;

INSERT INTO version (table_name, table_version) VALUES ('domain', 1)
    ON CONFLICT (table_name) DO UPDATE SET table_version = EXCLUDED.table_version;

INSERT INTO version (table_name, table_version) VALUES ('dialog', 1)
    ON CONFLICT (table_name) DO UPDATE SET table_version = EXCLUDED.table_version;
