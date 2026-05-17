# Data Model: TSiSIP Observability Platform

## Entity: MetricFamily
- **family_id**: UUID
- **name**: string (e.g., opensips_active_dialogs_total)
- **type**: enum (counter, gauge, histogram, summary)
- **description**: string
- **labels**: JSON array of label names
- **cardinality_limit**: integer (default 10000)
- **created_at**: timestamp

## Entity: TimeSeries
- **series_id**: UUID
- **family_id**: UUID (FK to MetricFamily)
- **label_set**: JSON object
- **timestamp**: timestamp
- **value**: float

## Entity: ScrapeJob
- **job_id**: UUID
- **name**: string (e.g., opensips, rtpengine)
- **target_url**: string
- **scrape_interval**: integer (seconds)
- **timeout**: integer (seconds)
- **metrics_path**: string (default /metrics)
- **status**: enum (active, paused)

## Entity: AlertRule
- **rule_id**: UUID
- **name**: string
- **expression**: string (PromQL)
- **duration**: string (e.g., 2m)
- **severity**: enum (critical, warning, info)
- **notification_channel**: string
- **runbook_url**: string
- **status**: enum (active, silenced)

## Entity: Dashboard
- **dashboard_id**: UUID
- **title**: string
- **role_visibility**: enum (noc, admin, devops)
- **refresh_interval**: integer (seconds)
- **panel_configuration_json**: JSON
- **locale**: enum (en, es, pt)

## Entity: Notification
- **notification_id**: UUID
- **rule_id**: UUID (FK to AlertRule)
- **status**: enum (firing, resolved)
- **message**: string
- **sent_at**: timestamp
- **resolved_at**: timestamp (nullable)

## Relationships
- MetricFamily (1) -> (*) TimeSeries
- ScrapeJob (*) -> (1) MetricFamily (scrape produces metrics)
- AlertRule (*) -> (1) MetricFamily (rules evaluate metrics)
- Dashboard (*) -> (*) MetricFamily (dashboards query metrics)
- AlertRule (1) -> (*) Notification (rules trigger notifications)
