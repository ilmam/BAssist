---
title: Scope
---

## Context & Definition

Scope explicitly defines the hard boundaries of an initiative. It acts as a filter that divides the universe of possible requirements into two distinct categories: In-Scope (the specific business processes, user groups, systems, and capabilities that will be changed or delivered) and Out-of-Scope (the capabilities, regions, or integrations that are explicitly excluded).

In BABOK terms, scope modelling visualizes the boundaries of control, change, need, and the solution itself.

## Why It Matters

Unbounded projects die from "scope creep"—the silent, continuous accumulation of new features that destroy budgets and timelines. Business analysts often make the mistake of only documenting what will be built. By failing to document what won't be built, they leave room for stakeholders to assume their pet features are included. Defining scope sets definitive, contractual boundaries to manage stakeholder expectations and protect the development team's capacity.

## How to Use

1. **Define the "In-Scope" boundaries:** Be specific across multiple dimensions. Do not just list software features.
   - **Process Scope:** Which workflows are we touching? (e.g., "In-Scope: Spare parts digital ordering. Out-of-Scope: Warranty claims processing.")
   - **Actor Scope:** Which stakeholder groups will use this? (e.g., "In-Scope: Parts Field Team. Out-of-Scope: End-consumers.")
   - **System Scope:** Which databases or platforms are being modified?
2. **Weaponize the "Out-of-Scope" list:** Explicitly list adjacent features or requests that were discussed but rejected or deferred. This is your defense mechanism when a stakeholder later asks, "Why isn't X in the release?"
3. **Trace to Business Needs:** A scope boundary is only valid if it directly supports an approved Business Need. If an In-Scope item doesn't trace back to a Need, it is unapproved gold-plating.

## The Bigger Picture & Downstream Links

- **Upstream (The Justification):** Derived directly from Strategy Analysis (Business Objectives and Business Needs). Scope is the container that holds the solution to those needs.
- **Downstream (The Execution):** Dictates the absolute limits for Stakeholder Requirements, Functional Requirements, Solution Design, and BDD Scenarios.
- **The Rule of Integrity:** If a developer or BA tries to write a User Story or BDD feature for something that falls into the "Out-of-Scope" bucket, it should be treated as an immediate compliance failure and challenged before work proceeds.

**Practical tip:** Think of Scope like a fence around a house. The Business Need tells you why you bought the house. The Requirements are the furniture inside. But if you don't build the fence (Scope), the neighbors will start planting their trees in your yard.

---

*BABOK® Guide Section 6.4 Define Change Strategy – Solution Scope & Technique 10.41 Scope Modelling.*
