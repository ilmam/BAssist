**Reverse provenance:** Code maps to a scenario, which belongs to a story governed by specific business rules, fulfilling a business need (why), which is measured by a strategic business objective (what).

### 3.2 Non-negotiables (SOP)

1. No user story without a **business need**.
2. No business objective without a parent **business need**.
3. No “ready for build” without **scenarios** under that story.
4. Unvalidated assumptions act as soft blocks to readiness.
5. **Change starts on the spine**, then flows to tests and code.

### 3.3 Culture vs system

| System does | Culture / SOP does |
|-------------|-------------------|
| Makes the path visible and easy | Requires teams to start from needs |
| Centralizes rules and constraints | Insists on validating assumptions before building |
| Exports for Reqnroll, checklists, matrices | Devs import/run tests in the codebase |
| Shows gaps to managers | Treats gaps as coaching / governance topics |

---

## 4. Product principles

| Principle | Meaning |
|-----------|---------|
| **Coverage** | We can see whether stated needs/stories have scenarios. |
| **Provenance** | We can explain why work exists via links back to strategic objectives. |
| **Living baseline** | After releases, updates start on the spine; rules and specs are not abandoned. |
| **Progressive Disclosure** | We keep the MVP clean, adding advanced BABOK artifacts (like RACI) over time. |
| **Encourage, don’t police** | Soft gates and gap views first; optional hard gates later. |

---

## 5. Personas

| Persona | Goals with Need Spine |
|---------|------------------------|
| **Business Analyst** | Capture objectives and needs; document rules/constraints; write scenarios; keep cascade clean. |
| **Project Manager** | See gaps, validate assumptions, view baselines; run SOP. |
| **Developer** | Receive clear scenarios and constraints; export to Reqnroll; know what “done” means. |
| **Sponsor / lead** | View strategic alignment and coverage without living in developer tickets. |

---

## 6. Phase roadmap

### Phase 1 — Core Spine + Handoff (MVP)

**Goal:** Establish the foundational trace from Business Objective down to Scenarios.

**In scope:**
- Project workspace
- Business Objectives
- Business Needs
- User Stories (User Requirements)
- Features + Scenarios (BDD structure)
- Traceability matrix (derived view + export)
- Soft gap indicators (“missing scenarios”, “orphan links”)
- Export scenarios (Gherkin format)

### Phase 1.5 — The BABOK Guardrails

**Goal:** Introduce the context that surrounds and governs the requirements.

**In scope:**
- **Business Rules Catalog:** Global policies that apply across multiple stories.
- **Assumptions & Constraints Tracking:** Linked directly to needs/stories to highlight unverified beliefs and hard project boundaries.
- Readiness dashboards (e.g., highlighting stories blocked by unvalidated assumptions).

### Phase 2 — Deeper Lifecycle Management

**Goal:** Close the loop with testing and advanced management.

**In scope:**
- Record/link unit-test artifacts against scenarios.
- Acceptance run evidence.
- Prioritization frameworks (MoSCoW).

---

## 7. Domain model (Conceptual schema)

| Entity | Purpose & Linkage |
|--------|---------|
| **BusinessObjective** | The measurable strategic outcome. |
| **BusinessNeed** | What the business must achieve/change; **must** link to an Objective. |
| **Assumption / Constraint** | Unverified beliefs or hard boundaries; linked to Needs or Stories. |
| **BusinessRule** | A formal policy directive; linked to Stories or Scenarios. |
| **UserStory** | User/system expectation; **must** link to ≥1 BusinessNeed. |
| **Feature** | BDD feature grouping; links to UserStories. |
| **Scenario** | Given/When/Then; belongs to Feature; traces to requirement. |

---

## 8. UX directions

- **Project home:** Displays spine progress, top gaps, and unvalidated assumptions.
- **Story page:** Shows parent need, associated business rules, scenarios list, and export actions.
- **Matrix page:** First-class navigation item for PMs and BAs to trace Objectives down to Scenarios.
- Prefer guided flows over endless admin lists for creation paths.

---

## 9. Naming cheat-sheet (for UI)

| Concept / BABOK term | Preferred UI term | Definition for Users |
|---------|-------------------|-------|
| Strategy Goal | Business Objective | The ultimate strategic target we must hit. |
| Problem/Opportunity | Business Need | The gap between where we are and the objective. |
| Policy / Guideline | Business Rule | A formal condition the business *must* follow (e.g., max discount is 20%). |
| Unverified Belief | Assumption | Something we believe is true but haven't proven yet. |
| Restriction | Constraint | A hard limitation we cannot change (e.g., budget, law, server type). |
| Agile slice | User Story | The specific requirement or user expectation. |
| BDD Example | Scenario | The Given/When/Then behavior specification. |

---

## 10. Manifesto

We work on many projects with many teams. Without a shared practice, work tangles, assumptions go unchecked, and rules are forgotten.

**Need Spine** is how we deliver: every user story fulfills a business need, which targets a strategic objective. We bind our work with clear assumptions, constraints, and business rules. We specify exact behavior in scenarios before we treat a build as ready. 

Tools support the practice. Culture establishes it. 

**Three questions we must always be able to answer:** Did we build what we said? Why does this code exist? What do we update when the business moves?