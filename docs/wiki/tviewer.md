# TViewer

## Purpose

The TViewer (Table Viewer) module provides a generic, configuration-driven interface for viewing any PostgreSQL table. Admins define schemas (table name, columns, primary key) and TViewer renders a read-only or editable grid.

## Access

- **Roles**: Any authenticated (view schemas), devops/admin (manage schemas)
- **Navigation**: System → TViewer

## Operations

| Operation | Permission | Description |
|---|---|---|
| View Table | Any authenticated | Browse data from configured tables |
| List Schemas | Any authenticated | View available table schemas |
| Create Schema | devops, admin | Define a new table schema |
| Edit Schema | devops, admin | Modify schema definition |
| Delete Schema | devops, admin | Remove a schema definition |

## Table Schema

- `tviewer_schemas` (OCP extension)
  - `table_name` — target PostgreSQL table
  - `columns` — comma-separated column list or `*`
  - `primary_key` — key column for pagination
  - `enabled` — whether the schema is active

## Security Notes

- Table names are validated against a whitelist (`/^[a-zA-Z0-9_]+$/`)
- Only `SELECT` operations are performed on target tables
