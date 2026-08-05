---
title: BDD Features & Scenarios
---

## Context & Definition

A BDD (Behavior-Driven Development) feature translates a stakeholder need into structured, executable behaviour using Gherkin syntax. The feature (or story) states who benefits, what capability is delivered, and why it matters. A scenario is a concrete example of that behaviour, expressed as Given / When / Then steps that anyone on the delivery team can read the same way.

Together they form a pair: the feature frames the capability; scenarios prove it with deterministic examples. Prefer business intent over screens or implementation details—describe the outcome a role experiences, not the button they click.

## Why It Matters

Ambiguous requirements fragment into conflicting interpretations among business, development, and testing teams. BDD features and scenarios create a shared language—often forged in a "Three Amigos" conversation (BA, Developer, QA)—so acceptance criteria are concrete examples everyone can execute, not prose everyone rewrites.

Clear Given / When / Then examples reduce rework: edge cases surface early, business rules become visible in steps, and pass/fail is objective rather than negotiated after the code is built.

## How to Use

1. **Frame the Feature:** Write who / what / why (As a… I want… So that…) for one clear capability.
2. **Add Scenarios:** Cover the happy path and meaningful exceptions with concrete Given / When / Then steps. Prefer business outcomes over UI choreography.
3. **Bind the Guardrails:** Reflect applicable Business Rules, Constraints, and Assumptions in the steps or tags so policy is testable, not buried in footnotes.
4. **Keep Examples Deterministic:** Each scenario should have an unambiguous pass or fail—no "usually" or "the system somehow knows."
5. **Trace Upstream:** Link the feature to the Stakeholder Need it realises so behaviour stays anchored to a real voice and problem.
6. **Optional BPD coverage:** When a swimlane process/decision step elaborates this behaviour, select that process step on the Feature form. Lineage still runs through the Stakeholder Need; the step link is for process coverage only.

**Example — Dealer Inquiry State Management**

```
Feature: Dealer Inquiry State Management
  As a Parts Field Agent
  I want inquiry status to advance only when required data is complete
  So that procurement never acts on incomplete requests

  Scenario: Inquiry moves to Submitted when all mandatory fields are present
    Given a dealer inquiry draft with part number, quantity, and dealer code filled
    When the agent submits the inquiry
    Then the inquiry status is Submitted
    And procurement can see it in the open queue

  Scenario: Inquiry stays Draft when a mandatory field is missing
    Given a dealer inquiry draft missing the part number
    When the agent attempts to submit the inquiry
    Then the inquiry remains Draft
    And the agent is told which fields are still required
```

## The Bigger Picture & Downstream Links

- **Upstream (The Justification):** Stakeholder Needs, Business Rules, Constraints, and Assumptions that justify and bound the behaviour.
- **Downstream (The Execution):** Automated acceptance test frameworks (e.g., Cucumber, SpecFlow), QA test suites, and developer Test-Driven Development (TDD) that implement the same examples.
- **The Rule of Integrity:** Treat a feature without at least one scenario as incomplete for acceptance—a capability without an example cannot be agreed upon or verified. As a BA practice, prefer every scenario to sit under a feature that traces to a validated Stakeholder Need.

**Practical tip:** Never write UI steps in your scenarios! If your scenario says, "When I click the green submit button," you have created a fragile requirement. What happens if the UI changes to a swipe on a mobile app? Your test breaks, even though the business logic hasn't changed. Write "When the agent submits the inquiry." Keep it focused on the behaviour, not the screen layout.

---

*BABOK® Guide Section 7.2 Specify and Model Requirements & Technique 10.1 Acceptance and Evaluation Criteria.*
