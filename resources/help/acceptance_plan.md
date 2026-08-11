---
title: Acceptance Testing & Criteria
---

## Context & Definition

Acceptance Testing validates whether the built solution successfully meets the original business needs and is acceptable to key stakeholders. Acceptance Criteria define the specific, objective conditions and measures of value that must be met for a requirement to be considered fulfilled. In practice, these criteria form the definitive "Definition of Done."

## Why It Matters

Without explicit acceptance tests, the definition of "done" is entirely subjective. A developer might declare a feature complete because the code compiles and the database updates, while a stakeholder will reject it because it fails to support their actual operational workflow. Explicit acceptance criteria eliminate ambiguity, preventing costly rework and endless arguments by establishing a mutually agreed-upon, objective contract of success before development even begins.

## How to Use

1. **Define Objective Pass/Fail Conditions:** Attach clear, measurable criteria to every Solution Requirement or User Story. If a condition cannot be objectively tested (e.g., "The system should be fast"), it must be rewritten (e.g., "The system must load the dashboard in under 2 seconds").

2. **Bind to BDD Scenarios:** Translate your acceptance criteria into executable BDD Scenarios (Given/When/Then). This turns human-readable rules into automated testing blueprints for developers.

3. **Facilitate UAT Sign-Off:** During User Acceptance Testing (UAT), log the actual test results against the expected criteria. This is where you capture the formal stakeholder approval required for deployment.

4. **Guard the Release Gate:** Treat readiness for release as a delivery practice: do not move a feature to release without a linked, passed acceptance test and stakeholder approval.

## The Bigger Picture & Downstream Links

- **Upstream (Backward Traceability):** Validates that the executed software fulfills the parent Stakeholder Requirements, Solution Requirements, and Business Rules.
- **Downstream (Forward Traceability):** Serves as the ultimate gateway for QA Test Plans, release deployment checklists, and post-implementation Solution Evaluation (verifying if the released software actually moved the needle on the Business Objectives).
- **The Rule of Integrity:** A requirement is only as good as its testability. An un-testable requirement is an invalid requirement.

**Practical tip:** Never accept "it works on my machine" as proof of completion. Acceptance testing isn't about proving the software works technically; it's about proving it solves the user's problem. Always write your Acceptance Criteria from the perspective of the stakeholder using the system. If you can't explain exactly how a user will physically test and approve a feature, you haven't finished analyzing the requirement.

---

*BABOK® Guide Technique 10.1 Acceptance and Evaluation Criteria & Chapter 8 Solution Evaluation.*
