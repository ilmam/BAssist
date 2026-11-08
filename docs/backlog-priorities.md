# Backlog priorities

**Updated:** 2026-11-08
**Purpose:** One ranked view across the todo docs. The detail stays in each source doc; this file only orders it.
**Sources:** [`phase-2-todo.md`](phase-2-todo.md), [`phase-1.x-todo.md`](phase-1.x-todo.md), [`optimization-todo.md`](optimization-todo.md), [`brief-entity.md`](brief-entity.md)
**Principle:** Encourage, don't police.

Not checked when this was written: open comment threads in BAssist (the connector failed to authenticate). Review those separately.

---

## Now: housekeeping from the Brief slice

The Brief entity shipped in commit `28bf5a1`. Close it out before moving on.

- [ ] Grant roles Brief permissions (until then only Super Admin can use Briefs)
- [ ] Check the Brief and project pages in a browser (tested by render and form post only)

## 1. P0 — Risk subject linking (Phase 2 §1)

The only item the docs mark as the next shippable slice. Not started; the design is written. See [`phase-2-todo.md`](phase-2-todo.md) §1.

## 2. P1 — Phase 2

- [ ] Risk v2 (§2): reverse-lookup panel on requirement pages, readiness gap for unlinked critical risks, filters. Follows item 1.
- [ ] SDLC / test handoff links (§3): link scenarios to test artifacts, soft gap for scenarios without links
- [ ] Acceptance run evidence (§4): record pass/fail runs against scenarios, soft gap for scenarios never run

## 3. P2 — pick up when needed

- [ ] Guardrails ↔ spine linking (§6; carried from Phase 1.x §1.3, never closed)
- [ ] Multi-version Strategic Baseline (§10; design locked, do not implement in v1)
- [ ] Excel import (§5)
- [ ] Ownership on nodes, and warn-vs-block "Ready" toggle (§8)

## 4. P3 and parked

- Risk v3 visualization (§9), project-level risk tolerance, C4 expansion (§8)
- Load editor and diagram code on demand ([`optimization-todo.md`](optimization-todo.md))
- Traceability: risks mapped to Business Objectives (§2.6, marked do-not-implement-yet; depends on item 1)

## Loose ends

- [ ] Phase 1.x: smoke-test the Rules & Assumptions hub in the UI (one of each under a project; hub lists them in sections)
- [ ] Reconnect the BAssist connector (401) so open comment threads can be reviewed
- [ ] Decide what to do with the untracked `docs/need-spine-process.html` and `.pptx`
- [ ] When an item ships, move it to DONE in its source doc with date and commit (see Document maintenance in [`phase-2-todo.md`](phase-2-todo.md)), then tick it here.

---

## Parked: design thinking

Paused on purpose. Resume when the above is under control. Nothing here is lost; it is only ordered last.

- Brief follow-ups: "New version" button, coverage view (parts of the ask no project owns), has-brief control in the project list UI, ERD and data dictionary entries. See [`brief-entity.md`](brief-entity.md).
- Empathize / Define fields on `StakeholderNeed`, `BusinessNeed`, `BusinessObjective`
- Prototype validation on `Screen` and the soft readiness gate
- Ideate: options considered and why one was chosen
- Skills: brief capture in `need-spine`, reframing and validation checks in `spine-ready`
- Closing the loop: a brief's success measures checked against outcomes after delivery (needs the Phase 2 test and acceptance items first)
