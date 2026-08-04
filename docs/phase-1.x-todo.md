# Phase 1.x — Todo (locked)

**Status:** In progress / partially shipped  
**Theme:** BABOK guardrails around the spine (encourage, don’t police)  
**Principle:** Satellite catalogs use **one nav link → one landing page → sections**, same pattern as Diagrams.

---

## 1. Rules & Assumptions hub — LOCKED / DONE

### Decision

| Decision | Choice |
|----------|--------|
| Entities | **Three separate entities** (boilerplate cost is fine) |
| Owner | Related to **Project** directly |
| Navigation | **One** project artifact link (not three sidebar items) |
| Landing UX | One hub page with **three sections** (mirror Diagrams) |
| Spine role | Side-context / guardrails — **not** a new rung between Need and Story |
| Links to spine nodes | Optional follow-on (see §1.3) |

### Entities

1. **Assumption** — belief treated as true but not yet proven  
2. **Constraint** — hard limit the solution must respect  
3. **BusinessRule** — policy the business must follow (often shared across stories)

### Hub UX

- Nav label: **Rules & Assumptions**
- Route: `guardrails.index`
- Sections: Assumptions · Constraints · Business Rules

### Implementation checklist

- [x] Scaffold **Assumption**, **Constraint**, **BusinessRule** (models, migrations, DTOs, repos, CRUD config)
- [x] Each belongs to `Project`
- [x] `nav => false` on the three entities (reachable via hub)
- [x] Hub controller + `index` blade with three sections
- [x] Register one `project_artifacts` nav entry in `config/navigation.php`
- [x] Lang keys (`ui.php`) for hub title, help, section labels
- [x] Basic create/edit/view via existing CRUD/modals
- [ ] Smoke-test in UI: create one of each under a project; hub lists them in sections

### 1.3 Follow-on (same 1.x)

- [ ] Optional M:N links from guardrails → BusinessNeed / StakeholderNeed
- [ ] Soft readiness cues using linked guardrails (beyond project-level open assumptions)
- [ ] Surface linked guardrails on need / story detail pages

---

## 2. Project readiness / gap dashboard — DONE (v1)

Derived view on the project dashboard (not a new entity). Reuses spine gap ideas from Traceability and adds guardrail signals.

- [x] `ProjectReadinessService` gap summary
- [x] Readiness card on project dashboard
- [x] Links into Traceability / Features / Guardrails hub
- [x] Severity badges use existing Metronic badge classes

---

## 3. MoSCoW via Priority master — DONE

- [x] Remap `high` / `medium` / `low` → `must` / `should` / `could` (preserve FKs)
- [x] Add `wont` (“Won't”)
- [x] Update `EntityPriority`, `StatusPrioritySeeder`, Need Spine demo seeder
- [x] Default priority = **Should**
- [x] Guardrails included in project export pack

No new MoSCoW model — same `priority_id` field.

---

## Later Phase 1.x (not started)

1. Change impact (“if this need changes → affected children”) — *partially addressed via Change Request cascade*  
2. Baselines / versions — *LOCKED in [`phase-2-todo.md`](phase-2-todo.md) §10 (multi-version Strategic Baseline)*  
3. Acceptance run evidence on existing Acceptance Test surface — *queued in [`phase-2-todo.md`](phase-2-todo.md) §4*

**Phase 2 backlog:** Risk subject linking (P0), multi-version Strategic Baseline (§10), SDLC/test handoff, and other deferred lifecycle items are tracked in [`phase-2-todo.md`](phase-2-todo.md).

---

## Out of scope for this slice

- AI suggestions  
- Hard “cannot mark ready” locks  
- Full BABOK technique library  
- Merging the three types into one polymorphic/`type` entity  

---

## Success criteria

1. A project can capture assumptions, constraints, and business rules.  
2. Users reach all three from **one** nav link and one landing page.  
3. Project dashboard shows readiness gaps from existing spine + guardrail data.  
4. Priority dropdown uses MoSCoW labels (Must / Should / Could / Won't).
