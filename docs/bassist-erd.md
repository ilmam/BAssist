# BAssist Entity-Relationship Diagram

_Generated from live SQLite schema. Render source: [`bassist-erd.mmd`](bassist-erd.mmd)._

**Exportable images (share with mentor):**

- [`bassist-erd.png`](bassist-erd.png)
- [`bassist-erd.svg`](bassist-erd.svg)

Full column-level dictionary: [`bassist-data-dictionary.md`](bassist-data-dictionary.md).

## Need spine ERD

![BAssist need-spine ERD](bassist-erd.png)

<details>
<summary>Mermaid source</summary>


```mermaid
erDiagram
    TENANTS ||--o{ WORKSPACES : contains
    WORKSPACES ||--o{ PROJECTS : contains
    PROJECTS ||--o| STRATEGIC_BASELINES : has
    PROJECTS ||--o{ SCOPE_ITEMS : defines
    PROJECTS ||--o{ ASSUMPTIONS : records
    PROJECTS ||--o{ CONSTRAINTS : records
    PROJECTS ||--o{ BUSINESS_RULES : records
    PROJECTS ||--o{ BUSINESS_OBJECTIVES : has
    PROJECTS ||--o{ BUSINESS_NEEDS : has
    PROJECTS ||--o{ STAKEHOLDERS : has
    PROJECTS ||--o{ STAKEHOLDER_NEEDS : has
    PROJECTS ||--o{ FUNCTIONAL_REQUIREMENTS : has
    PROJECTS ||--o{ FEATURES : has
    PROJECTS ||--o{ STATE_FLOWS : has
    PROJECTS ||--o{ SWIMLANE_FLOWS : has
    PROJECTS ||--o| ARCHITECTURES : has
    PROJECTS ||--o{ RISKS : assesses
    PROJECTS ||--o{ CHANGE_REQUESTS : tracks

    BUSINESS_OBJECTIVES }o--o{ BUSINESS_NEEDS : "M:N via pivot"
    BUSINESS_NEEDS }o--o{ STAKEHOLDER_NEEDS : "M:N via pivot"
    STAKEHOLDERS }o--o{ STAKEHOLDER_NEEDS : "M:N via pivot"
    STAKEHOLDER_NEEDS ||--o{ FUNCTIONAL_REQUIREMENTS : specifies
    STAKEHOLDER_NEEDS ||--o{ FEATURES : packages
    FEATURES ||--o{ SCENARIOS : contains
    BUSINESS_NEEDS ||--o{ SCOPE_ITEMS : optional_link

    STATUSES ||--o{ PROJECTS : status
    STATUSES ||--o{ BUSINESS_OBJECTIVES : status
    STATUSES ||--o{ BUSINESS_NEEDS : status
    STATUSES ||--o{ STAKEHOLDER_NEEDS : status
    STATUSES ||--o{ FEATURES : status
    PRIORITIES ||--o{ BUSINESS_OBJECTIVES : moscow
    PRIORITIES ||--o{ BUSINESS_NEEDS : moscow
    PRIORITIES ||--o{ STAKEHOLDER_NEEDS : moscow
    PRIORITIES ||--o{ FEATURES : moscow
    PRIORITIES ||--o{ FUNCTIONAL_REQUIREMENTS : moscow

    TENANTS {
        int id PK
        string name
        int status_id FK
    }
    WORKSPACES {
        int id PK
        string name
        int tenant_id FK
        int status_id FK
    }
    PROJECTS {
        int id PK
        string name
        string code
        int workspace_id FK
        int status_id FK
    }
    BUSINESS_OBJECTIVES {
        int id PK
        int number
        string title
        text success_measure
        text potential_value
        int project_id FK
        int priority_id FK
        int status_id FK
    }
    BUSINESS_NEEDS {
        int id PK
        int number
        string title
        string need_type
        text rationale
        text impact
        text do_nothing_consequence
        int project_id FK
        int priority_id FK
        int status_id FK
    }
    STAKEHOLDERS {
        int id PK
        string name
        string type
        string influence
        string interest
        int project_id FK
        bool is_system
    }
    STAKEHOLDER_NEEDS {
        int id PK
        int number
        string title
        text description
        int project_id FK
        int priority_id FK
        int status_id FK
    }
    FUNCTIONAL_REQUIREMENTS {
        int id PK
        int number
        string title
        text statement
        text trigger
        text acceptance_criteria
        int project_id FK
        int stakeholder_need_id FK
        int priority_id FK
        int status_id FK
    }
    FEATURES {
        int id PK
        int number
        string title
        int project_id FK
        int stakeholder_need_id FK
        int priority_id FK
        int status_id FK
    }
    SCENARIOS {
        int id PK
        string title
        text body
        int feature_id FK
        int status_id FK
    }
    STRATEGIC_BASELINES {
        int id PK
        int project_id FK
        text current_state
        text future_state
        text change_strategy
        string status
    }
    SCOPE_ITEMS {
        int id PK
        string title
        string direction
        int project_id FK
        int business_need_id FK
    }
    ASSUMPTIONS {
        int id PK
        string title
        string status
        int project_id FK
    }
    CONSTRAINTS {
        int id PK
        string title
        string status
        int project_id FK
    }
    BUSINESS_RULES {
        int id PK
        string title
        string status
        int project_id FK
    }
    STATE_FLOWS {
        int id PK
        string title
        json transitions
        int project_id FK
    }
    SWIMLANE_FLOWS {
        int id PK
        string title
        json elements
        int project_id FK
    }
    ARCHITECTURES {
        int id PK
        json model
        int project_id FK
    }
    RISKS {
        int id PK
        int number
        string title
        string category
        string likelihood
        string impact
        string response
        string status
        string related_to
        int project_id FK
    }
    CHANGE_REQUESTS {
        int id PK
        int number
        string title
        text problem
        text proposed_change
        string affected_type
        int affected_id
        string status
        int project_id FK
    }
    STATUSES {
        int id PK
        string name
    }
    PRIORITIES {
        int id PK
        string name
    }
```


</details>
