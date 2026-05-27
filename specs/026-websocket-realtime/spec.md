# Feature Specification: WebSocket Real-time Updates

## Overview

**Feature**: WebSocket Real-time Updates  
**Short name**: websocket-realtime  
**Created**: 2026-05-26  
**Status**: Complete  

### Context

Current real-time pages (gateway-health, call-queue, rtpengine-status) use AJAX polling every 15 seconds. This creates unnecessary HTTP overhead and delays in data freshness.

### Objective

Replace AJAX polling with WebSocket connections for real-time data push from server to client.

---

## User Scenarios & Testing

### Scenario 1: Real-time Gateway Health
- **Given** the user is on gateway-health.php
- **When** a gateway status changes
- **Then** the UI updates within 1 second without page reload

### Scenario 2: Multiple Clients
- **Given** two users are viewing call-queue.php
- **When** a new call arrives
- **Then** both users see the update simultaneously

### Scenario 3: Reconnection
- **Given** the WebSocket connection drops
- **When** the network recovers
- **Then** the connection re-establishes automatically
- **And** the client receives any missed updates

---

## Functional Requirements

### FR-001: WebSocket Server
**Description**: A WebSocket server pushes MI data to connected clients.  
**Acceptance Criteria**:
- Built on existing PHP infrastructure (no new services)
- Uses `ratchet/pawl` or similar PHP WebSocket library
- Listens on internal Docker network only
- Authenticates connections via session token

### FR-002: Client Connection
**Description**: Browser connects to WebSocket on page load.  
**Acceptance Criteria**:
- Automatic connection on real-time pages
- Graceful fallback to polling if WebSocket unavailable
- Visual connection status indicator

### FR-003: Data Push
**Description**: Server pushes data when MI values change.  
**Acceptance Criteria**:
- Push gateway status changes
- Push new transactions
- Push rtpengine session changes
- Debounce rapid changes (max 1 push per second)

### FR-004: Connection Management
**Description**: Handle multiple clients and reconnections.  
**Acceptance Criteria**:
- Support 50+ concurrent connections
- Auto-reconnect with exponential backoff
- Heartbeat/ping to detect dead connections
- Clean disconnect on page unload

---

## Security Requirements

| ID | Requirement | Verification |
|---|---|---|
| SR-001 | WebSocket only on internal network | `docker compose config` |
| SR-002 | Session token validation | Auth failure test |
| SR-003 | Rate limiting | Max 1 message/sec per client |

## Scope

### In Scope
- WebSocket server implementation
- Client connection logic
- Data push for gateway-health, call-queue, rtpengine-status
- Connection management

### Out of Scope
- Video/audio streaming
- File upload via WebSocket
- Cross-origin WebSocket connections

## Dependencies

- Feature 002 (OCP Rebrand) — must be complete
- PHP WebSocket library
