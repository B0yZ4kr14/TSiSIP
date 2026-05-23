# Work IQ Spec Kit Extension — Usage Guide

## Overview

The Work IQ extension connects your Spec Kit workflow to Microsoft 365 organizational data. This guide covers each command in detail with examples and explains the data flow.

## Data Flow

```
┌─────────────────┐     ┌──────────────┐     ┌─────────────────┐
│  Spec Kit CLI   │────▶│  Work IQ     │────▶│  Microsoft 365  │
│  /speckit.workiq│     │  CLI or MCP  │     │  Graph API      │
│  .ask/context/  │     │              │     │                 │
│  stakeholders/  │◀────│              │◀────│  Emails         │
│  enrich         │     │              │     │  Meetings       │
└────────┬────────┘     └──────────────┘     │  Documents      │
         │                                    │  Teams          │
         ▼                                    └─────────────────┘
┌─────────────────┐
│ .specify/context│
│  workiq-*.md    │
└─────────────────┘
```

## Commands

### `/speckit.workiq.ask` — Direct Query

The simplest command. Ask any question and get an answer from your M365 data.

**One-shot mode:**
```
/speckit.workiq.ask What were the key decisions from Friday's architecture meeting?
```

**Interactive mode (no arguments):**
```
/speckit.workiq.ask
> What would you like to know about your M365 data?
```

**Example scenarios:**
- Finding context while coding: "What requirements were discussed for the file upload feature?"
- Checking decisions: "Did we agree on REST or GraphQL for the new API?"
- Finding documents: "Where is the security review document for Project Alpha?"
- Meeting prep: "What open items exist from last sprint's retrospective?"

---

### `/speckit.workiq.context` — Context Gathering

Runs multiple queries across emails, meetings, documents, and Teams to build a comprehensive context brief.

**Usage:**
```
/speckit.workiq.context user authentication redesign
```

**What it queries:**
1. Recent emails mentioning the topic
2. Meetings scheduled or held about the topic
3. Documents and files related to the topic
4. Teams channel discussions about the topic

**Output:** A structured markdown file at `.specify/context/workiq-context.md` containing:
- Summary of all findings
- Email threads with key decisions
- Meeting notes and action items
- Related documents with links
- Key decisions and constraints already established
- Open questions that need answers

**Best used:** Before starting a new spec, to understand what organizational knowledge already exists.

---

### `/speckit.workiq.stakeholders` — Stakeholder Discovery

Analyzes M365 activity to identify who should be involved with a spec.

**Usage:**
```
/speckit.workiq.stakeholders payment processing migration
```

**What it discovers:**
- **Decision Makers** — People approving decisions (meeting organizers, email approvers)
- **Subject Matter Experts** — People with deep knowledge (document authors, technical responders)
- **Active Contributors** — People currently working on the topic
- **Management Chain** — Leadership involved with the area

**Output:** A stakeholder map at `.specify/context/workiq-stakeholders.md` with:
- Categorized people with activity signals
- Top 3-5 recommended spec reviewers with rationale
- Suggestions for who to consult on specific aspects

**Best used:** When identifying reviewers for a spec, or when joining a new project area.

---

### `/speckit.workiq.enrich` — Spec Enrichment

Reads your existing spec and queries M365 for information that could fill gaps.

**Usage:**
```
/speckit.workiq.enrich
/speckit.workiq.enrich path/to/my-spec.md
```

**What it checks:**
- Requirements without justification — searches for supporting decisions
- Missing stakeholder input — finds feedback from relevant people
- Undocumented constraints — discovers technical or business constraints discussed in M365
- Decision rationale — finds why certain choices were made

**Output:** An enrichment report at `.specify/context/workiq-enrichment.md` with:
- Proposed additions with M365 source citations
- Confidence levels (High/Medium/Low) for each finding
- Undocumented decisions that should be captured
- Suggestions for new spec sections

**Important:** The enrich command **never modifies your spec automatically**. It presents proposed changes and waits for your approval.

**Best used:** After writing a first draft of a spec, to catch gaps and add organizational context.

---

## Integration Modes

### MCP Server (Preferred)

When running inside an AI agent (e.g., GitHub Copilot) with the Work IQ MCP server:

```bash
# Start the MCP server
npx -y @microsoft/workiq mcp
```

Commands automatically use the `workiq-ask_work_iq` MCP tool for seamless integration.

### CLI Mode

When the MCP server isn't available, commands fall back to the Work IQ CLI:

```bash
# Direct CLI usage
workiq ask -q "What meetings do I have about auth?"
```

## Setup Checklist

1. ✅ Install Node.js 18+
2. ✅ Install Work IQ: `npm install -g @microsoft/workiq`
3. ✅ Accept EULA: `workiq accept-eula`
4. ✅ Authenticate: `workiq auth`
5. ✅ Install extension: `specify extension add workiq --from <zip-url>`
6. ✅ Verify: `/speckit.workiq.ask Hello, what's in my inbox?`
