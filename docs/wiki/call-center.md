# Call Center

## Purpose

The Call Center module manages call queues, flows, and agents using the OpenSIPS `call_center` module. It enables automatic call distribution (ACD) with skill-based routing.

## Access

- **Roles**: devops, admin
- **Navigation**: System → Call Center

## Operations

| Operation | Permission | Description |
|---|---|---|
| List Flows | devops, admin | View all call flows |
| Create Flow | devops, admin | Add a new call flow |
| Edit Flow | devops, admin | Modify flow parameters |
| Delete Flow | devops, admin | Remove a flow |
| List Agents | devops, admin | View all agents |
| Create Agent | devops, admin | Add a new agent |
| Edit Agent | devops, admin | Modify agent skills or login state |
| Delete Agent | devops, admin | Remove an agent |

## Table Schema

- `cc_flows` — call flow definitions (`flowid`, `priority`, `skill`, `cid`)
- `cc_agents` — agent definitions (`agentid`, `location`, `skills`, `logstate`, `flowid`)
- `cc_calls` — active/historical calls (`callid`, `flowid`, `agentid`, `state`)

## Flow Types

The OpenSIPS `call_center` module supports FIFO, LIFO, and skill-based distribution. Flows are matched by `skill` and `cid` fields.

## Related Modules

- Subscribers — agents are typically SIP subscribers
- Groups — skill-based routing can leverage group definitions
