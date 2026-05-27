# Plan: WebSocket Real-time Updates

## Architecture Decision

SSE chosen over WebSocket for simpler PHP integration without persistent server process.

## Components

- web/common/sse-stream.php
- web/tsisip/js/sse-client.js
- web/dashboard.php

## Data Sources

1. OpenSIPS version
2. Uptime
3. Memory usage
4. Active dialogs
5. Active transactions
6. Gateway health
7. RTPengine status
8. CPU load

## Security

- CSRF token validation
- Internal network only
