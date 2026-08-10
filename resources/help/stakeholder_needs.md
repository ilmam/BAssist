---
title: Stakeholder Needs
---

## Context & Definition

A Stakeholder Need (formally classified in BABOK® as a Stakeholder Requirement) is the behavioral bridge connecting a Business Objective to the specific solution capabilities required to address it. While business needs describe why the enterprise must change, and business objectives define the measurable what, stakeholder needs describe what a specific operational role or user group must be able to do, decide, or experience for that outcome to be achieved.

## Why It Matters

Stakeholder needs keep delivery honest to real roles. Without them, objectives stay abstract and solution requirements drift into unowned feature lists. Each stakeholder need should be owned by identifiable stakeholders who can validate and accept the outcome.

## How to Use

1. **Name the Actor:** Identify which stakeholder role must be able to act, decide, or experience the change.
2. **Trace Upward Rigorously:** Explicitly anchor every stakeholder need back to its parent Business Objective. If a stakeholder need cannot trace to an active objective (and through it to a business need), it represents unapproved scope creep.
3. **Stay Behavioral:** Describe capability or experience, not UI widgets or technical design.
4. **Cover Downstream:** Drive Functional Requirements and/or BDD Features from the stakeholder need.

## The Bigger Picture & Downstream Links

- **Upstream (The Foundation):** Justified by and anchored to Business Objectives (and, through them, Business Needs). Also derived from the Stakeholders Matrix.
- **Downstream (The Drivers):** Directly spawns Solution Requirements — Functional Requirements and BDD Features with scenarios.
- **The Rule of Integrity:** Any solution packaging that cannot trace back to an active stakeholder need (and through it to an objective and need) should be challenged as orphan scope.

**Practical tip:** If two different roles need different behaviors to hit the same objective, write two stakeholder needs — don’t collapse them into one vague sentence.

---

*IIBA® and BABOK® are registered trademarks of International Institute of Business Analysis.*
