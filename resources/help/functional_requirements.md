---
title: Functional Requirements
---

## Context & Definition

If a stakeholder requirement describes what a role must be able to do, a Functional Requirement (FR) describes what the **system** must do to make that possible. FRs are solution-level requirements: granular, testable obligations—behaviors, calculations, data handling, and automated responses—written so developers and QA do not have to invent the rules.

Example: “The system shall automatically timestamp the Open and Close events to calculate ticket duration.”

FRs are distinct from Non-Functional Requirements (quality of service: performance, security, usability, and similar conditions under which those behaviors must remain effective). Keep quality attributes out of the FR statement; capture them separately.

## Why It Matters

Developers cannot code from a vague wish such as “I want to track tickets.” They need to know *how* the system tracks them, under what conditions, and what “done” means. Explicit shall-statements cut guesswork, rework, and failed UAT. Atomic FRs also give teams a clear obligation when Gherkin-style examples are not the right packaging for that slice of behavior.

## How to Use

1. **Trace upward:** Anchor every FR to the stakeholder requirement it realises. Behavior with no parent need is candidate scope creep and should be challenged before build.
2. **Write the shall statement:** Use declarative language: “The system shall [action] when [condition].” Prefer *shall* / *must* over soft verbs (*should*, *might*). Capture optional triggers or entry conditions alongside the statement when that keeps the obligation clear.
3. **Define acceptance criteria:** List binary pass/fail checks for this obligation. An FR without a clear verification path is untestable and should not be treated as ready for development.
4. **Stay above UI and stack:** Specify behaviour and information, not layout or frameworks. Write “The system shall allow the agent to submit the inquiry,” not “The system shall show a green Submit button on the left.”
5. **Keep NFRs and policy separate:** Do not bury SLAs, security limits, or load metrics inside the FR. If the behaviour enforces a business rule, reference that rule so policy stays maintainable in one place.
6. **Keep status honest:** Treat early wording as draft until the obligation and acceptance checks are clear enough for agreement.

## The Bigger Picture & Downstream Links

- **Upstream:** Derived from and justified by stakeholder requirements; bounded by business rules, constraints, and assumptions where relevant.
- **Related solution packaging:** BDD features and scenarios are an alternative way to express solution behaviour as executable examples. Use FRs, examples, or both—whichever makes the obligation unambiguous—without forcing one form to parent the other.
- **Downstream:** FRs feed design, development, and system/integration testing. Acceptance criteria are the verification path for this form of specification.
- **The Rule of Integrity:** An FR without an upstream stakeholder link is candidate scope creep. An FR without clear acceptance criteria is untestable.

**Practical tip:** Never describe the UI in a Functional Requirement. If two reviewers can disagree whether a demo passed the statement, tighten the verb, object, and condition (and the acceptance criteria) until only one honest reading remains.

---

*BABOK® Guide Section 2.3 Requirements Classification Schema (Solution / Functional Requirements) & Section 7.1 Specify and Model Requirements.*
