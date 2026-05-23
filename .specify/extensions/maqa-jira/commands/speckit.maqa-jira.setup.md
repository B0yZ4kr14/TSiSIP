---
description: "Bootstrap Jira config for MAQA. Lists projects and workflow statuses via the Jira REST API v3 and generates maqa-jira/jira-config.yml. Run once per project."
---

You are setting up Jira integration for MAQA.

## Prerequisites check

```bash
[ -n "$JIRA_BASE_URL" ]  && echo "JIRA_BASE_URL: set"  || echo "ERROR: JIRA_BASE_URL not set"
[ -n "$JIRA_EMAIL" ]     && echo "JIRA_EMAIL: set"     || echo "ERROR: JIRA_EMAIL not set"
[ -n "$JIRA_API_TOKEN" ] && echo "JIRA_API_TOKEN: set" || echo "ERROR: JIRA_API_TOKEN not set"
AUTH=$(echo -n "$JIRA_EMAIL:$JIRA_API_TOKEN" | base64)
```

Stop if any missing.

## Step 1 — List projects

```bash
curl -s -H "Authorization: Basic $AUTH" \
  -H "Content-Type: application/json" \
  "$JIRA_BASE_URL/rest/api/3/project/search?maxResults=50" | \
  python3 -c "
import json,sys
data = json.load(sys.stdin)
for p in data.get('values',[]):
    print(f\"{p['key']:10} — {p['name']}\")
"
```

Ask user which project key to use.

## Step 2 — Get workflow transitions

```bash
PROJECT_KEY="<selected>"
# Get a sample issue to fetch transitions (or use the project's default workflow)
curl -s -H "Authorization: Basic $AUTH" \
  "$JIRA_BASE_URL/rest/api/3/issue/createmeta?projectKeys=$PROJECT_KEY&expand=projects.issuetypes.fields" | \
  python3 -c "import json,sys; print(json.dumps(json.load(sys.stdin), indent=2))" | head -40

# Get transitions by creating a temp issue or listing statuses
curl -s -H "Authorization: Basic $AUTH" \
  "$JIRA_BASE_URL/rest/api/3/project/$PROJECT_KEY/statuses" | \
  python3 -c "
import json,sys
data = json.load(sys.stdin)
for issuetype in data:
    print(f\"Type: {issuetype['name']}\")
    for s in issuetype.get('statuses',[]):
        print(f\"  {s['id']} — {s['name']}\")
"
```

Map status IDs to: Todo, In Progress, In Review, Done. Ask user to confirm.

## Step 3 — Write config

```bash
mkdir -p maqa-jira
cat > maqa-jira/jira-config.yml << EOF
# MAQA Jira Configuration — generated $(date -Iseconds)
project_key: "$PROJECT_KEY"
board_id: ""
todo_transition_id: "$TODO_ID"
in_progress_transition_id: "$IN_PROGRESS_ID"
in_review_transition_id: "$IN_REVIEW_ID"
done_transition_id: "$DONE_ID"
todo_status: "To Do"
in_progress_status: "In Progress"
in_review_status: "In Review"
done_status: "Done"
issue_type: "Story"
subtask_type: "Subtask"
EOF
```

## Done

Report mapped statuses and tell user to run `/speckit.maqa.coordinator`.
