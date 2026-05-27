# Feature Specification: Custom Dashboard Layouts

## Overview

**Feature**: Custom Dashboard Layouts  
**Short name**: custom-dashboard  
**Created**: 2026-05-26  
**Status**: Complete  

### Context

Different operators need different information on their dashboard. Admins need failover controls, while devops need metrics.

### Objective

Allow users to customize their dashboard layout by adding, removing, and rearranging widgets.

---

## User Scenarios & Testing

### Scenario 1: Add Widget
- **Given** the user is on dashboard.php
- **When** they click "Add Widget"
- **Then** they see a list of available widgets
- **And** selecting one adds it to the dashboard

### Scenario 2: Rearrange Widgets
- **Given** the user has multiple widgets on their dashboard
- **When** they drag a widget to a new position
- **Then** the widget moves and the layout persists

### Scenario 3: Remove Widget
- **Given** the user has a widget on their dashboard
- **When** they click the widget's remove button
- **Then** the widget is removed after confirmation
- **And** the layout adjusts

---

## Functional Requirements

### FR-001: Widget Library
**Description**: A library of available dashboard widgets.  
**Acceptance Criteria**:
- System Status widget (existing)
- Active Dialogs widget
- Gateway Health widget
- Recent Alerts widget
- Quick Actions widget
- Metrics Sparkline widget

### FR-002: Drag-and-Drop
**Description**: Users can rearrange widgets via drag-and-drop.  
**Acceptance Criteria**:
- HTML5 drag-and-drop API
- Visual feedback during drag
- Snap-to-grid layout
- Persist layout order

### FR-003: Layout Persistence
**Description**: Layout saved per user.  
**Acceptance Criteria**:
- Save to `ocp_user_preferences` table
- Load on login
- Default layout for new users
- Reset to default option

### FR-004: Widget Configuration
**Description**: Some widgets have configurable options.  
**Acceptance Criteria**:
- Refresh interval setting
- Number of items to show
- Filter criteria
- Config saved with layout

---

## Data Model

### Entity: ocp_user_preferences
| Column | Type | Description |
|---|---|---|
| user_id | INT FK | User reference |
| preference_key | VARCHAR(50) | 'dashboard_layout' |
| preference_value | JSONB | Widget positions and configs |

## Scope

### In Scope
- Widget system
- Drag-and-drop layout
- Layout persistence
- Widget configuration

### Out of Scope
- Custom widget creation by users
- Widget marketplace
- Shared/public dashboards
