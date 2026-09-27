---
title: Traceability Matrix
---

## Context & Definition

The Traceability Matrix is an automated, relational view that maps dependencies across your entire project hierarchy. Traceability identifies and documents the lineage of every requirement. It provides *backward* traceability (tracing a feature up to the strategic goal it supports) and *forward* traceability (tracing a requirement down to the specific test cases and code scenarios that fulfill it).

## Why It Matters

As projects evolve, scope creep and "gold-plating" (adding unapproved, unnecessary features) occur silently. If a stakeholder requests a new feature mid-project, the team needs an immediate, objective way to evaluate its impact. The matrix provides project governance and auditability: it proves whether a piece of software actually serves a legitimate, approved business objective, while ensuring that no strategic need has been accidentally left out of the final solution.

## How to Use

1. **Monitor Link Integrity:** Scan the matrix for *orphan* entities (e.g. features with no parent Stakeholder Need) or *barren* entities (e.g. needs with no downstream FR or BDD scenarios). Use gap chips to show one break type at a time; click a row to highlight its chain.
2. **Fix gaps in place:** Empty cells can offer **Add story / Add feature / Add scenario**. Click a code or title to open the record (stays on the matrix). “Show gaps” still filters to incomplete rows. A **Won't** or **Deprecated** stakeholder need stays on the matrix (labelled “Won't (this release)”) and is not treated as a missing-feature gap.
3. **Perform Instant Impact Analysis:** Before modifying, prioritizing, or retiring any requirement, consult the matrix. Trace forward to see which downstream scenarios or process steps will break. Trace backward to see which parent needs and objectives are impacted and which stakeholders must be consulted about the change.
4. **Validate Relationships:** Ensure the links make logical sense. Does this feature genuinely *derive from* that stakeholder requirement? Does this BDD scenario adequately *validate* the feature?
5. **Export for Audits & Sign-Offs:** Export CSV during milestone reviews. The export respects the same project / gaps / gap-type filters as the on-screen matrix.

## The Bigger Picture & Downstream Links

- **Upstream (Backward Traceability):** Connects the entire tactical execution layer back to the foundational entities: Business Needs (why), Business Objectives (what), and Stakeholder Requirements.
- **Downstream (Forward Traceability):** Connects high-level requirements down to solution packaging—Functional Requirements and/or BDD Features with Scenarios (Given/When/Then)—and onward to Business Process Diagram (swimlane) steps. Primary lineage for FR/Feature remains Stakeholder Need; process steps optionally link upstream to a Stakeholder Need, while FR/Feature may optionally reference a process step for BPD coverage. Code deployment and QA testing artifacts sit further downstream as a related practice beyond what the matrix itself shows.
- **The Rule of Integrity:** Prefer a continuous, unbroken chain from a Business Need through Business Objectives all the way down to a testable Scenario. A break in that chain usually means one of two things: missing scope (you forgot to build something) or unnecessary waste (you built something nobody asked for). A Stakeholder Need may be packaged by an FR, a BDD Feature, or a scenario that lives under another Feature but is mapped to this need. Needs marked Won't (this release) or Deprecated remain on the spine; they are not current-release packaging gaps. BPD process steps are optional coverage: they appear on an FR/Feature row only when linked, and unlinked boxes are not matrix gaps.

**Practical tip:** The Traceability Matrix is your best defense against scope creep. When a stakeholder asks to squeeze in a "quick" new feature mid-sprint, never just say "no." Open the matrix, trace the new feature's impact forward to the test plans it breaks, and backward to the business rule it violates. Show them the matrix and ask: "Are you willing to fund the impact on all these connected pieces?" Let the matrix do the heavy lifting for you.

---

*BABOK® Guide Chapter 5 Requirements Life Cycle Management — Section 5.1 Trace Requirements & Section 5.4 Assess Requirements Changes.*
