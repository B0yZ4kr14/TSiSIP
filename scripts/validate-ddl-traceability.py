#!/usr/bin/env python3
"""
R10: DDL Traceability Check

Validates that audit and security tables referenced in governance documents
have corresponding DDL in db/init/*.sql.

Usage:
    python3 scripts/validate-ddl-traceability.py
"""

import re
import sys
from pathlib import Path

PROJECT_ROOT = Path(__file__).resolve().parent.parent
DB_INIT_DIR = PROJECT_ROOT / "db" / "init"

# Tables that MUST have DDL because they are referenced in governance docs
REQUIRED_TABLES = {
    "auth_audit_log",
    "ocp_login_log",
    "ocp_password_changes",
    "ocp_audit_log",
    "ocp_users",
    "subscriber",
    "dispatcher",
    "tenants",
    "header_routing_rules",
    "pbx_backends",
    "sip_trunk_providers",
    "sip_trunk_did_mappings",
    "sip_trunk_registrations",
    "cdr",
    "dialog",
}


def extract_tables_from_sql() -> set[str]:
    tables: set[str] = set()
    for sql_file in sorted(DB_INIT_DIR.glob("*.sql")):
        content = sql_file.read_text(encoding="utf-8")
        matches = re.findall(
            r"CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([a-z_][a-z0-9_]*)",
            content,
            re.IGNORECASE,
        )
        tables.update(m.lower() for m in matches)
    return tables


def main() -> int:
    ddl_tables = extract_tables_from_sql()
    print(f"Tables found in DDL: {len(ddl_tables)}")
    for table in sorted(ddl_tables):
        print(f"  OK {table}")

    print(f"\nRequired tables from governance: {len(REQUIRED_TABLES)}")

    missing = sorted(REQUIRED_TABLES - ddl_tables)
    if missing:
        print(f"\nMISSING DDL for {len(missing)} required table(s):")
        for table in missing:
            print(f"  - {table}")
        return 1

    print("\nAll required tables have corresponding DDL.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
