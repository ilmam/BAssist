<?php

/**
 * Export SQLite schema as Markdown data dictionary + Mermaid ERD (domain focus).
 *
 * Usage: php database/scripts/export_schema_dictionary.php
 */

$root = dirname(__DIR__, 2);
$dbPath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';
$outDir = $root.DIRECTORY_SEPARATOR.'docs';

if (! is_file($dbPath)) {
    fwrite(STDERR, "SQLite database not found at {$dbPath}\n");
    exit(1);
}

$pdo = new PDO('sqlite:'.$dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $pdo->query(
    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
)->fetchAll(PDO::FETCH_COLUMN);

$infra = [
    'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
    'migrations', 'password_reset_tokens', 'personal_access_tokens',
    'sessions', 'passkeys',
];

$domainTables = array_values(array_diff($tables, $infra));

$schema = [];
foreach ($tables as $table) {
    $cols = $pdo->query("PRAGMA table_info('{$table}')")->fetchAll(PDO::FETCH_ASSOC);
    $fks = $pdo->query("PRAGMA foreign_key_list('{$table}')")->fetchAll(PDO::FETCH_ASSOC);
    $schema[$table] = ['columns' => $cols, 'foreign_keys' => $fks];
}

$entityNotes = [
    'tenants' => 'Organization / tenancy boundary.',
    'workspaces' => 'Workspace under a tenant; contains projects.',
    'projects' => 'Delivery effort container for the need spine.',
    'business_objectives' => 'Measurable outcomes (SMART); coded BO-n.',
    'business_needs' => 'Business problems/opportunities; coded BN-n.',
    'business_need_business_objective' => 'M:N — Need ↔ Objective.',
    'stakeholders' => 'Roles/people impacted by or influencing the change.',
    'stakeholder_needs' => 'Stakeholder-level requirements / stories; coded SN-n.',
    'business_need_stakeholder_need' => 'M:N — Business Need ↔ Stakeholder Need.',
    'stakeholder_stakeholder_need' => 'M:N — Stakeholder ↔ Stakeholder Need.',
    'functional_requirements' => 'Solution functional requirements; coded FR-n.',
    'features' => 'BDD feature packages linked to a stakeholder need.',
    'scenarios' => 'Gherkin scenarios under a feature.',
    'strategic_baselines' => 'Current / future state and change strategy narrative (1 per project).',
    'scope_items' => 'In/out solution scope boundaries.',
    'assumptions' => 'Beliefs treated as true until proven.',
    'constraints' => 'Hard limits (NFR / boundary obligations).',
    'business_rules' => 'Policies the solution must respect.',
    'state_flows' => 'State transition diagrams (JSON transitions).',
    'swimlane_flows' => 'Process swimlane diagrams (JSON elements).',
    'architectures' => 'C4 architecture model (1 per project).',
    'risks' => 'Risk register (likelihood × impact); optional free-text related_to until structured subject links.',
    'change_requests' => 'Governed change intake / impact on spine subjects.',
    'statuses' => 'Shared entity status lookup.',
    'priorities' => 'Shared MoSCoW priority lookup.',
    'users' => 'Application users (tenant + role).',
    'roles' => 'RBAC roles.',
    'role_entity_permissions' => 'Per-entity CRUD permissions for a role.',
];

$generatedAt = date('Y-m-d H:i');

// --- Data dictionary Markdown ---
$md = [];
$md[] = '# BAssist Data Dictionary';
$md[] = '';
$md[] = '_Generated from live SQLite schema on '.$generatedAt.'._';
$md[] = '';
$md[] = 'Companion files:';
$md[] = '';
$md[] = '- [`bassist-erd.md`](bassist-erd.md) — Mermaid ERD (paste into [mermaid.live](https://mermaid.live) for PNG/SVG)';
$md[] = '- [`bassist-data-dictionary.csv`](bassist-data-dictionary.csv) — Excel-friendly column list';
$md[] = '';
$md[] = '## Domain model overview (need spine)';
$md[] = '';
$md[] = '```';
$md[] = 'Tenant → Workspace → Project';
$md[] = '  ├─ StrategicBaseline (1:1)';
$md[] = '  ├─ ScopeItem, Assumption, Constraint, BusinessRule';
$md[] = '  ├─ BusinessObjective ←M:N→ BusinessNeed ←M:N→ StakeholderNeed';
$md[] = '  │                              ↑ M:N';
$md[] = '  │                         Stakeholder';
$md[] = '  ├─ Risk (project-scoped; optional related_to text)';
$md[] = '  ├─ StakeholderNeed → FunctionalRequirement (FR)';
$md[] = '  ├─ StakeholderNeed → Feature → Scenario (BDD)';
$md[] = '  ├─ StateFlow, SwimlaneFlow, Architecture';
$md[] = '  └─ ChangeRequest (optional affected subject)';
$md[] = '```';
$md[] = '';
$md[] = 'Traceability lineage (Package 3 RTM):';
$md[] = '**Business Objective → Business Need → Stakeholder Need → Functional Requirement / BDD Feature**.';
$md[] = '';
$md[] = '## Tables (domain)';
$md[] = '';

foreach ($domainTables as $table) {
    $note = $entityNotes[$table] ?? '';
    $md[] = '### `'.$table.'`';
    if ($note !== '') {
        $md[] = '';
        $md[] = $note;
    }
    $md[] = '';
    $md[] = '| Column | Type | Null | Key | Default |';
    $md[] = '| --- | --- | --- | --- | --- |';
    foreach ($schema[$table]['columns'] as $col) {
        $key = $col['pk'] ? 'PK' : '';
        foreach ($schema[$table]['foreign_keys'] as $fk) {
            if ($fk['from'] === $col['name']) {
                $key = trim($key.' FK→'.$fk['table'].'.'.$fk['to']);
            }
        }
        $md[] = '| `'.$col['name'].'` | '.$col['type'].' | '.($col['notnull'] ? 'NO' : 'YES').' | '.$key.' | '.($col['dflt_value'] ?? '').' |';
    }
    if ($schema[$table]['foreign_keys'] !== []) {
        $md[] = '';
        $md[] = '**Foreign keys**';
        $md[] = '';
        foreach ($schema[$table]['foreign_keys'] as $fk) {
            $md[] = '- `'.$fk['from'].'` → `'.$fk['table'].'.'.$fk['to'].'`';
        }
    }
    $md[] = '';
}

$md[] = '## Tables (infrastructure / framework)';
$md[] = '';
$md[] = 'Present in the database but not part of the BA need-spine domain:';
$md[] = '';
foreach ($infra as $table) {
    if (! isset($schema[$table])) {
        continue;
    }
    $cols = array_map(fn ($c) => $c['name'], $schema[$table]['columns']);
    $md[] = '- `'.$table.'` — '.implode(', ', $cols);
}
$md[] = '';

file_put_contents($outDir.DIRECTORY_SEPARATOR.'bassist-data-dictionary.md', implode("\n", $md));

// --- CSV (Excel-friendly) ---
$csvPath = $outDir.DIRECTORY_SEPARATOR.'bassist-data-dictionary.csv';
$csv = fopen($csvPath, 'w');
fputcsv($csv, ['table', 'description', 'column', 'type', 'nullable', 'key', 'default', 'foreign_key']);
foreach ($domainTables as $table) {
    $note = $entityNotes[$table] ?? '';
    foreach ($schema[$table]['columns'] as $col) {
        $key = $col['pk'] ? 'PK' : '';
        $fkLabel = '';
        foreach ($schema[$table]['foreign_keys'] as $fk) {
            if ($fk['from'] === $col['name']) {
                $key = trim($key.' FK');
                $fkLabel = $fk['table'].'.'.$fk['to'];
            }
        }
        fputcsv($csv, [
            $table,
            $note,
            $col['name'],
            $col['type'],
            $col['notnull'] ? 'NO' : 'YES',
            $key,
            $col['dflt_value'] ?? '',
            $fkLabel,
        ]);
    }
}
fclose($csv);

// --- Mermaid ERD (domain only, readable) ---
$erd = [];
$erd[] = '# BAssist Entity-Relationship Diagram';
$erd[] = '';
$erd[] = '_Generated on '.$generatedAt.'. Render with Mermaid (GitHub, VS Code, [mermaid.live](https://mermaid.live))._';
$erd[] = '';
$erd[] = 'Full column-level dictionary: [`bassist-data-dictionary.md`](bassist-data-dictionary.md).';
$erd[] = '';
$erd[] = '## Need spine ERD';
$erd[] = '';
$erd[] = '```mermaid';
$erd[] = 'erDiagram';
$erd[] = '    TENANTS ||--o{ WORKSPACES : contains';
$erd[] = '    WORKSPACES ||--o{ PROJECTS : contains';
$erd[] = '    PROJECTS ||--o| STRATEGIC_BASELINES : has';
$erd[] = '    PROJECTS ||--o{ SCOPE_ITEMS : defines';
$erd[] = '    PROJECTS ||--o{ ASSUMPTIONS : records';
$erd[] = '    PROJECTS ||--o{ CONSTRAINTS : records';
$erd[] = '    PROJECTS ||--o{ BUSINESS_RULES : records';
$erd[] = '    PROJECTS ||--o{ BUSINESS_OBJECTIVES : has';
$erd[] = '    PROJECTS ||--o{ BUSINESS_NEEDS : has';
$erd[] = '    PROJECTS ||--o{ STAKEHOLDERS : has';
$erd[] = '    PROJECTS ||--o{ STAKEHOLDER_NEEDS : has';
$erd[] = '    PROJECTS ||--o{ FUNCTIONAL_REQUIREMENTS : has';
$erd[] = '    PROJECTS ||--o{ FEATURES : has';
$erd[] = '    PROJECTS ||--o{ STATE_FLOWS : has';
$erd[] = '    PROJECTS ||--o{ SWIMLANE_FLOWS : has';
$erd[] = '    PROJECTS ||--o| ARCHITECTURES : has';
$erd[] = '    PROJECTS ||--o{ RISKS : assesses';
$erd[] = '    PROJECTS ||--o{ CHANGE_REQUESTS : tracks';
$erd[] = '';
$erd[] = '    BUSINESS_OBJECTIVES }o--o{ BUSINESS_NEEDS : "M:N via pivot"';
$erd[] = '    BUSINESS_NEEDS }o--o{ STAKEHOLDER_NEEDS : "M:N via pivot"';
$erd[] = '    STAKEHOLDERS }o--o{ STAKEHOLDER_NEEDS : "M:N via pivot"';
$erd[] = '    STAKEHOLDER_NEEDS ||--o{ FUNCTIONAL_REQUIREMENTS : specifies';
$erd[] = '    STAKEHOLDER_NEEDS ||--o{ FEATURES : packages';
$erd[] = '    FEATURES ||--o{ SCENARIOS : contains';
$erd[] = '    BUSINESS_NEEDS ||--o{ SCOPE_ITEMS : optional_link';
$erd[] = '';
$erd[] = '    STATUSES ||--o{ PROJECTS : status';
$erd[] = '    STATUSES ||--o{ STAKEHOLDER_NEEDS : status';
$erd[] = '    STATUSES ||--o{ FEATURES : status';
$erd[] = '    PRIORITIES ||--o{ STAKEHOLDER_NEEDS : moscow';
$erd[] = '    PRIORITIES ||--o{ FEATURES : moscow';
$erd[] = '    PRIORITIES ||--o{ FUNCTIONAL_REQUIREMENTS : moscow';
$erd[] = '';
$erd[] = '    TENANTS {';
$erd[] = '        int id PK';
$erd[] = '        string name';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    WORKSPACES {';
$erd[] = '        int id PK';
$erd[] = '        string name';
$erd[] = '        int tenant_id FK';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    PROJECTS {';
$erd[] = '        int id PK';
$erd[] = '        string name';
$erd[] = '        string code';
$erd[] = '        int workspace_id FK';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    BUSINESS_OBJECTIVES {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        text success_measure';
$erd[] = '        text potential_value';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    BUSINESS_NEEDS {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        string need_type';
$erd[] = '        text rationale';
$erd[] = '        text impact';
$erd[] = '        text do_nothing_consequence';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    STAKEHOLDERS {';
$erd[] = '        int id PK';
$erd[] = '        string name';
$erd[] = '        string type';
$erd[] = '        string influence';
$erd[] = '        string interest';
$erd[] = '        int project_id FK';
$erd[] = '        bool is_system';
$erd[] = '    }';
$erd[] = '    STAKEHOLDER_NEEDS {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        text description';
$erd[] = '        int project_id FK';
$erd[] = '        int priority_id FK';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    FUNCTIONAL_REQUIREMENTS {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        text statement';
$erd[] = '        text trigger';
$erd[] = '        text acceptance_criteria';
$erd[] = '        int project_id FK';
$erd[] = '        int stakeholder_need_id FK';
$erd[] = '        int priority_id FK';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    FEATURES {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        int project_id FK';
$erd[] = '        int stakeholder_need_id FK';
$erd[] = '        int priority_id FK';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    SCENARIOS {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        text body';
$erd[] = '        int feature_id FK';
$erd[] = '        int status_id FK';
$erd[] = '    }';
$erd[] = '    STRATEGIC_BASELINES {';
$erd[] = '        int id PK';
$erd[] = '        int project_id FK';
$erd[] = '        text current_state';
$erd[] = '        text future_state';
$erd[] = '        text change_strategy';
$erd[] = '        string status';
$erd[] = '    }';
$erd[] = '    SCOPE_ITEMS {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        string direction';
$erd[] = '        int project_id FK';
$erd[] = '        int business_need_id FK';
$erd[] = '    }';
$erd[] = '    ASSUMPTIONS {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        string status';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    CONSTRAINTS {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        string status';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    BUSINESS_RULES {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        string status';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    STATE_FLOWS {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        json transitions';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    SWIMLANE_FLOWS {';
$erd[] = '        int id PK';
$erd[] = '        string title';
$erd[] = '        json elements';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    ARCHITECTURES {';
$erd[] = '        int id PK';
$erd[] = '        json model';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    RISKS {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        string category';
$erd[] = '        string likelihood';
$erd[] = '        string impact';
$erd[] = '        string response';
$erd[] = '        string status';
$erd[] = '        string related_to';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    CHANGE_REQUESTS {';
$erd[] = '        int id PK';
$erd[] = '        int number';
$erd[] = '        string title';
$erd[] = '        text problem';
$erd[] = '        text proposed_change';
$erd[] = '        string affected_type';
$erd[] = '        int affected_id';
$erd[] = '        string status';
$erd[] = '        int project_id FK';
$erd[] = '    }';
$erd[] = '    STATUSES {';
$erd[] = '        int id PK';
$erd[] = '        string name';
$erd[] = '    }';
$erd[] = '    PRIORITIES {';
$erd[] = '        int id PK';
$erd[] = '        string name';
$erd[] = '    }';
$erd[] = '```';
$erd[] = '';
$erd[] = '## Pivot tables';
$erd[] = '';
$erd[] = '| Pivot | Links |';
$erd[] = '| --- | --- |';
$erd[] = '| `business_need_business_objective` | business_needs ↔ business_objectives |';
$erd[] = '| `business_need_stakeholder_need` | business_needs ↔ stakeholder_needs |';
$erd[] = '| `stakeholder_stakeholder_need` | stakeholders ↔ stakeholder_needs |';
$erd[] = '';
$erd[] = '## How to share with a mentor';
$erd[] = '';
$erd[] = '1. Open [`bassist-erd.png`](bassist-erd.png) / [`bassist-erd.svg`](bassist-erd.svg), or paste the Mermaid block into https://mermaid.live.';
$erd[] = '2. Share `bassist-data-dictionary.md` as the column-level reference.';
$erd[] = '3. Re-generate after schema changes: `php database/scripts/export_schema_dictionary.php` then render `bassist-erd.mmd` with mermaid-cli.';
$erd[] = '';

$erdMarkdown = implode("\n", $erd);
file_put_contents($outDir.DIRECTORY_SEPARATOR.'bassist-erd.md', $erdMarkdown);

// Standalone .mmd for mermaid-cli (body between fences only).
$mermaidBody = '';
if (preg_match('/```mermaid\n(.*)\n```/s', $erdMarkdown, $m)) {
    $mermaidBody = trim($m[1])."\n";
}
file_put_contents($outDir.DIRECTORY_SEPARATOR.'bassist-erd.mmd', $mermaidBody);

// Mentor-facing wrapper that embeds the PNG when present.
$erdShare = [];
$erdShare[] = '# BAssist Entity-Relationship Diagram';
$erdShare[] = '';
$erdShare[] = '_Generated from live SQLite schema. Render source: [`bassist-erd.mmd`](bassist-erd.mmd)._';
$erdShare[] = '';
$erdShare[] = '**Exportable images (share with mentor):**';
$erdShare[] = '';
$erdShare[] = '- [`bassist-erd.png`](bassist-erd.png)';
$erdShare[] = '- [`bassist-erd.svg`](bassist-erd.svg)';
$erdShare[] = '';
$erdShare[] = 'Full column-level dictionary: [`bassist-data-dictionary.md`](bassist-data-dictionary.md).';
$erdShare[] = '';
$erdShare[] = '## Need spine ERD';
$erdShare[] = '';
$erdShare[] = '![BAssist need-spine ERD](bassist-erd.png)';
$erdShare[] = '';
$erdShare[] = '<details>';
$erdShare[] = '<summary>Mermaid source</summary>';
$erdShare[] = '';
$erdShare[] = '';
$erdShare[] = '```mermaid';
$erdShare[] = $mermaidBody !== '' ? rtrim($mermaidBody) : '';
$erdShare[] = '```';
$erdShare[] = '';
$erdShare[] = '';
$erdShare[] = '</details>';
$erdShare[] = '';
file_put_contents($outDir.DIRECTORY_SEPARATOR.'bassist-erd.md', implode("\n", $erdShare));

echo "Wrote docs/bassist-data-dictionary.md\n";
echo "Wrote docs/bassist-data-dictionary.csv\n";
echo "Wrote docs/bassist-erd.md\n";
echo "Wrote docs/bassist-erd.mmd\n";
echo 'Domain tables: '.count($domainTables).' | Infra tables: '.count(array_intersect($tables, $infra))."\n";
