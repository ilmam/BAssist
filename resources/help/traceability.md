---
title: Traceability Matrix
---

## Context & Definition

The Traceability Matrix is an automated, relational view that maps dependencies across the entire project hierarchy. In BABOK terms, traceability identifies and documents the lineage of each requirement, including its *backward* traceability (to the strategic goals it supports), its *forward* traceability (to the test cases and code that fulfill it), and its relationship to other requirements.

## Why It Matters

As projects evolve, scope creep and gold-plating (adding unapproved features) occur silently. If a stakeholder requests a new feature mid-project, the team needs an immediate way to evaluate its impact. The matrix provides auditability and governance: it proves whether a piece of software or a specific requirement actually serves a legitimate, approved business objective, and ensures that no strategic need has been left out of the final solution.

## How to Use

1. **Monitor link integrity (coverage):** Regularly scan the matrix for *orphan* entities (features without a parent business need) or *barren* entities (business needs that have no downstream scenarios). Gaps may be highlighted in the matrix or in reviews.
2. **Perform impact analysis:** Before modifying, prioritizing, or retiring any requirement, consult the matrix. Trace forward to see which downstream scenarios, test plans, or developer tasks will be affected, and trace backward to see which stakeholders must be consulted about the change.
3. **Export for audits & sign-offs:** Use matrix views to demonstrate full requirements coverage to sponsors and regulatory compliance officers—proving that what was built matches what was requested.
4. **Validate relationships:** Ensure links make logical sense based on BABOK relationship types: does the feature *derive from* the stakeholder requirement? Does the scenario or test case *satisfy* or *validate* the feature?

## The Bigger Picture & Downstream Links

- **Upstream (backward traceability):** Connects everything back to the foundational entities: business objectives and business needs.
- **Downstream (forward traceability):** Connects high-level requirements down to stakeholder needs, features, and scenarios. Code deployment artefacts sit further downstream as a related practice beyond what this matrix shows.
- **The rule of integrity:** Prefer a continuous chain from business objective down to a testable scenario. A break in that chain usually means missing scope or unnecessary features—incomplete coverage to resolve in review.

---

*BABOK® Guide Chapter 5 Requirements Life Cycle Management — Section 5.1 Trace Requirements & Section 5.4 Assess Requirements Changes.*
