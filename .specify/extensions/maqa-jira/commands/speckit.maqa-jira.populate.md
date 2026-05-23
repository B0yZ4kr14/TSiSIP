---
description: "Populate Jira project from specs/*/tasks.md. Creates one Story per feature with Subtasks per task item. Skips features already in the project. Safe to re-run."
---

You are populating a Jira project from spec-kit specs. Safe to re-run.

## Step 1 — Read config

```bash
AUTH=$(echo -n "$JIRA_EMAIL:$JIRA_API_TOKEN" | base64)
source <(python3 -c "
import re
with open('maqa-jira/jira-config.yml') as f:
    for line in f:
        m = re.match(r'^(\w+):\s*\"?([^\"#\n]+)\"?', line.strip())
        if m and m.group(2).strip():
            print(f'{m.group(1).upper()}={m.group(2).strip()}')
")
```

## Step 2 — Get existing issue summaries

```bash
EXISTING=$(curl -s -H "Authorization: Basic $AUTH" \
  "$JIRA_BASE_URL/rest/api/3/search?jql=project=$PROJECT_KEY+AND+issuetype=$ISSUE_TYPE&fields=summary&maxResults=200" | \
  python3 -c "
import json,sys
data = json.load(sys.stdin)
for i in data.get('issues',[]):
    print(i['fields']['summary'].lower().strip())
")
```

## Step 3 — Discover specs and create stories

For each ready spec not already in Jira:

Parse title and tasks from tasks.md (same logic as maqa-trello populate).

Create Story:

```bash
STORY_ID=$(curl -s -X POST \
  -H "Authorization: Basic $AUTH" \
  -H "Content-Type: application/json" \
  "$JIRA_BASE_URL/rest/api/3/issue" \
  -d "{
    \"fields\": {
      \"project\": {\"key\": \"$PROJECT_KEY\"},
      \"summary\": \"$TITLE\",
      \"issuetype\": {\"name\": \"$ISSUE_TYPE\"},
      \"description\": {
        \"type\": \"doc\", \"version\": 1,
        \"content\": [{\"type\": \"paragraph\", \"content\": [{\"type\": \"text\", \"text\": \"$DESC\"}]}]
      }
    }
  }" | python3 -c "import json,sys; print(json.load(sys.stdin)['key'])")
```

Create Subtask per task:

```bash
curl -s -X POST \
  -H "Authorization: Basic $AUTH" \
  -H "Content-Type: application/json" \
  "$JIRA_BASE_URL/rest/api/3/issue" \
  -d "{
    \"fields\": {
      \"project\": {\"key\": \"$PROJECT_KEY\"},
      \"parent\": {\"key\": \"$STORY_ID\"},
      \"summary\": \"$TASK\",
      \"issuetype\": {\"name\": \"$SUBTASK_TYPE\"}
    }
  }" -o /dev/null
```

## Step 4 — Report

```
populated[N]{name,story_id,subtasks}:
  ...
skipped[M]{name,reason}:
  ...
```
