---
title: BDD Features & Scenarios
---

## Context & Definition

A BDD feature translates a stakeholder need into structured, executable behaviour using Gherkin. The feature (or story) states *who* benefits, *what* capability is delivered, and *why* it matters. A scenario is a concrete example of that behaviour, expressed as Given / When / Then steps that anyone on the delivery team can read the same way.

Together they form a pair: the feature frames the capability; scenarios prove it with deterministic examples. Prefer business intent over screens or implementation details—describe the outcome a role experiences, not the button they click.

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

## Why It Matters

Ambiguous requirements fragment into conflicting interpretations among business, development, and test. BDD features and scenarios create a shared language—often forged in a Three Amigos conversation—so acceptance criteria are examples everyone can execute, not prose everyone rewrites.

Clear Given / When / Then examples reduce rework: edge cases surface early, business rules become visible in steps, and pass/fail is objective rather than negotiated after the build.

## How to Use

1. **Frame the feature:** Write who / what / why (As a… I want… So that…, or an equivalent story statement) for one clear capability.
2. **Add scenarios:** Cover the happy path and meaningful exceptions with concrete Given / When / Then steps. Prefer business outcomes over UI choreography.
3. **Bind the guardrails:** Reflect applicable business rules, constraints, and assumptions in the steps or tags so policy is testable, not buried in footnotes.
4. **Keep examples deterministic:** Each scenario should have an unambiguous pass or fail—no “usually” or “the system somehow knows.”
5. **Trace upstream:** Link the feature to the stakeholder need it realises so behaviour stays anchored to a real voice and problem.

## The Bigger Picture & Downstream Links

- **Upstream (why it exists):** Stakeholder needs, business rules, constraints, and assumptions that justify and bound the behaviour.
- **Downstream (what it drives):** Automated acceptance (e.g. Cucumber, SpecFlow), QA suites, and developer TDD that implement the same examples.
- **The rule of integrity:** Treat a feature without at least one scenario as incomplete for acceptance—capability without an example cannot be agreed or verified. Prefer every scenario to sit under a feature that traces to a stakeholder need.

---

*BABOK® Guide Section 7.2, Technique 10.1 (Acceptance and Evaluation Criteria), Technique 10.48 (User Stories), Chapter 11 (Agile Perspective).*
