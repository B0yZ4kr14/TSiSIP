---
description: Verify locally that the implemented feature is ready to be deployed to Azure by running it against local servers (with Azure-only dependencies provisioned first).
handoffs:
  - label: Deploy to Azure
    agent: speckit.deploy
    prompt: Deploy the verified feature to Azure
---

## User Input

```text
$ARGUMENTS
```

## Outline

**Purpose.** Bring the feature alive on the user's machine and exercise it end-to-end against the dependencies it actually needs. Run locally what can be local (Azurite, Cosmos/SQL/Postgres/Redis emulators or containers, Functions/web/API hosts). Provision in Azure first what has no faithful emulator (Microsoft Foundry models/agents, Azure OpenAI, AI Search, Cosmos for MongoDB vCore, Event Grid, etc.) and point local servers at those cloud resources via a `dev` env. A green run gates `/speckit.deploy`; failure blocks it.

You are an assistant: surface what to start, propose how, ask before installing tools or provisioning cloud resources, run on confirmation, translate failures into next steps.

`$ARGUMENTS` (if any) = guidance: which component (`api`/`web`/`functions`/`worker`), env (`dev`/`local`), `--no-cloud` (refuse Azure provisioning), `--reuse` (reuse running processes), `--rerun` (force fresh start). Empty = "start everything I need locally, provisioning the minimum Azure deps if needed, and walk me through verifying it works".

> **Context.** `/speckit.implement` already ran the unit/integration suite. This step is different: it runs the feature so the user can poke at it like a real consumer.

1. **Load context**. Read `.specify/feature.json` → `<feature_directory>`. Read `constitution.md`, `spec.md`, `plan.md`, `tasks.md`, `tasks.json`. Stop if `tasks.*` missing (run `/speckit.tasks`). If any task is pending, warn (list IDs) and ask whether to verify the partial impl or stop. Resolve any `Resolve: <question>` setup tasks; do not guess.

2. **Inventory**. From `plan.md` + repo, list each runnable component (path, run command — `dotnet run` / `npm run dev` / `func start` / `uvicorn …`, port, health URL). List each dependency, classifying as:
   - **local-emulatable** (Azurite, Cosmos Emulator, Docker SQL/Postgres/Redis/RabbitMQ, Functions Core Tools).
   - **cloud-only** (Foundry agents/models, Azure OpenAI, AI Search, Cosmos for MongoDB vCore, Event Grid, App Insights ingestion).
   - **stubbable** (recordable fixture / fake — ask the user which to use).
   Show the inventory as a table (Component → Run cmd → Deps → Local/Cloud) and confirm before proceeding.

3. **Provision cloud-only deps first** (only what the inventory needs). If `--no-cloud`, stop and list missing deps + choices (provision/stub/skip). Otherwise: confirm subscription/RG/region/`dev` env name (no silent defaults). Reuse existing resources (`az resource show`, `azd env get-values`, `az cognitiveservices account deployment list`) if config matches. Else propose minimal command and confirm: `azd provision -e dev` (when `azure.yaml` exists), or targeted `az deployment group create` against a focused Bicep module. For Foundry: ensure project + required model deployments (`az cognitiveservices account deployment create`); capture endpoint + deployment name + identity. Capture every endpoint / KV URI / identifier into the project's local config (`.env`, `local.settings.json`, `appsettings.Development.json`, `azd env set`); show what you're writing first; never echo secrets.

4. **Plan startup order**. Build dependency-respecting order (emulators → API → web); note ports + parallelism. Detect busy ports; ask whether to reuse / kill / pick another — never silently override. Show the planned commands and confirm before any state-changing action (tool install, image pull, container start).

5. **Pre-flight**. Verify required tools (`docker`, `az`, `azd`, `func`, `node`, `dotnet`, package manager) — tell the user before installing anything. Restore deps if needed (`npm ci`, `pip install -e .`, `dotnet restore`, `go mod download`) — tell the user first. Load env vars from `.env`, `local.settings.json`, `appsettings.Development.json`, `azd env get-values`; secrets must resolve via Key Vault / managed identity, never literals; never echo secrets.

6. **Start emulators + components**. Start each local-emulatable dep and wait for ready. Run one-time setup (migrations, seeds, queue/topic/container creation) — show commands; never destroy local data without asking. Start each component in the planned order, in the background where appropriate, streaming logs to a tailable file; report URL + PID. Health-probe (or TCP) before declaring "up". On failure, halt, surface error + log excerpt + likely fix; do not retry blindly.

7. **Walk acceptance scenarios** from `spec.md`. Translate each into a concrete check (`curl`, browser URL, UI flow, agent prompt). Run automatable checks yourself and show responses; for UI flows give URL + steps. Mark each scenario passed / failed (with evidence) / needs-human-confirmation. Watch for runtime-only failures: missing config, wrong identity/RBAC on cloud deps, Foundry model not in region, throttling, connection-string mismatches.

8. **Tear down — only what you started**. Offer to stop processes/emulators you started; never stop anything you didn't. Leave cloud `dev` resources running by default (expensive to recreate); tell the user how to delete (`azd down -e dev`, `az group delete`) and warn about cost for non-trivial resources (Foundry, AI Search).

9. **Hand off**: clear go/no-go for `/speckit.deploy` plus a short report: inventory (local vs cloud), cloud resources provisioned/reused (sub/RG/names/endpoints — no secrets), local processes (component → URL → PID → log file), per-scenario result with evidence for failures, anything still unverified and why, exact next step (`/speckit.deploy` if all green, else specific fixes).
