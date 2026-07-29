---
title: Stakeholder Needs
---

## Context & Definition

A Stakeholder Need (formally classified in BABOK® as a Stakeholder Requirement) is the behavioral bridge connecting a high-level Business Need to the specific solution capabilities required to address it. While business needs describe what is broken or missing for the enterprise as a whole, stakeholder needs describe what a specific operational role or user group must be able to do, decide, or experience for that problem to be resolved.

In practice: If a business need highlights that manual phone and email inquiries cause high error rates in spare parts procurement, the corresponding stakeholder need for the Parts Field Team states: “As a Parts Field Agent, I want to submit a parts inquiry through a single digital form, so that procurement receives complete, validated requests without manual re-keying.”

## Why It Matters

Jumping straight from a high-level business problem into screens, APIs, or software features is a critical failure point for business analysts. When you skip this layer, developers are forced to guess user workflows, resulting in poor user experience (UX) and ultimate system rejection by operators. Capturing stakeholder needs forces a disciplined pause: you must explicitly name who needs the change, what exact behavior they require, and which business need that behavior serves before anyone commits to solution design.

## How to Use

1. **Use Persona Syntax:** Express each requirement using standard agile structures to maintain clarity on who needs the capability and why:

   *As a [User Role / Stakeholder], I want to [Action / Capability], so that [Business Value / Benefit].*

2. **Trace Upward Rigorously:** Explicitly anchor every stakeholder need back to its parent Business Need. If a stakeholder need cannot trace to an active business need, it represents unapproved scope creep.

3. **Keep it Behavioral, Not Technical:** Focus strictly on what the user needs to achieve within their operational environment (e.g., viewing branch-level visibility or submitting inquiries digitally)—avoid mentioning database schemas, UI button colors, or internal tech stacks.

4. **Assign Accountability:** Link the requirement directly to the specific stakeholder group defined in your Stakeholders Matrix to ensure clear sponsorship and validation sign-off.

## The Bigger Picture & Downstream Links

- **Upstream (The Foundation):** Justified by and anchored to Business Needs and derived directly from the Stakeholders Matrix.
- **Downstream (The Guardrails):** Acts as the direct parent source for Solution Requirements (Functional & Non-Functional), application Features, BDD Scenarios (Given/When/Then), and User Acceptance Testing (UAT) test cases.
- **The Rule of Integrity:** No solution feature or functional requirement should proceed without a valid, approved parent stakeholder need. Orphaned specs should be treated as incomplete coverage and challenged in review before work continues. (In practice, a feature may exist without a parent link until that gap is corrected.)

**Practical tip:** Never write a stakeholder need as a technical mandate like “The system must have a dropdown menu.” Write it as a human behavior: “I need to select my active branch region so I can filter my open tickets.” Let the developers figure out if that behavior becomes a dropdown, a radio button, or an autocomplete field. Stay focused on the user's operational reality!

---

*BABOK® Guide Section 2.3 Requirements Classification Schema – Stakeholder Level & Chapter 7 Requirements Analysis and Design Definition.*
