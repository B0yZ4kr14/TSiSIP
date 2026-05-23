---
description: "Bootstrap Azure DevOps config for MAQA. Reads organization, project, and board columns via REST API and generates maqa-azure-devops/azure-devops-config.yml. Run once per project."
---

You are setting up Azure DevOps integration for MAQA.

## Prerequisites check

```bash
[ -n "$AZURE_DEVOPS_TOKEN" ] && echo "AZURE_DEVOPS_TOKEN: set" || echo "ERROR: AZURE_DEVOPS_TOKEN not set"
ADO_AUTH=$(echo -n ":$AZURE_DEVOPS_TOKEN" | base64)
```

Stop if missing.

## Step 1 — List projects in organization

Ask user for organization name, then:

```bash
ORG="<org>"
curl -s -H "Authorization: Basic $ADO_AUTH" \
  "https://dev.azure.com/$ORG/_apis/projects?api-version=7.1" | \
  python3 -c "
import json,sys
data = json.load(sys.stdin)
for p in data.get('value',[]):
    print(f\"{p['name']}\")
"
```

Ask user which project to use.

## Step 2 — Get teams and board columns

```bash
PROJECT="<selected>"
# Get teams
curl -s -H "Authorization: Basic $ADO_AUTH" \
  "https://dev.azure.com/$ORG/$PROJECT/_apis/teams?api-version=7.1" | \
  python3 -c "
import json,sys
for t in json.load(sys.stdin).get('value',[]):
    print(f\"{t['name']}\")
"

# Get board columns for selected team
TEAM="<selected>"
curl -s -H "Authorization: Basic $ADO_AUTH" \
  "https://dev.azure.com/$ORG/$PROJECT/$TEAM/_apis/work/boards?api-version=7.1" | \
  python3 -c "
import json,sys
for b in json.load(sys.stdin).get('value',[]):
    print(f\"{b['name']} — {b['id']}\")
"
```

Map columns to MAQA slots. Ask user to confirm.

## Step 3 — Write config

```bash
mkdir -p maqa-azure-devops
cat > maqa-azure-devops/azure-devops-config.yml << EOF
# MAQA Azure DevOps Configuration — generated $(date -Iseconds)
organization: "$ORG"
project: "$PROJECT"
team: "$TEAM"
todo_column: "$TODO_COL"
in_progress_column: "$IN_PROGRESS_COL"
in_review_column: "$IN_REVIEW_COL"
done_column: "$DONE_COL"
story_type: "User Story"
task_type: "Task"
area_path: ""
iteration_path: ""
EOF
```

## Done

Report mapped columns and tell user to run `/speckit.maqa.coordinator`.
