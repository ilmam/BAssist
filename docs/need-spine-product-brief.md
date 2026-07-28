# Need Spine — Product Brief

**Working name:** Need Spine  
**Tagline:** From business need to tested code — and back.  
**Document purpose:** Source brief for building the product in a dedicated workspace.  
**Audience:** Product owner, architects, developers scaffolding the system.  
**Status:** Vision / Phase-1 oriented (no AI in first phase).

---

## 1. What this product is

Need Spine is a **practice-model workspace** that helps medium-sized organizations run software projects so that:

1. Work starts from **business needs**, not orphan tickets.
2. Delivery is **BDD/TDD-first**: scenarios sit under user requirements/stories before build is treated as ready.
3. **Traceability is seamless** forward and backward — not only across requirements docs, but across the delivery chain.
4. Managers can establish and coach an **SOP** using visibility and handoff artifacts; the tool **encourages** the model; culture enforces it.

It is **not** a full enterprise ALM replacement (Jama/Polarion), not a Jira clone, and not an AI requirements writer in Phase 1.

**Foundation (implementation note):** The first implementation may use a Generic Laravel CRUD boilerplate (auth, roles, DTO forms, Metronic UI) as technical foundation. Domain logic is product-specific and layered on top.

---

## 2. Problem statement

In medium orgs with **many teams and many projects**, work tangles:

| Pain | Question the org cannot answer |
|------|--------------------------------|
| Incomplete delivery | Did we develop everything we said we would? |
| Lost provenance | A year from now, why does this line of code exist? |
| Spec rot after releases | After v1.5, who updates functional requirements when business logic evolves? |

Typical stacks (Jira + Confluence + Excel + hope) optimize for **creating** work items. They do not keep a **living spine** from need → story → scenario → test → code.

---

## 3. Practice model (the methodology the product manifests)

### 3.1 The spine

```text
Business need
  └── User requirement / user story
        └── Scenario(s)          ← BDD; entry to development
              └── BDD steps
                    └── Unit tests (e.g. via Reqnroll after export)
                          └── Code
              └── Acceptance evidence / checklist (derived / manual)
```

**Reverse provenance (test-mediated):**

```text
Code
  ← exercised by Unit test
    ← generated from / maps to BDD step
      ← belongs to Scenario
        ← sits under User requirement / story
          ← traces to Business need
```

The product does **not** invent intent from source files. Intent is written first; tests are the bridge to code.

### 3.2 Non-negotiables (SOP)

1. No user story / user requirement without a **business need**.
2. No “ready for build” without **scenarios** under that story/requirement.
3. **Change starts on the spine** (need / story / scenario), then flows to tests and code.
4. Orphans are visible; managers use gaps to coach — hard technical locks are optional per org later.

### 3.3 Agile fit

Need Spine is **compatible with Agile**:

- Slice size and cadence remain Agile (stories, sprints).
- Each story still hangs under a business need.
- Scenarios are the precise acceptance behavior (living DoD).
- Avoid big-bang frozen BRDs; keep the cascade **incremental and living**.
- Backlog refinement = add/split stories and **update scenarios** when behavior changes.

```text
Business need (theme / outcome)
  └── User story (sprint-sized)
        └── Scenario(s)
              └── tests → code
```

### 3.4 Culture vs system

| System does | Culture / SOP does |
|-------------|-------------------|
| Makes the path visible and easy | Requires teams to start from scenarios |
| Holds linked artifacts | Insists on following the spine |
| Exports for Reqnroll, checklists, matrices | Devs import/run tests in the codebase |
| Shows gaps to managers | Treats gaps as coaching / governance topics |
| Cannot force CI green or perfect coverage | Owns Definition of Done in the org |

Many stops remain **manual** (write scenarios, export, generate tests with Reqnroll). That is intentional.

---

## 4. Product principles

Name these in UI copy and docs so the product stays coherent:

| Principle | Meaning |
|-----------|---------|
| **Coverage** | We can see whether stated needs/stories have scenarios (and later test handoffs). |
| **Provenance** | We can explain why work exists via test-mediated links back to needs. |
| **Living baseline** | After releases, updates start on the spine; specs are not abandoned. |
| **Encourage, don’t pretend to police** | Soft gates and gap views first; optional hard gates later. |
| **Handoff over ownership of CI** | Export and checklists beat rebuilding Git/test runners in Phase 1. |
| **Measured depth** | Add BABOK/SDLC artifacts only when they have a parent link, a place on the spine, and a manager-visible gap. |

---

## 5. Personas

| Persona | Goals with Need Spine |
|---------|------------------------|
| **Business Analyst** | Capture needs and requirements; write scenarios; keep cascade clean. |
| **Project Manager** | See gaps, baselines, ownership; run SOP; steer multi-project coherence. |
| **Developer** | Receive clear scenarios; export to Reqnroll; know what “done” means; reverse-trace when needed. |
| **Sponsor / lead (viewer)** | Readiness and coverage without living in tickets. |

Primary beachhead: **medium-sized orgs, multi-team, multi-project**.

---

## 6. Competitive position (honest)

| Landscape | Role |
|-----------|------|
| Excel / Confluence / Jira | Flexible; gaps everywhere — Need Spine’s contrast. |
| Need Spine | Opinionated spine + visibility + exports; SOP manifestation. |
| Jama Connect / Polarion / DOORS | Enterprise ALM, compliance-heavy — different buyer and weight. |

**Do not claim** invention of traceability or BDD. **Do claim** a usable, preachable practice + workspace for seamless forward/backward chain and living updates, aimed where full ALM is too heavy and wikis are too loose.

---

## 7. Phase roadmap

### Phase 1 — Spine + handoff (MVP)

**Goal:** One project can run Need → Req/Story → Scenarios with live traceability; exports support engineering handoff; managers see gaps.

**In scope:**

- Project workspace
- Business objectives (optional but recommended parent of needs) and/or **Business needs**
- User needs / user requirements / user stories (naming: pick one primary term in UI; see §8)
- Requirements cascade with mandatory parent links
- Features + scenarios (BDD structure)
- Traceability matrix (derived view + export)
- Soft gap indicators (“missing scenarios”, “orphan links”)
- Export scenarios (Gherkin / Reqnroll-oriented text)
- Acceptance test **checklist** generated from scenario titles (manual use; not forced)
- Basic roles (BA, PM, Dev, Viewer) within a project/workspace
- No AI

**Out of scope for Phase 1:**

- Forcing CI, coverage %, or IDE integration
- Full BABOK library
- Full C4 modeling suite (optional thin entities later in Phase 1.5)
- Billing / multi-tenant SaaS packaging (can design `workspace_id` early)
- Auto-updating code or inventing links from git history

**Phase 1 success criteria:**

1. A real project captures need → story → scenarios with required links.
2. Traceability matrix is viewable and exportable.
3. Scenarios export for use with Reqnroll (or equivalent).
4. Acceptance checklist can be generated from scenario titles.
5. Manager can see coverage gaps without opening every record.
6. Team can explain the SOP using the three questions (§2).

### Phase 1.5 — Manager SOP + light structure

- Baselines / versions (“Requirements baseline v1.2”)
- Ownership / RACI-lite on nodes
- Change impact (“if this need changes → affected stories/scenarios”)
- Readiness dashboard
- Optional: project **entities** + simplified C4 (Context + Container)
- Optional soft vs hard “Ready” gates (org toggle)

### Phase 2 — Deeper SDLC links (still encourage)

- Record/link unit-test artifacts or IDs against scenarios/steps (manual or import)
- Optional Git/PR references as *supplement*, not replacement for test-mediated spine
- Acceptance run evidence (pass/fail notes or import from CI later)
- Prioritization (MoSCoW etc.)
- Import from Excel

### Phase 3 — Selective BABOK + SaaS

- Add BABOK-aligned artifacts only with spine placement + gap metric (stakeholders, scope, risks, etc.)
- Multi-workspace SaaS, templates of Need Spine, subscription
- Optional hybrid “human baseline review” service
- Optional AI *after* the model is trusted (suggest links/wording — never required for MVP)

---

## 8. Domain model (conceptual)

Use this as the starting schema discussion in the new workspace. Names can be adjusted; relationships should not.

### 8.1 Core entities (Phase 1)

| Entity | Purpose |
|--------|---------|
| **Workspace / Organization** | Tenant boundary (even if single-tenant at first). |
| **Project** | Container for one delivery effort. |
| **BusinessObjective** *(optional)* | Measurable outcome; parent of needs. |
| **BusinessNeed** | What the business must achieve/change; hangs under objective if used. |
| **UserRequirement** (aka User Story in Agile UI) | User/system expectation; **must** link to ≥1 BusinessNeed. |
| **Requirement** *(optional split)* | If you separate “user need” from “system requirement”; else merge into UserRequirement for MVP. |
| **Feature** | BDD feature grouping; links to UserRequirement(s). |
| **Scenario** | Given/When/Then (And/But); belongs to Feature; traces to requirement. |
| **ScenarioStep** | Individual steps (optional normalize vs store as structured text). |
| **ArtifactExport / GeneratedChecklist** | Record of exports (optional audit). |

**Recommended MVP simplification:**  
`BusinessNeed` → `UserStory` → `Feature` → `Scenario` (+ steps as fields or child rows).  
Add `BusinessObjective` if you want an extra tier without cost. Defer separate “system requirement” table until needed.

### 8.2 Link rules

- Creating a UserStory requires `business_need_id` (or M:N with at least one need).
- Creating a Scenario requires a Feature that links to at least one UserStory (or direct story link).
- Prefer **M:N** in the model even if UI starts with “primary parent” only.
- Traceability matrix = query over FKs / pivot tables, not a separately typed spreadsheet as source of truth.

### 8.3 Status (suggested)

Per node (need, story, scenario): `draft` | `ready` | `baselined` | `deprecated`  
“Ready for build” on a story implies scenarios present (soft warn or hard block — configurable later).

### 8.4 Later entities (not Phase 1 blockers)

- Entity (domain object), Relationship, C4 Node/View  
- Baseline, Approval, Comment  
- TestArtifactLink, CodeChangeLink, AcceptanceRun  
- Stakeholder, Risk, ScopeStatement (BABOK-selective)

---

## 9. Key product capabilities (detail)

### 9.1 Cascade capture

- Forms/wizards to create needs, stories, features, scenarios.
- Parent link required (or draft orphan that cannot be marked ready).
- Context text: why this step matters (short guidance per station) — optional copy in UI.

### 9.2 Traceability matrix

- Filter by project, need, story, feature.
- Columns: Need → Story → Feature → Scenario (IDs + titles).
- Export CSV/Excel/PDF.
- Highlight orphans and broken chains.

### 9.3 BDD authoring

- Feature name/description seeded from story when created from that story.
- Scenario editor: title + Given/When/Then/And.
- Quality checklist (non-AI): has When? has Then? names actor? — warn only in Phase 1.

### 9.4 Exports (handoff)

- **Gherkin export** for Reqnroll / Cucumber-style tools (download `.feature` or zip).
- **Acceptance checklist** from scenario titles (checklist UI + printable/export).
- Users may ignore exports — product still provides them.

### 9.5 Gap / coverage views (manager SOP)

- % needs with ≥1 story  
- % stories with ≥1 scenario  
- Stories marked ready without scenarios  
- Features/scenarios not exported (if export is tracked)  
- Later: scenarios without test links  

### 9.6 Explicit non-capabilities (Phase 1)

- Does not run unit or acceptance tests.
- Does not auto-generate production code.
- Does not guarantee test↔line coverage quality.
- Does not replace GitHub/GitLab/Jira (may link later).

---

## 10. UX directions

- **Project home:** spine progress + top gaps (not a generic CRUD dashboard only).
- **Story page:** parent need, scenarios list, export actions, ready checklist.
- **Matrix page:** first-class nav item for PMs.
- Prefer guided flows over endless admin lists for create paths.
- Implementation may start on Laravel + Blade/Metronic; complex wizards may later use Livewire/Inertia if needed.

---

## 11. Permissions (initial)

| Role | Capabilities |
|------|----------------|
| Workspace admin | Projects, members, settings |
| BA | CRUD needs, stories, features, scenarios |
| PM | Same + baselines/gap dashboards emphasis; membership |
| Developer | Read spine; create/edit scenarios if allowed; exports |
| Viewer | Read-only matrix and artifacts |

Refine with project-level membership in Phase 1.5+.

---

## 12. Monetization (later — do not build yet)

- **SaaS:** per workspace subscription for the spine engine + exports + dashboards.
- **Hybrid:** platform + optional human baseline/audit at gates.
- **Internal first:** dogfood as org SOP tool; SaaS after the practice is stable.

Positioning when selling: *seamless living spine for medium multi-team orgs* — not “we enforce quality by software alone.”

---

## 13. Naming cheat-sheet (for UI)

| Concept | Preferred UI term | Notes |
|---------|-------------------|-------|
| Methodology | Need Spine | |
| Business need | Business need | |
| Agile slice | User story | Maps to user requirement in BA language |
| BDD group | Feature | |
| Example | Scenario | |
| Reverse why | Provenance / “Why this exists” | Test-mediated |
| Manager view | Coverage / Gaps | |

---

## 14. Manifesto (short — for in-app / docs)

We work on many projects with many teams. Without a shared practice, work tangles.

**Need Spine** is how we deliver: every user story comes from a business need; we specify behavior in scenarios before we treat build as ready; tests mediate to code so we can explain why code exists; when business logic evolves, we update the spine first.

Tools support the practice. Culture and SOP establish it.

**Three questions:** Did we build what we said? Why does this code exist? What do we update when the business moves?

---

## 15. Open decisions (resolve in new workspace)

1. MVP naming: `UserStory` only vs separate User Need + System Requirement.  
2. Objectives tier: include in Phase 1 or start at Business Need?  
3. Soft-only gaps vs optional hard block on “Ready”.  
4. Scenario steps: structured rows vs single Gherkin text field.  
5. Single-tenant org deploy first vs workspace multi-tenancy from day one (`workspace_id` recommended either way).  
6. C4/entities in Phase 1.5 — confirm priority vs baselines/change impact.

---

## 16. Suggested first build slice (for the other workspace)

1. Auth + workspace + project.  
2. CRUD: BusinessNeed, UserStory (FK/M:N), Feature, Scenario.  
3. Story detail with scenario list + “Ready” soft checklist.  
4. Traceability matrix + CSV export.  
5. Gherkin export + acceptance checklist from scenario titles.  
6. Project home gap widgets.

Then dogfood on one internal project; tweak SOP copy; only then expand.

---

## 17. Document history

| Version | Date | Notes |
|---------|------|-------|
| 0.1 | 2026-07-20 | Initial product brief from vision conversations (Need Spine, Phase 1 without AI, test-mediated provenance, Agile-compatible, encourage-not-force). |

---

*End of brief. Use this document as the seed for PRDs, schema design, and backlog in the dedicated product workspace.*
