---
title: Business Rules
---

## Context & Definition

Business Rules are the specific, testable directives that shape day-to-day business behavior and guide operational decision-making.

In BABOK, rules must be documented independently of the processes or systems that enforce them. They generally fall into two categories:

- **Behavioral (Operative) Rules:** Rules that govern action. They dictate what must or must not happen (e.g., "A spare parts inquiry cannot be closed until a formal dealer response is logged").
- **Definitional (Structural) Rules:** Rules that categorize or calculate knowledge (e.g., "A 'VIP Dealer' is defined as a branch with over $50,000 in quarterly parts orders").

## Why It Matters

A common mistake is burying business rules deep inside User Stories or Process Flows. When rules are hidden in a flow, they are easily forgotten, hard to test, and even harder to update when company policy changes.

Software cannot just execute actions; it must enforce governance. Without documented, centralized rules, developers will implement arbitrary logic that may violate company policy or legal regulations. This guide exists to act as the single source of truth for organizational logic.

## How to Use

1. **Separate the Rule from the Process:** Do not write rules as steps in a sequence. A rule should hold true regardless of how the user is interacting with the system (mobile app, desktop, or API).
2. **Write Atomically:** Break complex policies into single, declarative statements.
   - **Bad:** "If the ticket is open and the parts team replies, then it moves to Response status, but only if they attach a document."
   - **Good (Atomic):** "Rule 1: A ticket may only transition to 'Response' status if a document is attached."
3. **Bind to State Transitions:** Link your rules directly to the specific workflow states they govern. If a developer looks at the transition between 'Open' and 'Closed', the governing rules that block or allow that action should be discoverable alongside the transition.
4. **Remove Ambiguity:** Ensure every rule is testable. Avoid words like "sometimes," "usually," or "if possible." Use "must," "shall not," or "is defined as."

## The Bigger Picture & Downstream Links

- **Upstream (The Source):** Derived from external regulatory frameworks, internal organizational policies, and Strategy Analysis (Assumptions & Constraints).
- **Downstream (The Enforcement):** Rules are directly enforced by Functional Requirements and database validation constraints. They become the primary input for the Given and Then clauses in your BDD Scenarios.
- **The Rule of Integrity:** If a company policy changes, you should only have to update the rule once here—and every downstream Feature, User Story, and Test Case that relies on it should be reviewed.

**Practical tip:** Think of a business process as a highway, and a Business Rule as the speed limit sign. The highway tells you where you can go, but the speed limit applies universally to everyone driving on it. Don't build the speed limit into the pavement; put it on a sign so you can easily change it when the law changes.

---

*BABOK® Guide Technique 10.5 Business Rules Analysis & Section 7.1 Specify and Model Requirements.*
