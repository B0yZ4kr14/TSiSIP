---
description: Deploy the implemented feature to Azure.
---

## User Input

```text
$ARGUMENTS
```

## Outline

`$ARGUMENTS` is deployment guidance: target env (`dev`/`staging`/`prod`), subscription/RG/region overrides, or `--what-if` for a dry run. Empty = "deploy to the default env in the plan".

1. **Load context**. Read `.specify/feature.json` → `<feature_directory>`. Read `constitution.md`, `spec.md`, `plan.md`, `tasks.md`, `tasks.json`. Stop if `tasks.*` missing (run `/speckit.tasks`) or any task is pending (run `/speckit.implement`; list pending IDs). Recommend running `/speckit.verify` first if it hasn't been run for the current implementation; proceed only with explicit user confirmation. Resolve any `Resolve: <question>` setup tasks with the user; do not guess.

2. **Plan**. From `plan.md` extract: target services, region, naming, identity/RBAC, networking, constitution non-negotiables. Locate IaC entry point (priority: `azure.yaml` (azd) → `infra/main.bicep` → `main.bicep` → `infra/main.tf` → `main.tf`); if none, stop. Pick tool: `azd up` for azd, else `az deployment {sub|group} create` for Bicep, or `terraform apply` for Terraform. Confirm subscription/RG/env with the user; show what will be created/updated/deleted.

3. **Pre-flight**. Verify CLI installed and signed in (`az account show`, `azd auth login` if azd); never store/echo credentials. Validate first: `az deployment group validate` + `what-if` (Bicep), `terraform plan`, or `azd provision --preview`. Show output and pause for confirmation. If `$ARGUMENTS` includes `--what-if`, stop and report.

4. **Deploy**. Run the command, stream output, don't swallow errors. On failure, halt and report the failing resource + error + likely next step (template fix, quota, RBAC); do not retry blindly. Never use destructive flags (`--force`, `-auto-approve` against prod, RG deletion) without explicit user opt-in in `$ARGUMENTS`. Don't print or commit secrets — reference Key Vault / managed identity.

5. **Post-deploy**. Confirm each planned resource exists and is healthy (`az resource show`, endpoint probe, smoke test). Map deployed resources to spec success criteria; flag any that can't yet be verified and why. Capture outputs (endpoints, IDs, KV-referenced connection info).

6. **Report**: subscription/RG/env, IaC entry point, command run, resources created/updated/deleted, outputs, validation results, unresolved issues / follow-ups.
