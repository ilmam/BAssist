---
title: Constraints
---

## Context & Definition

Constraints describe aspects of the current state, aspects of the planned future state that may not be changed by the solution, or mandatory elements of the design.

Unlike assumptions, which are educated guesses awaiting validation, constraints are the absolute, unyielding boundaries of your initiative. They are not suggestions; they are the immovable walls of your project scope and design space.

## Why It Matters

Ignoring constraints is the fastest way to build a brilliant solution that the enterprise cannot afford, cannot deploy, or is legally barred from using. Constraints are often treated as afterthoughts or generic boilerplate text. A ruthless Business Analyst knows that constraints dictate reality.

They must be carefully examined to ensure that they are accurate and justified. If you blindly accept a fake constraint (e.g., "we've always done it this way"), you artificially limit the solution space and suffocate innovation. If you miss a real constraint, you waste immense time and resources designing a fantasy. Documenting constraints exists to establish the exact parameters of the sandbox you are allowed to play in.

## How to Use

1. **Categorize the restriction:** Constraints may reflect budgetary restrictions, time restrictions, technology, infrastructure, policies, limits on the number of resources available, restrictions based on the skills of the team and stakeholders, a requirement that certain stakeholders not be affected by the implementation of the solution, compliance with regulations, and any other restriction.
2. **Interrogate the source:** They must be carefully examined to ensure that they are accurate and justified. Demand proof of the restriction before letting it dictate your design.
3. **Review enterprise directives:** Policies are a common source of constraints on a solution or on the solution space.
4. **Identify technical limits:** Technical constraints include any IT architecture standards that must be followed.
5. **Evaluate as risks:** Constraints, assumptions, and dependencies can be analyzed for risks and sometimes should be managed as risks themselves.

## The Bigger Picture & Downstream Links

- **Upstream (The Foundation):** Constraints fundamentally shape Strategy Analysis and your solution scope. Before you define a future state, you must know what parts of the enterprise or system are entirely off-limits.
- **Downstream (The Guardrails):** Constraints act as the ultimate filter during Requirements Analysis and Design Definition. If a proposed design option violates a documented constraint, that option is immediately disqualified. There is no negotiating with a genuine constraint at the delivery phase.

**Practical tip:** Treat constraints like a cage. Your job as a Business Analyst is to map exactly where the bars are, ruthlessly shake every single bar to verify it actually needs to be there, and then design the absolute best solution inside that cage.

If a stakeholder tells you, "We have to use a SQL database," your immediate response must be: "Show me the enterprise architecture standard or vendor contract that mandates it." If they cannot produce the policy, it is not a constraint—it is just a preference. Do not let stakeholders disguise their personal preferences as business constraints. Document the real ones, discard the fake ones, and get to work.

---

*BABOK® Guide Chapter 6 Strategy Analysis (constraints on current/future state and design).*
