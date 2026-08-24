---
title: Data Dictionaries
---

## What it is

A **data dictionary** is the list of business entities and fields the BA already owes. Two pictures come from that list: a **conceptual ERD** (names and links) and a **design ERD** (fields, keys, types). Stub export is a one-time hand-off for developers.

## How it fits

Optional under **Requirements Modeling**, next to solution requirements and diagrams. Skip it when the sprint has no data shape to agree. It does not sit on the Needs → Tests conveyor.

## Guidance

- Capture name, meaning, type, keys, and which entity a field points at.
- Use **conceptual** with stakeholders (boxes and lines). Use **design** with developers (fields and keys). Switch tabs; both come from the same list.
- Let name conventions fill type (`*_id` → int, `*_at` → datetime). Override when the guess is wrong.
- Do not put string lengths or default values here unless you are willing to own them.
- Export is a stub. Developers write the rest and we do not round-trip from code.
