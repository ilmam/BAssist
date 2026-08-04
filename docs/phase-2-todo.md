# Phase 2 — Todo (requirements backlog)

**Status:** Not started (queue)  
**Theme:** Deeper lifecycle / SDLC links + requirement-scoped governance  
**Principle:** Encourage, don’t police — soft gaps and visibility before hard locks  
**Sources:** [`need-spine-product-brief-v2.md`](need-spine-product-brief-v2.md) §6, [`need-spine-product-brief.md`](need-spine-product-brief.md) §Phase 2, Risk subject-association plan (Aug 2026)

---

## Priority legend

| Priority | Meaning |
|----------|---------|
| **P0** | Next shippable slice — unblock contextual risk management |
| **P1** | Same phase — core Phase 2 outcomes from product brief |
| **P2** | Polish / carry-forward from Phase 1.x backlog |
| **P3** | Later within Phase 2 — only if demand appears |

---

## Already shipped (context — do not re-queue)

These were listed in early Phase 2 / Phase 1.5 roadmaps but are **done** in the current codebase:

- MoSCoW via Priority master (`must` / `should` / `could` / `wont`) — see [`phase-1.x-todo.md`](phase-1.x-todo.md) §3
- Risk register **v1** (project-scoped; `project_id` + free-text `source` + interim `related_to`)
- Change Request + downstream cascade preview (partial “change impact”)
- Strategic Baseline entity + Strategy nav folder (**v1 — 1:1 per project**; multi-version locked in §10)
- Project readiness / gap dashboard v1
- BABOK document suite (Package 1 incl. Risk Assessment Step C)

---

## 1. Risk — requirement-scoped subject linking — **TODO** · **P0**

### Problem

Risk is only project-scoped today (`risks.project_id` + free-text `source`). An interim **`related_to`** string (nullable, max 255) lets users note which BO / BN / SN / FR / Feature a risk relates to in free text (e.g. `BO-1, BN-2 Improve delivery…`) until structured linking ships — **`source` stays for origin/assumption notes**. When §1 lands, migrate or replace `related_to` with `subject_type` / `subject_id` + computed `subject_label`; existing free-text values can seed manual relinking or a one-off migration heuristic.

Uncertainty should tie to requirement levels the same way Change Requests do, with contextual capture from spine entity pages.

**Reference pattern:** Change Request `affected_type` / `affected_id` — see `app/Support/ChangeRequestAffectedType.php`, `app/Services/ChangeRequestAffectedService.php`, `resources/views/pages/change_requests/partials/request-change-button.blade.php`.

### Data model

| Column | Type | Notes |
|--------|------|-------|
| `subject_type` | string, nullable | Slug vocabulary aligned with CR (see allowed types below) |
| `subject_id` | unsignedBigInteger, nullable | FK to resolved subject row |
| Index | `(subject_type, subject_id)` | Reverse lookup support in §2 |

- **`project_id`:** Always required (unchanged). Denormalized for list filters, nav scoping, BABOK pack queries.
- **Naming:** Use `subject_*` (not `affected_*`) — CR *affects* a requirement; Risk *relates to* a requirement.
- **Keep `source`:** Free-text origin note (e.g. “Assumption: dealers have Wi‑Fi”, “Triggered by CR-012”) — complementary to structured link.
- **Replace `related_to`:** Interim free-text requirement reference column — superseded by structured subject; do not keep both long-term.
- **Migration:** Add columns + index; existing rows stay `subject_type/id = null` (valid project-level risks).
- **Shared abstraction:** Extract `RequirementSubjectType` + `RequirementSubjectService` from CR code; refactor CR to consume shared enum/service. Keep `cascadeFor()` CR-only.

### Allowed subject types (v1)

| Slug | Model | Include? |
|------|-------|----------|
| `business_objective` | BusinessObjective | ✅ |
| `business_need` | BusinessNeed | ✅ |
| `stakeholder_need` | StakeholderNeed | ✅ |
| `feature` | Feature | ✅ |
| `functional_requirement` | FunctionalRequirement | ✅ |
| `change_request` | ChangeRequest | ❌ — governance artifact, not a requirement level; mention CR in `source` and link to CR’s underlying requirement |
| `assumption` | Assumption | ❌ v1 — guardrails entity; defer to §2 |
| `constraint` / `business_rule` | Guardrails | ❌ v1 |
| `scenario` | Scenario | ❌ — too granular for register |

### UX

- **“Raise risk” button** — mirror `request-change-button.blade.php`:
  - Partial: `resources/views/pages/risks/partials/raise-risk-button.blade.php`
  - URL: `model_modal_path('Risk', 'create')?project_id=&subject_type=&subject_id=`
  - Permission: `entity_can('Risk', 'create')`
  - **Placement:** Same 10 blades as Request change — BO / BN / SN / FR / Feature × `details.blade.php` + `modals/view.blade.php`
- **Dedicated `RiskController`** (mirror `ChangeRequestController`):
  - `subjectOptions()` AJAX endpoint
  - Query prefill in `applyStickyContextDefaults()`
  - Subject-options script on risk form/modal (clone `affected-options-script.blade.php`)
- **Form fields:** `subject_type` (select) + `subject_id` (dynamic select driven by type + project)
- **Display:** Computed `subject_label` in list, details, view modal, BABOK risk partial (`resources/views/pages/projects/babok/partials/risk-assessment.blade.php`)
- **No cascade preview** on risk details in v1 (different semantics from CR — see §9)

### Validation

Risk has no Draft status (`RiskStatus::default()` = `open`). Do **not** copy CR’s “optional in Draft” rule.

| Scenario | Rule |
|----------|------|
| Create from risk list (no prefill) | Subject **optional** — allows portfolio/project-level risks |
| Create via “Raise risk” button | Subject **prefilled** (read-only or hidden + server-validated) |
| Status → `mitigated` or `closed` | Subject **required** |
| Status `open` / `realized` | Subject optional but encouraged |
| Cross-project integrity | Resolved subject must belong to same `project_id` |
| Pair consistency | If `subject_type` set, `subject_id` required (and vice versa) |

Repository gate pattern: mirror `ChangeRequestRepository::assertAffectedWhenRequired()` keyed off `RiskStatus`.

### Acceptance criteria

- [ ] Migration adds `subject_type`, `subject_id`, index; existing risks unchanged
- [ ] Shared `RequirementSubjectType` + `RequirementSubjectService`; CR refactored to use them
- [ ] Risk create/edit supports type + item select with AJAX options
- [ ] “Raise risk” appears on all five requirement entity types (details + view modal)
- [ ] Button prefill creates risk with correct subject; subject shown in list/details/BABOK
- [ ] Cannot close/mitigate without subject; cross-project subject rejected
- [ ] Tests mirror `tests/Unit/ChangeRequestFormTest.php` patterns in `tests/Unit/RiskTest.php`

### Implementation touch list

| Layer | Files |
|-------|-------|
| Migration | `database/migrations/*_add_risk_subject.php` |
| Support | `RequirementSubjectType.php`, `RequirementSubjectService.php`; refactor CR affected types/service |
| Backend | `Risk.php`, `RiskData.php`, `RiskViewData.php`, `RiskRepository.php`, **new** `RiskController.php` |
| Views | `risks/partials/raise-risk-button.blade.php`, `partials/subject-options-script.blade.php`, form/modal blades |
| Entity buttons | 10 blades under `business_objectives`, `business_needs`, `stakeholder_needs`, `features`, `functional_requirements` |
| Config | `config/crud.php`, `routes/web.php`, `lang/en/ui.php` |
| Docs | Update `resources/help/risks.md` |

---

## 2. Risk v2 — contextual polish — **TODO** · **P1**

Follow-on after §1 ships. Items were explicitly scoped as “v2 polish” in the Risk subject plan.

### 2.1 Reverse lookup panel on requirement pages — **TODO**

- On BO / BN / SN / FR / Feature **detail** pages, show **Related risks** panel (`Risk::where subject_type/id`)
- Read-only list with score band, status, link to risk detail/modal
- Does **not** imply CR-style cascade — direct links only

**Acceptance criteria:**

- [ ] Panel visible when ≥1 linked risk exists; empty state when none
- [ ] Respects entity view permissions

### 2.2 Readiness gap — unlinked critical risks — **TODO**

- Add info/warn-level gap in `ProjectReadinessService`: open Critical (score 9) risks with no `subject_type/id`
- Soft encouragement only — not a form validation block
- Existing critical-risk gates (response, treatment, accept rationale) unchanged

**Acceptance criteria:**

- [ ] Gap appears on project dashboard when condition met
- [ ] Links to filtered risk list

### 2.3 Assumption (and optionally Constraint) as subject types — **TODO**

- Extend `RequirementSubjectType` with `assumption` (and optionally `constraint`)
- Assumptions live in guardrails hub, not spine — justify only if guardrail↔risk linking becomes recurring
- Until then, continue using free-text `source` field

**Acceptance criteria:**

- [ ] Assumption appears in subject type select when project has assumptions
- [ ] Subject label resolves with guardrail entity number + title

### 2.4 Filter risks list by subject type — **TODO**

- List filter / datatable column for `subject_type` and/or subject label
- Quick filter chips optional

### 2.5 Traceability matrix — Risk column — **TODO**

- Optional RTM column or side panel in `TraceabilityMatrixService` showing linked risk count or highest score per spine row
- Export includes risk linkage where present
- **Out of scope for §1** — matrix has no Risk column today

**Acceptance criteria:**

- [ ] Matrix view shows risk signal without breaking existing columns
- [ ] Orphan / unlinked risks not falsely attributed to spine rows

---

## 3. SDLC / test handoff links — **TODO** · **P1**

From product brief **Phase 2 — Deeper SDLC links**. Closes the loop from scenarios to code verification (manual or import — still encourage, don’t police).

### Scope

- **Test artifact links:** Record/link unit-test artifact IDs or URLs against scenarios (and optionally steps)
- **Optional Git/PR references:** Supplement spine links — not a replacement for test-mediated provenance
- **Gap view:** Scenarios without test links (called out in brief §9.5 as “Later”)

### Conceptual entities (from brief §8.4)

- `TestArtifactLink` — scenario ↔ test reference
- `CodeChangeLink` — optional PR/commit reference

### Acceptance criteria

- [ ] Scenario detail shows linked test artifact(s); add/edit from scenario page
- [ ] Project dashboard or readiness includes “scenarios without test links” soft gap
- [ ] Export pack includes test link metadata where present
- [ ] No CI runner integration required in Phase 2

---

## 4. Acceptance run evidence — **TODO** · **P1**

From product brief Phase 2 and [`phase-1.x-todo.md`](phase-1.x-todo.md) “Later Phase 1.x” item #3.

### Scope

- Record pass/fail notes or status against acceptance tests / scenario checklist items
- Manual entry first; optional CI import hook documented but not required
- Surfaces on Acceptance Plan / project dashboard as coverage signal

### Conceptual entity

- `AcceptanceRun` — run date, outcome, notes, optional link to scenario(s)

### Acceptance criteria

- [ ] BA/PM can log an acceptance run outcome against a scenario or checklist row
- [ ] Readiness or gap view highlights scenarios with no recorded run (soft)
- [ ] Export includes run history summary

---

## 5. Excel import — **TODO** · **P2**

From product brief Phase 2.

### Scope

- Import spine entities (at minimum: Business Needs, Stakeholder Needs, Features/Scenarios) from structured Excel/CSV
- Preview + validation before commit; orphan rows flagged, not silently dropped
- Reuse patterns from `FeatureImportService` where applicable

### Acceptance criteria

- [ ] Upload → preview → apply flow with row-level errors
- [ ] Imported rows respect parent-link rules (or land as draft orphans per SOP)
- [ ] Help booklet documents expected column layout

---

## 6. Guardrails ↔ spine linking — **TODO** · **P2**

Carried forward from [`phase-1.x-todo.md`](phase-1.x-todo.md) §1.3 (not started).

### Scope

- Optional M:N links from Assumption / Constraint / BusinessRule → BusinessNeed / StakeholderNeed
- Soft readiness cues using **linked** guardrails (beyond project-level open assumptions)
- Surface linked guardrails on need / story detail pages

### Acceptance criteria

- [ ] Guardrail form supports linking to one or more needs/stories
- [ ] Need/story detail shows linked guardrails section
- [ ] Readiness distinguishes unlinked vs linked open assumptions where relevant

---

## 7. Solution requirements — NFR dialect — **TODO** · **P2**

From [`resources/help/solution_requirements.md`](resources/help/solution_requirements.md): *“Non-functional / quality-of-service capture may be expanded later as an additional dialect.”*

### Scope

- Third dialect under Solution Requirements hub (alongside Functional Requirements and BDD Features)
- Links upstream to Stakeholder Need; same nav/hub pattern as existing dialects
- Lightweight v1: title, description, category (performance, security, availability, etc.), optional acceptance criteria

### Acceptance criteria

- [ ] Hub section for NFR / QoS with CRUD parity to FR
- [ ] Traceability and export include NFR rows
- [ ] Help booklet updated

---

## 8. Phase 1.5 carry-forward — **TODO** · **P2–P3**

Items from product brief **Phase 1.5** not fully closed; safe to schedule in Phase 2 if not picked up earlier.

| Item | Priority | Notes |
|------|----------|-------|
| **RACI-lite / ownership on nodes** | P2 | Owner field or role assignment on BO/BN/SN/FR |
| **Soft vs hard “Ready” gates** | P2 | Org/project toggle: warn vs block when marking ready without scenarios / validated assumptions |
| **Project-level risk tolerance** | P3 | Mentor note: averse / neutral / seeking on Strategic Baseline or Governance doc — not per-risk field |
| **C4 / architecture expansion** | P3 | Thin C4 entity exists; expand Context + Container views if needed |

---

## 9. Risk v3 — advanced visualization — **TODO** · **P3**

**Only if needed** after §1–§2. Explicitly marked “v3” in Risk subject plan — **not** part of §1 minimal slice.

- Risk cascade / impact propagation visualization (distinct from CR cascade)
- Bulk report: “risks affecting this objective tree”

**Do not implement in §1:** CR-style cascade preview on risk forms — overstates scope for requirement-level uncertainty.

---

## 10. Strategic Baseline — multi-version — **TODO** · **P2**

**Status:** LOCKED for Phase 2 queue (documentation only — **do not implement in v1**).  
Carried forward from [`phase-1.x-todo.md`](phase-1.x-todo.md) “Later” item #2. Current codebase is **1:1** (`strategic_baselines.project_id` unique); this section defines the agreed multi-baseline design.

### Problem

Projects evolve strategy over time (re-baselines after major scope shifts, annual planning cycles). Today each project has at most one Strategic Baseline row. Users cannot retain historical strategy snapshots while marking which baseline drives readiness, export, and BABOK Package 1.

### Agreed decisions (LOCKED)

| Decision | Choice |
|----------|--------|
| Cardinality | **Multiple** `StrategicBaseline` records per project — **drop** unique `project_id` |
| Identity fields | **`title`**, **`baseline_date`**, **`status`** (reuse `draft` / `in_review` / `approved` from `StrategicBaselineStatus`) |
| Narrative fields | Keep **`current_state`**, **`future_state`**, **`change_strategy`** (unchanged semantics) |
| Current pointer | **`is_current`** boolean — at most one `true` per project |
| Resolver (single source of truth) | Used by Package 1, readiness gaps, nav folder progress: **(1)** row with `is_current = true` → **(2)** else latest **approved** by `baseline_date` → **(3)** else latest by `baseline_date` |
| Set as current | User action clears `is_current` on sibling baselines for the same project, then sets target row |
| Strategy hub UX | Multi-record **list** with create; **full-page form** for narrative fields (not modal-only for long text) |
| Migration | Backfill existing row: set **`title`** (e.g. from project name or “Initial baseline”), **`baseline_date`** (e.g. `created_at` or approval date), **`is_current = true`** |
| v1 export / BABOK | Continue resolving **current** baseline only — no query param required for first ship |

### Data model (target)

| Column | Type | Notes |
|--------|------|-------|
| `title` | string | Required; human label (e.g. “2026 Q1 re-baseline”) |
| `baseline_date` | date | Required; ordering key for resolver |
| `status` | string | Reuse existing enum — `draft`, `in_review`, `approved` |
| `is_current` | boolean | Default `false`; app enforces ≤1 per project |
| `current_state`, `future_state`, `change_strategy` | text, nullable | Unchanged |
| `project_id` | FK | **Non-unique** index (drop unique constraint) |

Optional index: `(project_id, is_current)` for resolver queries.

### Resolver service

Extract **`StrategicBaselineResolver`** (or equivalent) — one method, e.g. `resolveForProject(Project $project): ?StrategicBaseline`:

1. `where project_id and is_current = true` (if multiple, prefer highest `baseline_date` — should not happen if “Set as current” is enforced)
2. Else latest row with `status = approved` ordered by `baseline_date` desc, then `id` desc
3. Else latest row by `baseline_date` desc, then `id` desc

**Consumers (must use resolver, not `$project->strategicBaseline` 1:1 relation):**

- `ProjectExportService` / export blade
- `BabokDocumentService` / BABOK partials (`current-state-and-needs`, `change-strategy-scope`, etc.)
- `ProjectReadinessService` / dashboard artifact counts
- `NavFolderProgress` / Strategy folder badge

### UX

- **Strategy hub** (`strategy.index` or successor): table/list of baselines per project — title, date, status, current badge, actions (view, edit, set current, delete per soft-delete rules)
- **Create** → full-page form with title, date, status, narratives
- **Edit** → same full-page form
- **Set as current** → confirm optional; clears siblings’ `is_current`
- List may show draft/in-review rows; resolver still applies rules above when none marked current

### Acceptance criteria

- [ ] Migration drops `unique(project_id)`; adds `title`, `baseline_date`, `is_current`; backfills existing rows (`is_current = true`, sensible title/date)
- [ ] `StrategicBaselineResolver` implements the three-step rule; unit tests cover tie-breaks and empty project
- [ ] Export pack and BABOK Package 1 use resolver output (unchanged UX for users with one baseline)
- [ ] Readiness gaps and Strategy nav folder use resolver (not hard 1:1 `strategicBaseline` relation)
- [ ] Strategy hub shows multi-record list + create; narratives on full-page form
- [ ] “Set as current” enforces single current per project
- [ ] Help booklet / data dictionary updated to reflect 1:N model

### Implementation touch list (when scheduled)

| Layer | Files / areas |
|-------|----------------|
| Migration | Drop unique on `project_id`; add columns; backfill |
| Model | `StrategicBaseline.php`, `Project.php` (`hasMany` + deprecated/remove 1:1 helper) |
| Service | New `StrategicBaselineResolver`; refactor `ProjectExportService`, `BabokDocumentService`, `ProjectReadinessService`, `NavFolderProgress` |
| Controller / views | `StrategicBaselineController`, `strategy/index.blade.php`, new list + full-page form blades |
| Config | `config/crud.php`, `routes/web.php`, `lang/en/ui.php` |
| Docs | `docs/bassist-data-dictionary.md`, `resources/help/` strategy topic |

### Phase 2 polish / out of v1 scope (note only)

- **`?baseline_id=`** on project export and BABOK routes — historical pack for a specific baseline snapshot (optional later)
- **Requirement-level snapshot freeze** — capturing spine/guardrail state at baseline approval time — **OUT OF SCOPE** (Phase 3+ / separate initiative)

---

## Out of scope (Phase 3+)

Do not queue under Phase 2:

- Multi-workspace SaaS, billing, subscription packaging
- AI suggestions / auto-linking
- Full BABOK technique library
- Hard “cannot mark ready” locks as default (org toggle in §8 is the Phase 2 ceiling)
- Monetization / hybrid human baseline review service
- Scenario-step normalization debates (Phase 1 open decision)

See [`need-spine-product-brief.md`](need-spine-product-brief.md) §Phase 3.

---

## Success criteria (Phase 2 complete)

1. Risks can be raised in context from any spine requirement level and traced back from entity pages.
2. Test/acceptance handoff is visible: scenarios can link to test artifacts; acceptance runs can be recorded.
3. Managers see soft gaps for missing test links and unlinked critical risks — without blocking delivery.
4. Optional imports and guardrail/spine links reduce manual re-entry for mature projects.

---

## Document maintenance

When an item ships, move it to **DONE** with date and PR reference (mirror [`phase-1.x-todo.md`](phase-1.x-todo.md) style). Keep P0/P1 focused — defer new ideas to Phase 3 unless they appear in an updated product brief.
