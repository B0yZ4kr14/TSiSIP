# Feature Specification: OCP Navigation System Links

## Overview

**Feature**: OCP Navigation System Links  
**Short name**: ocp-navigation-system-links  
**Created**: 2026-05-19  
**Status**: Completed

### Context

After implementing OCP admin authentication (Feature 002 extension), users logging in as Admin landed on the dashboard but found only wiki/documentation links. The dashboard lacked links to actual system management pages (Dispatcher Targets, RTPengine Sessions), forcing users to manually type URLs or navigate only through the wiki.

### Objective

Restructure the TSiSIP Control Panel dashboard and sidebar navigation so that authenticated users — especially Admin and DevOps roles — can access both system management pages and wiki documentation from the landing page.

---

## User Scenarios & Testing

### Scenario 1: Admin logs in and sees system controls
- **Given** an Admin user has authenticated successfully
- **When** the dashboard loads
- **Then** the page shows a "System Management" section with links to Dispatcher and RTPengine
- **And** a "Documentation & Wiki" section with role-appropriate wiki pages

### Scenario 2: DevOps accesses technical ops pages
- **Given** a DevOps user has authenticated
- **When** viewing the dashboard or sidebar
- **Then** system management links are visible
- **And** wiki links for technical operations are present

### Scenario 3: Non-technical user sees only wiki
- **Given** a Dentist, Assistant, User, or Readonly user has authenticated
- **When** the dashboard loads
- **Then** no system management section appears
- **And** only documentation/wiki links are shown

### Scenario 4: Sidebar marks active page correctly
- **Given** the user clicks "Dispatcher Targets" in the sidebar
- **When** the dispatcher.php page loads
- **Then** the sidebar highlights "Dispatcher Targets" as active
- **And** wiki pages continue to highlight correctly when visited

---

## Functional Requirements

### FR-010-001: Dashboard System Management Section
**Description**: The dashboard must display system management links for privileged roles.
**Acceptance Criteria**:
- Admin and DevOps roles see links to `dispatcher.php` and `rtpengine.php`
- Section title is "System Management"
- Links render as primary buttons (`tsisip-btn-primary`)

### FR-010-002: Dashboard Documentation Section
**Description**: The dashboard must continue showing wiki/documentation links for all roles.
**Acceptance Criteria**:
- All authenticated roles see their role-appropriate wiki quick links
- Section title is "Documentation & Wiki"
- Links render as secondary buttons (`tsisip-btn-secondary`)

### FR-010-003: Sidebar System Pages
**Description**: The sidebar navigation must include system pages for privileged roles.
**Acceptance Criteria**:
- Admin and DevOps see "System" heading with Dispatcher and RTPengine links
- Sidebar separates "System" and "Wiki" sections visually
- Active page highlighting works for both system and wiki pages

### FR-010-004: System Status Indicators
**Description**: The dashboard shows operational status of core services.
**Acceptance Criteria**:
- Status list shows: OpenSIPS, RTPengine, PostgreSQL, OCP
- Each status has a colored dot indicator

---

## Success Criteria

- [ ] Admin dashboard shows both system management and documentation links
- [ ] DevOps dashboard shows both system management and documentation links
- [ ] Non-privileged roles see only documentation links
- [ ] Sidebar correctly highlights active page for system and wiki pages
- [ ] Login redirect remains `dashboard.php` (not changed to wiki)
- [ ] All PHP files pass syntax validation
- [ ] Container health check passes after deploy

---

## Rejected Patterns

| Rejected | Canonical Replacement |
|---|---|
| Redirect login to `wiki.php` | Keep redirect to `dashboard.php` |
| Mix system and wiki links in one undifferentiated list | Separate "System Management" and "Documentation & Wiki" sections |
| Show system links to all roles | Role-gated display (admin/devops only) |

---

## Related Features

- Feature 002: TSiSIP OCP Rebrand (base UI)
- Feature 009: VPS Deploy Automation Pipeline (deploys this fix)
