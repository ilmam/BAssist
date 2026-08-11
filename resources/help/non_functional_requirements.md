---
title: Non-Functional Requirements
---

## Context & Definition

Non-Functional Requirements (NFRs), also called quality-of-service requirements, describe the **conditions under which** the solution must remain effective—performance, security, availability, usability, compliance, and similar attributes. They are solution-level siblings of Functional Requirements: both realize a Stakeholder Need, but NFRs measure *how well* or *under what constraints* the solution must behave rather than which behaviours it performs.

Example: “Inquiry search results must render within 2 seconds for a catalogue of 100,000 parts.”

## Why It Matters

A solution that implements every functional workflow but fails under load, exposes data, or cannot be accessed by intended users still fails the stakeholder need. Capturing NFRs as first-class artifacts keeps quality attributes visible, testable, and traced—not buried inside FR statements or left as informal expectations.

## How to Use

1. **Trace upward:** Anchor every NFR to the Stakeholder Need (or an approved Change Request) it supports.
2. **Choose a category:** Classify as performance, security, availability, reliability, usability, scalability, maintainability, accessibility, compliance, or other.
3. **Write a measurable description:** Prefer numbers, thresholds, and environments over vague adjectives (“fast”, “secure”, “easy”).
4. **Add acceptance criteria when useful:** Binary pass/fail checks (load test thresholds, audit outcomes) make the NFR ready for verification.
5. **Keep Constraints separate:** Project-level Boundaries (budget, platform, regulation) stay under Guardrails. Solution QoS attributes belong here under Solution Requirements.

## The Bigger Picture & Downstream Links

- **Upstream:** Stakeholder Needs (and optionally approved Change Requests).
- **Siblings:** Functional Requirements and BDD Features under the Solution Requirements hub.
- **Downstream:** Design, capacity planning, security reviews, acceptance / non-functional test plans, export and Project Documents packs.

**Practical tip:** If two reviewers can disagree whether a demo “met” the NFR, rewrite the description (and acceptance criteria) until the pass/fail condition is unambiguous.

---

*BABOK® Guide Section 2.3 Requirements Classification Schema (Solution / Non-Functional Requirements) & Section 7.1 Specify and Model Requirements.*
