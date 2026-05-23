---
description: "Populate Azure DevOps board from specs/*/tasks.md. Creates User Stories with Task child items per feature. Skips existing. Safe to re-run."
---

You are populating an Azure DevOps board from spec-kit specs. Safe to re-run.

## Step 1 — Read config

```bash
ADO_AUTH=$(echo -n ":$AZURE_DEVOPS_TOKEN" | base64)
source <(python3 -c "
import re
with open('maqa-azure-devops/azure-devops-config.yml') as f:
    for line in f:
        m = re.match(r'^(\w+):\s*\"?([^\"#\n]+)\"?', line.strip())
        if m and m.group(2).strip():
            print(f'{m.group(1).upper()}={m.group(2).strip()}')
")
BASE="https://dev.azure.com/$ORGANIZATION/$PROJECT"
```

## Step 2 — Get existing work item titles

```bash
EXISTING=$(curl -s -X POST \
  -H "Authorization: Basic $ADO_AUTH" \
  -H "Content-Type: application/json" \
  "$BASE/_apis/wit/wiql?api-version=7.1" \
  -d "{\"query\":\"SELECT [System.Title] FROM WorkItems WHERE [System.WorkItemType] = '$STORY_TYPE' AND [System.TeamProject] = '$PROJECT'\"}" | \
  python3 -c "
import json,sys,urllib.request,urllib.error
data = json.load(sys.stdin)
# WIQL returns IDs only; fetch titles in batch
ids = [str(w['id']) for w in data.get('workItems',[])]
print('\n'.join(ids))
")
```

Fetch titles for those IDs, build existing set.

## Step 3 — Create stories and tasks

For each ready spec not already in Azure DevOps:

```bash
# Create User Story
STORY_ID=$(curl -s -X POST \
  -H "Authorization: Basic $ADO_AUTH" \
  -H "Content-Type: application/json-patch+json" \
  "$BASE/_apis/wit/workitems/\$$STORY_TYPE?api-version=7.1" \
  -d "[
    {\"op\":\"add\",\"path\":\"/fields/System.Title\",\"value\":\"$TITLE\"},
    {\"op\":\"add\",\"path\":\"/fields/System.Description\",\"value\":\"$DESC\"}
  ]" | python3 -c "import json,sys; print(json.load(sys.stdin)['id'])")

# Create Task children
for TASK in "${TASKS[@]}"; do
  curl -s -X POST \
    -H "Authorization: Basic $ADO_AUTH" \
    -H "Content-Type: application/json-patch+json" \
    "$BASE/_apis/wit/workitems/\$$TASK_TYPE?api-version=7.1" \
    -d "[
      {\"op\":\"add\",\"path\":\"/fields/System.Title\",\"value\":\"$TASK\"},
      {\"op\":\"add\",\"path\":\"/relations/-\",\"value\":{
        \"rel\":\"System.LinkTypes.Hierarchy-Reverse\",
        \"url\":\"$BASE/_apis/wit/workItems/$STORY_ID\"
      }}
    ]" -o /dev/null
done
```

## Step 4 — Report

```
populated[N]{name,story_id,tasks}:
  ...
skipped[M]{name,reason}:
  ...
```
