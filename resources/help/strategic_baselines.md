---
title: Strategic Baseline
---

## Context & Definition

The Strategic Baseline is the project’s approved Strategy Analysis narrative—the anchor of *why* the enterprise is changing, *where it stands today*, *where it intends to go*, and *how* it plans to bridge the gap. It is **not** a project management baseline (scope/schedule/cost). It anchors business logic so later requirements and delivery stay honest to the need.

In BAssist, each project has **one** Strategic Baseline document with three narrative fields—**Current state**, **Future state**, and **Change strategy**—plus a lifecycle **Status** (`Draft` → `In review` → `Approved`). Related strategy artifacts live beside it on the Strategy hub and Need Spine: Business Needs, Business Objectives, Scope Items (in/out), Assumptions, Constraints, and Risks.

This maps to BABOK Strategy Analysis (Chapter 6): Analyze Current State (6.1), Define Future State (6.2), Assess Risks (6.3), and Define Change Strategy (6.4).

## Why It Matters

Without a shared, approved strategy narrative, teams invent scope from slide decks and hallway decisions. Scope creep then looks like “just one more feature” instead of a challenge to the business need. The Strategic Baseline draws the line: once **Approved**, significant Requirements Analysis and Design Definition (RADD) and solution investment should treat that narrative—and the linked Need Spine—as the reference. The BA is the guardian of the business need, not the curator of informal decks.

## How to Use

1. **Write the three narratives (Tasks 6.1, 6.2, 6.4):**
   - **Current state:** What is broken or limited today—operations, process friction, capability gaps. Stay problem-focused; avoid prescribing screens or vendors.
   - **Future state:** The intended operating model once the change succeeds. Keep it outcome-oriented; measurable targets belong primarily on **Business Objectives** (`Success measure`, `Potential value`).
   - **Change strategy:** How you bridge the gap—phasing, pilots, cutover, training, temporary workarounds. Enterprise readiness and gap thinking belong here in prose; use **Readiness & Gaps** on the dashboard as operational signals, not a substitute for judgment.

2. **Complete the strategy package around the document—not only inside it:**
   - **Strategic alignment:** Link **Business Needs** ↔ **Business Objectives** so every objective traces to an enterprise why. The baseline narratives should agree with those records; there is no separate “alignment statement” field.
   - **Business case / value:** BAssist does **not** store a dedicated ROI or formal business-case entity. Capture potential value and success measures on **Business Objectives**, and keep the value story consistent with Future state and Change strategy.
   - **Scoped boundaries:** Record explicit **In** / **Out** **Scope Items** on the Strategy hub. Do not bury in/out lists only inside the Change strategy textarea.
   - **Assumptions & constraints registry:** Maintain **Assumptions** and **Constraints** as their own records (Guardrails). Call out the critical ones in Change strategy if they shape the approach.
   - **Risks:** Capture material risks on the Risk register (Task 6.3); the baseline does not replace that list.

3. **Establish timing and status:** Draft during Strategy Analysis. Move to **In review** for sponsor/stakeholder consensus, then **Approved** before heavy RADD and solution delivery investment. Readiness treats a missing or still-**Draft** baseline as a gap signal. One baseline per project—open it from Strategy or the project’s baseline shortcut; do not create parallel “versions” as separate records.

4. **Manage change after approval:** Do not silently rewrite an approved narrative when stakeholders shift direction. Raise a **Change Request** (anchored to the affected **Stakeholder Need**), use impact notes and the cascade preview, and check **Traceability** so downstream FR/BDD stay honest. Update Current/Future/Change strategy only when the approved change strategy says the anchor itself must move—and re-secure **Approved** status when the narrative changes materially.

## The Bigger Picture & Downstream Links

- **Upstream:** Executive intent and market reality, expressed as Business Needs (why) and Business Objectives (what). The baseline synthesizes those into a single approved strategy story.
- **Beside (same Strategy Analysis package):** Scope Items, Assumptions, Constraints, Risks—and Readiness & Gaps for absorption capacity.
- **Downstream:** Stakeholder Needs, Functional Requirements, BDD Features, and delivery work must remain traceable to the approved need and within Scope Items. Change Requests protect integrity after approval.
- **The Rule of Integrity:** An **Approved** baseline with empty narratives, orphan needs, or no in/out scope is theater. Approval without consensus is not a baseline.

**Practical tip:** If someone asks you to “just tweak the future state” after approval, ask which Business Need or Scope Item changed—and open a Change Request. Informal slide updates are not the Strategic Baseline.

---

*BABOK® Guide Chapter 6 Strategy Analysis (Tasks 6.1–6.4); Change Control / Assess Requirements Changes (Task 5.4) and Traceability (Task 5.1) for post-baseline change.*
