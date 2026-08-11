---
title: Assumptions
---

## Context & Definition

Assumptions are foundational beliefs about the current environment or the future state that are treated as facts for planning purposes, even though they lack 100% empirical proof at the time.

Assumptions are a critical input to strategy analysis. Because we cannot know everything before a project starts, we must make assumptions to move forward—for example, "It is assumed that the dealer network has a stable, high-speed internet connection to submit digital inquiries."

## Why It Matters

Unvalidated assumptions are the silent killers of software projects. Business analysts often make the mistake of treating assumptions as passive notes or "disclaimers" at the end of a document. If an unstated assumption fails (e.g., you build a cloud-heavy system, but dealers actually work in concrete warehouses with no Wi-Fi), the entire solution collapses. Documenting assumptions exists to drag hidden beliefs into the light so they can be actively managed, tested, and validated.

## How to Use

1. **Document the "Silent Beliefs":** List every technical, environmental, and business condition you are relying on to make the solution successful. If you catch yourself thinking, "Well, obviously the users will..."—stop and write it down as an assumption.
2. **Assign a Validation Strategy:** Do not just log an assumption and forget it. Actively seek to prove or disprove it during Elicitation. (e.g., "Assumption: Dealers have Wi-Fi. Validation: Send a survey to 50 regional dealers by Q2.")
3. **The Pivot (Convert to Risk):** If an assumption is disproven (or remains highly uncertain as development approaches), convert or reclassify it as a project risk. You must then evaluate its impact on your approved requirements.
4. **Keep Stakeholders Accountable:** Share assumptions during baseline approvals. Make project sponsors explicitly agree to the foundational beliefs the project is built upon.

## The Bigger Picture & Downstream Links

- **Upstream (The Foundation):** Informs Strategy Analysis and initial project scoping. Assumptions are often paired with Constraints (restrictions on the solution).
- **Downstream (The Guardrails):** Protects Solution Design, system architecture, and deployment planning against unforeseen environmental failures. Feeds directly into the Risk Register.
- **The Rule of Integrity:** An assumption cannot contradict an established Business Rule. If a Business Rule dictates a strict compliance workflow, you cannot "assume" users will have an exception.

**Practical tip:** An assumption is just a project risk that hasn't grown up yet. If you assume the user is tech-savvy, and you're wrong, your UI design will fail. Write your assumptions down, put them on a wall, and ruthlessly try to prove them wrong before the developers write a single line of code.

---

*BABOK® Guide Section 6.1 Analyze Current State, Section 6.3 Assess Risks & Technique 10.38 Risk Analysis and Management.*
