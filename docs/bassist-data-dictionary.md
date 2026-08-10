# BAssist Data Dictionary

_Generated from live SQLite schema on 2026-08-04 06:55._

Companion files:

- [`bassist-erd.md`](bassist-erd.md) — Mermaid ERD (paste into [mermaid.live](https://mermaid.live) for PNG/SVG)
- [`bassist-data-dictionary.csv`](bassist-data-dictionary.csv) — Excel-friendly column list

## Domain model overview (need spine)

```
Tenant → Workspace → Project
  ├─ StrategicBaseline (1:1)
  ├─ ScopeItem, Assumption, Constraint, BusinessRule
  ├─ BusinessNeed ←M:N→ BusinessObjective ←M:N→ StakeholderNeed
  │                                          ↑ M:N
  │                                     Stakeholder
  ├─ Risk (project-scoped; optional related_to text)
  ├─ StakeholderNeed → FunctionalRequirement (FR)
  ├─ StakeholderNeed → NonFunctionalRequirement (NFR)
  ├─ StakeholderNeed → Feature → Scenario (BDD)
  ├─ StateFlow, SwimlaneFlow, Architecture
  └─ ChangeRequest (optional affected subject)
```

Traceability lineage (Package 3 RTM):
**Business Need → Business Objective → Stakeholder Need → Functional Requirement / BDD Feature**.

## Tables (domain)

### `architectures`

C4 architecture model (1 per project).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `elements` | TEXT | YES |  |  |
| `relationships` | TEXT | YES |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `layout` | TEXT | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `project_id` → `projects.id`

### `assumptions`

Beliefs treated as true until proven.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `status` | varchar | NO |  | 'open' |
| `source` | varchar | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

### `business_need_business_objective`

M:N — Need ↔ Objective.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `business_need_id` | INTEGER | NO | FK→business_needs.id |  |
| `business_objective_id` | INTEGER | NO | FK→business_objectives.id |  |
| `is_primary` | tinyint(1) | NO |  | '0' |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |

**Foreign keys**

- `business_objective_id` → `business_objectives.id`
- `business_need_id` → `business_needs.id`

### `business_objective_stakeholder_need`

M:N — Business Objective ↔ Stakeholder Need.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `business_objective_id` | INTEGER | NO | FK→business_objectives.id |  |
| `stakeholder_need_id` | INTEGER | NO | FK→stakeholder_needs.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |

**Foreign keys**

- `stakeholder_need_id` → `stakeholder_needs.id`
- `business_objective_id` → `business_objectives.id`

### `business_needs`

Business problems/opportunities; coded BN-n.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `rationale` | TEXT | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `need_type` | varchar | YES |  |  |
| `impact` | TEXT | YES |  |  |
| `do_nothing_consequence` | TEXT | YES |  |  |
| `number` | INTEGER | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

> No requirements `status_id` or MoSCoW `priority_id` — a Business Need is a raw problem/opportunity (BACCM), not a managed requirements lifecycle artifact.

### `business_objectives`

Measurable outcomes (SMART); coded BO-n.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `success_measure` | TEXT | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `potential_value` | TEXT | YES |  |  |
| `number` | INTEGER | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

> No requirements `status_id` or MoSCoW `priority_id` — objectives are strategic intent (BABOK §6.2), not backlog sequencing or requirements states.

### `business_rules`

Policies the solution must respect.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `status` | varchar | NO |  | 'draft' |
| `source` | varchar | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

### `change_requests`

Governed change intake / impact on spine subjects.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `number` | INTEGER | YES |  |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `problem` | TEXT | NO |  |  |
| `proposed_change` | TEXT | NO |  |  |
| `requestor` | varchar | YES |  |  |
| `impact_level` | varchar | NO |  | 'medium' |
| `impact_notes` | TEXT | YES |  |  |
| `affected_type` | varchar | YES |  |  |
| `affected_id` | INTEGER | YES |  |  |
| `priority_id` | INTEGER | YES | FK→priorities.id |  |
| `status` | varchar | NO |  | 'draft' |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `priority_id` → `priorities.id`
- `project_id` → `projects.id`

### `constraints`

Hard limits (NFR / boundary obligations).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `status` | varchar | NO |  | 'active' |
| `source` | varchar | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

### `features`

BDD feature packages linked to a stakeholder need.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `number` | INTEGER | YES |  |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `stakeholder_need_id` | INTEGER | YES | FK→stakeholder_needs.id |  |
| `priority_id` | INTEGER | YES | FK→priorities.id |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `body` | TEXT | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `priority_id` → `priorities.id`
- `stakeholder_need_id` → `stakeholder_needs.id`
- `project_id` → `projects.id`

### `functional_requirements`

Solution functional requirements; coded FR-n.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `number` | INTEGER | YES |  |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `stakeholder_need_id` | INTEGER | YES | FK→stakeholder_needs.id |  |
| `statement` | TEXT | NO |  |  |
| `trigger` | TEXT | YES |  |  |
| `acceptance_criteria` | TEXT | YES |  |  |
| `priority_id` | INTEGER | YES | FK→priorities.id |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `priority_id` → `priorities.id`
- `stakeholder_need_id` → `stakeholder_needs.id`
- `project_id` → `projects.id`

### `non_functional_requirements`

Solution quality-of-service requirements; coded NFR-n. Sibling of FR under Stakeholder Need (or approved Change Request).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `number` | INTEGER | YES |  |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `stakeholder_need_id` | INTEGER | YES | FK→stakeholder_needs.id |  |
| `change_request_id` | INTEGER | YES | FK→change_requests.id |  |
| `category` | varchar(64) | NO |  |  |
| `description` | TEXT | NO |  |  |
| `acceptance_criteria` | TEXT | YES |  |  |
| `priority_id` | INTEGER | YES | FK→priorities.id |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `priority_id` → `priorities.id`
- `change_request_id` → `change_requests.id`
- `stakeholder_need_id` → `stakeholder_needs.id`
- `project_id` → `projects.id`

### `priorities`

Shared MoSCoW priority lookup.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `code` | varchar | NO |  |  |
| `sort_order` | INTEGER | NO |  | '0' |
| `description` | TEXT | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `is_system` | tinyint(1) | NO |  | '0' |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`

### `projects`

Delivery effort container for the need spine.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `code` | varchar | YES |  |  |
| `workspace_id` | INTEGER | NO | FK→workspaces.id |  |
| `description` | TEXT | YES |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `workspace_id` → `workspaces.id`

### `risks`

Risk register (likelihood × impact); optional free-text related_to until structured subject links.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `number` | INTEGER | YES |  |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `category` | varchar | NO |  | 'technical' |
| `likelihood` | varchar | NO |  | 'medium' |
| `impact` | varchar | NO |  | 'medium' |
| `response` | varchar | YES |  |  |
| `treatment` | TEXT | YES |  |  |
| `trigger` | varchar | YES |  |  |
| `owner` | varchar | YES |  |  |
| `status` | varchar | NO |  | 'open' |
| `source` | varchar | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `related_to` | varchar | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

### `role_entity_permissions`

Per-entity CRUD permissions for a role.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `role_id` | INTEGER | NO | FK→roles.id |  |
| `entity` | varchar | NO |  |  |
| `can_view` | tinyint(1) | NO |  | '0' |
| `can_create` | tinyint(1) | NO |  | '0' |
| `can_update` | tinyint(1) | NO |  | '0' |
| `can_delete` | tinyint(1) | NO |  | '0' |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |

**Foreign keys**

- `role_id` → `roles.id`

### `roles`

RBAC roles.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `slug` | varchar | NO |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |

### `scenarios`

Gherkin scenarios under a feature.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `feature_id` | INTEGER | NO | FK→features.id |  |
| `is_outline` | tinyint(1) | NO |  | '0' |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `body` | TEXT | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `feature_id` → `features.id`

### `scope_items`

In/out solution scope boundaries.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `direction` | varchar | NO |  | 'in' |
| `description` | TEXT | YES |  |  |
| `business_need_id` | INTEGER | YES | FK→business_needs.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `business_need_id` → `business_needs.id`
- `project_id` → `projects.id`

### `stakeholder_needs`

Stakeholder-level requirements / stories; coded SN-n.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `priority_id` | INTEGER | YES | FK→priorities.id |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `number` | INTEGER | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `priority_id` → `priorities.id`
- `project_id` → `projects.id`

### `stakeholder_stakeholder_need`

M:N — Stakeholder ↔ Stakeholder Need.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `stakeholder_id` | INTEGER | NO | FK→stakeholders.id |  |
| `stakeholder_need_id` | INTEGER | NO | FK→stakeholder_needs.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |

**Foreign keys**

- `stakeholder_need_id` → `stakeholder_needs.id`
- `stakeholder_id` → `stakeholders.id`

### `stakeholders`

Roles/people impacted by or influencing the change.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `type` | varchar | YES |  |  |
| `influence` | varchar | YES |  |  |
| `interest` | varchar | YES |  |  |
| `notes` | TEXT | YES |  |  |
| `is_system` | tinyint(1) | NO |  | '0' |
| `system_key` | varchar | YES |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `project_id` → `projects.id`

### `state_flows`

State transition diagrams (JSON transitions).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `transitions` | TEXT | YES |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `project_id` → `projects.id`

### `statuses`

Shared entity status lookup.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `code` | varchar | NO |  |  |
| `sort_order` | INTEGER | NO |  | '0' |
| `description` | TEXT | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |
| `is_system` | tinyint(1) | NO |  | '0' |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`

### `strategic_baselines`

Current / future state and change strategy narrative (1 per project).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `current_state` | TEXT | YES |  |  |
| `future_state` | TEXT | YES |  |  |
| `change_strategy` | TEXT | YES |  |  |
| `status` | varchar | NO |  | 'draft' |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `project_id` → `projects.id`

### `swimlane_flows`

Process swimlane diagrams (JSON elements).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `title` | varchar | NO |  |  |
| `project_id` | INTEGER | NO | FK→projects.id |  |
| `description` | TEXT | YES |  |  |
| `direction` | varchar | NO |  | 'TB' |
| `elements` | TEXT | YES |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `project_id` → `projects.id`

### `tenants`

Organization / tenancy boundary.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `slug` | varchar | NO |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`

### `users`

Application users (tenant + role).

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `email` | varchar | NO |  |  |
| `email_verified_at` | datetime | YES |  |  |
| `password` | varchar | NO |  |  |
| `remember_token` | varchar | YES |  |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `two_factor_secret` | TEXT | YES |  |  |
| `two_factor_recovery_codes` | TEXT | YES |  |  |
| `two_factor_confirmed_at` | datetime | YES |  |  |
| `role_id` | INTEGER | YES | FK→roles.id |  |
| `tenant_id` | INTEGER | YES | FK→tenants.id |  |
| `workspace_id` | INTEGER | YES | FK→workspaces.id |  |

**Foreign keys**

- `workspace_id` → `workspaces.id`
- `tenant_id` → `tenants.id`
- `role_id` → `roles.id`

### `workspaces`

Workspace under a tenant; contains projects.

| Column | Type | Null | Key | Default |
| --- | --- | --- | --- | --- |
| `id` | INTEGER | NO | PK |  |
| `name` | varchar | NO |  |  |
| `slug` | varchar | NO |  |  |
| `tenant_id` | INTEGER | NO | FK→tenants.id |  |
| `description` | TEXT | YES |  |  |
| `status_id` | INTEGER | YES | FK→statuses.id |  |
| `created_at` | datetime | YES |  |  |
| `updated_at` | datetime | YES |  |  |
| `created_by` | INTEGER | YES | FK→users.id |  |
| `updated_by` | INTEGER | YES | FK→users.id |  |
| `deleted_by` | INTEGER | YES | FK→users.id |  |
| `deleted_at` | datetime | YES |  |  |

**Foreign keys**

- `deleted_by` → `users.id`
- `updated_by` → `users.id`
- `created_by` → `users.id`
- `status_id` → `statuses.id`
- `tenant_id` → `tenants.id`

## Tables (infrastructure / framework)

Present in the database but not part of the BA need-spine domain:

- `cache` — key, value, expiration
- `cache_locks` — key, owner, expiration
- `failed_jobs` — id, uuid, connection, queue, payload, exception, failed_at
- `job_batches` — id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at
- `jobs` — id, queue, payload, attempts, reserved_at, available_at, created_at
- `migrations` — id, migration, batch
- `password_reset_tokens` — email, token, created_at
- `personal_access_tokens` — id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at
- `sessions` — id, user_id, ip_address, user_agent, payload, last_activity
- `passkeys` — id, user_id, name, credential_id, credential, last_used_at, created_at, updated_at
