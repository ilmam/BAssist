---
title: Solution Requirements
---

## Context & Definition

While Stakeholder Requirements describe what the users need to achieve, Solution Requirements describe exactly how the system will behave to meet those needs. They are the detailed characteristics and capabilities that a solution must possess.

In BABOK, Solution Requirements are always divided into two critical categories:

- **Functional Requirements (FRs):** The specific behaviors, responses, and information the system will manage (e.g., "The system shall automatically timestamp the 'Open' and 'Close' events to calculate ticket duration").
- **Non-Functional Requirements (NFRs) / Quality of Service:** The environmental conditions under which the solution must remain effective, such as performance, security, compliance, and usability (e.g., "The dashboard must render search results within 2 seconds for a database of 100,000 records").

This hub hosts three dialects: **Functional Requirements** (classic “system shall” statements), **Non-Functional Requirements** (quality of service), and **BDD Features** (executable behaviour examples). All three link upstream to a Stakeholder Need.

## Why It Matters

Developers cannot code directly from a stakeholder's wish (e.g., "I need to communicate with dealers"). If you pass high-level needs directly to an engineering team, they are forced to guess the technical boundaries, data structures, and security protocols. Solution Requirements exist to eliminate developer guesswork by providing unambiguous, highly granular system instructions.

## How to Use

1. **Enforce the "System Shall" Syntax:** Write from the perspective of the machine, not the human. Use definitive language: "The system shall [perform action] when [condition is met]."
2. **Split Functional from Non-Functional:** Always categorize your requirements. Never bury a critical security protocol or a page-load speed metric inside a functional workflow description.
3. **Bind to Business Rules:** If a functional requirement calculates a value or restricts access, link it directly to the defining Business Rule (e.g., "The system shall restrict file uploads to PDF formats, per BR-09").
4. **Demand Testability:** Look at every Solution Requirement and ask, "Can QA write a clear Pass/Fail test for this?" If the answer is no (e.g., "The system shall be easy to use"), it must be rewritten into measurable terms.

## The Bigger Picture & Downstream Links

- **Upstream (The Justification):** Every Solution Requirement must trace backward to a parent Stakeholder Requirement. If the system is doing something that no user explicitly asked for, it is gold-plating (unapproved scope).
- **Downstream (The Execution):** Acts as the direct parent for BDD Scenarios (Given/When/Then), UI/UX technical designs, and automated testing scripts.
- **The Rule of Integrity:** A Solution Requirement is the final bridge between business language and technical execution. As a BA practice, treat build work as incomplete until it satisfies an approved Solution Requirement.

**Practical tip:** Never ignore the Non-Functional Requirements. Analysts often spend most of their time on functional workflows (buttons, forms, and data) and forget about NFRs. A system that perfectly tracks parts inquiries but crashes when 50 dealers log in at the same time is a failed project. Security, load times, and scalability are just as important as the buttons on the screen.

---

*BABOK® Guide Section 2.3 Requirements Classification Schema & Chapter 7 Requirements Analysis and Design Definition.*
