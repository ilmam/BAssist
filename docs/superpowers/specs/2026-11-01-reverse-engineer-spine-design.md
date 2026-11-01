# Reverse-engineering a spine from existing code (`/spine-recover`)

Status: design, awaiting approval. Date: 2026-11-01.

Items marked **(my call)** were decided by the author, not by Ilmam. Everything else was said in the design conversation.

## Intent and why

Need Spine assumes requirements come first. In practice many projects arrive as running code with no written requirements: a handover, an audit, a rebuild, or a change request on something never specified. Today the tool has nothing to offer there.

This adds a skill that reads an existing **Shift Framework** solution and writes a **draft** spine into BAssist: Features, Scenarios, Functional Requirements, acceptance criteria and Business Rules recovered from what the code actually does. Code shows *what* the system does, not *why*, so everything above that level is a clearly marked placeholder that a business analyst must replace. Where the code does something that may be an accident or an undocumented policy, the skill asks a person via a BAssist comment.

The point is a head start and a visible list of questions, not a finished specification.

## Goals

- Produce draft Features, Scenarios (Given/When/Then), FRs with acceptance criteria, and Business Rules from a Shift solution (UI, API, backend, Functions).
- Keep the lineage structurally complete using generic placeholders, so every recovered item has a parent.
- Make it impossible to mistake the output for agreed requirements.
- Surface questions as BAssist comments, controlled by a **sensitivity** setting.
- Be safely re-runnable: after some comments are closed, a rerun pulls more questions without repeating any.

## Non-goals

- Stacks other than Shift. (Generic or "lens"-based support for other stacks is a later step.)
- Discovering real business needs, objectives or success metrics. Those stay placeholders.
- Writing or changing code, or generating tests.
- Recovering Screens/wireframes (UI design records) in this first version. **(my call)**

## Anti-goals (would make it a failure even if it runs)

- Marking anything `agreed`, closing any thread, or approving any record. Only people do those.
- Inventing a business reason. Unknown intent is recorded as an open Assumption or asked as a comment.
- Looking authoritative: a reader of BAssist must see at a glance that the item is inferred from code.
- Flooding the BA with hundreds of comments on a first run.

## Constraints

- BAssist is the system of record. If it is unreachable or the token is read-only, the skill stops and says so; it does not fall back to files. (Matches the existing method.)
- It reads the code and never modifies it.
- It uses existing BABOK concepts only: Business Rule, Assumption, comment, status. No new record kinds.
- Runs as the signed-in BAssist user, within their permissions.

## How it works

Invocation: `/spine-recover [path-to-solution] [--sensitivity N] [--dry-run]`. The BAssist project comes from `.need-spine.json`, as with the other spine skills. If there is no binding, suggest `/spine-init` once. **(my call: name and flags)**

1. **Inventory (Shift lens).** Walk the solution and list what it contains, using the Shift mapping below.
2. **Model.** Build, in memory, the set of Features, Scenarios, FRs and Business Rules, each with its code reference (file and line).
3. **Placeholders.** Ensure they exist (create only if missing, see below).
4. **Write recovered records** as `draft`, linked to their parent, each carrying its provenance. Write feature by feature so a stopped run keeps what was done.
5. **Find questions** (suspicious or unexplained behaviour, cross-layer mismatches) and **score** them.
6. **File comments** for the top slice set by sensitivity, on the record each concerns.
7. **Summarise**: for example "42 questions found, 8 filed, 34 below threshold; 61 records created, 12 already existed."

`--dry-run` does steps 1 to 5 and prints the ranked question list and the records it would create, writing nothing. **(my call)**

### Shift mapping

| In the Shift solution | Becomes |
|---|---|
| ShiftList / ShiftEntityForm pages, dialogs, nav menu entries | **Feature**, one per page; menu gives grouping |
| TypeAuth action trees and roles registered in Identity | **Stakeholder Need**, one per role or tree, replacing the generic placeholder where roles are visible |
| Entity classes, DTOs, required and unique fields, hash-ID/foreign keys | **Functional Requirements** (what can be captured and constrained) |
| Repository hooks, save rules, triggers, status transitions | **Business Rules** and **Scenarios** |
| Data-level access (company, branch, brand, region, team) | Business Rules on who sees what |
| Attention/overdue evaluators, PII masking, drafts, protected rows | Business Rules |
| Existing Reqnroll `.feature` files and tests | Reused as source for Scenarios and as evidence of intended behaviour |
| Azure Functions: timers, queue consumers, public intake endpoints | Features for behaviour with no screen |

Scenarios are built by tracing a user action down: UI action, then API endpoint, then backend rules. The Given/When/Then follows that path, and rules found on the way are linked to it.

### Placeholders

Created only if missing, all `draft`:

- One Business Need: "Existing system behaviour (recovered from code, business need unconfirmed)".
- One Business Objective under it: "Confirm and document current behaviour".
- One Stakeholder Need per role found in the permissions, or a single "Main stakeholder" if roles are not visible. **(my call: titles above are examples; the builder may refine wording)**
- One **open Assumption** per placeholder level: "Inferred from code, business intent unconfirmed." These keep the readiness check and build gate failing until a BA replaces the placeholders.

When a BA later replaces a placeholder with a real need, the recovered items are re-linked by a person, not by this skill.

### Provenance

Every recovered record states in its description: that it was inferred from code, and the code reference(s). Every comment and record carries a stable marker, `[recover:<fingerprint>]`, where the fingerprint is derived from the kind of item plus the normalised code location/symbol and behaviour key (not the line number alone, so small edits do not break it). **(my call: exact marker format)**

### Questions and sensitivity

A *question* is something the code does that a person should confirm. Signals used to score impact (**my call on the list and weights**; Ilmam confirmed the idea of ranking by impact, not these exact signals):

- money, pricing, tax or discount logic
- security, permissions, compliance
- irreversible or destructive actions
- behaviour many other scenarios depend on
- hard-coded values or special cases with no explanation nearby
- behaviour inconsistent with similar code
- cross-layer mismatches: UI offers an action the API rejects, an action tree the UI never uses, a rule enforced only in the UI

Sensitivity is 0 to 100:

- 100: every question becomes a comment.
- 0: no comments at all (records are still created unless `--dry-run`).
- N: sets a **budget of open questions**. The budget is N% (rounded up) of the questions found whose threads are not closed. Ranking is by score, with ties broken deterministically.
- Threads that are open, answered or implemented occupy the budget. **Closed threads free it**, so closing comments and rerunning files the next-highest unfiled questions (decided by Ilmam). Raising N also enlarges the budget.
- A closed question is never raised again and is excluded from the total the percentage is taken from. **(my call)**
- Default 30. **(my call)**

Below-threshold questions are not filed. Their records are still created. The summary states how many were left out, so nothing is silently lost.

Each comment states: what the code does, where, why it was ranked, and the specific question for the BA. It is attached to the most specific record it concerns.

### Re-running

The skill is **stateless**: BAssist is the only record of what has been done. On each run it re-reads the code and compares against existing records and comments by `[recover:...]` marker.

- Existing record with the marker: skip. Do not overwrite anything a person may have edited. If the code now behaves differently from the record, raise one comment about the difference rather than changing the record. **(my call)**
- Existing comment with the marker, in any state (open, answered, implemented, closed): do not raise it again.
- A closed thread settles that question. Applying an answer to requirements, tests or code is `/spine-apply`'s job, not this skill's.
- The usual rerun is: close some comments, rerun, and receive as many next-highest unfiled questions as the freed budget allows (see Questions and sensitivity). A rerun with nothing closed and the same sensitivity files nothing new.

Optional later optimisation: a disposable local cache of the analysis keyed by commit hash, deleted any time with no loss. Build only if reruns on large solutions prove too slow. **(my call: deferred)**

## Interaction with the existing method

- Recovered items are `draft` with open Assumptions and (for filed questions) open threads, so `/spine-ready`, `/spine-status` and the build gate all report them as not ready, as intended.
- `/spine-pull` can then bring the recovered Gherkin into the code project, and `/spine-verify` can trace scenarios to existing tests, which gives a first measure of how much current behaviour is untested.
- This skill bypasses the local gap log and `/spine-feedback`: sensitivity and `--dry-run` are the review control. **(my call)**

## Left to the builder

- Exact BAssist field names and rules: read them with `describe-entity` before writing.
- How to parse C#, Razor and attributes (Roslyn, plain search, or reading with the model), and how deep to trace.
- Final wording of placeholder titles and comment text.
- Scoring weights, provided the signals above are all represented and ranking is deterministic.
- Where the skill file lives. The working skills are in `.claude/skills/` in this repo, and the plugin copy is a separate packaging step. Follow what `spine-verify` does.

## Details settled after the builder check (all **my call**)

- **Fingerprint.** `kind` + the stable Shift-level name of the thing (namespace-qualified entity, page route, endpoint, rule or hook name) + a normalised `behaviour key` (for a Business Rule, its condition and outcome; for a Scenario, the action path; for an FR, the field/constraint). Line numbers are never part of it.
- **Rename or move.** Treated as a new item. The summary lists existing recovered records whose code reference no longer exists as "possibly removed or renamed". The skill never deprecates or deletes them; a person decides.
- **Where the marker is stored.** In the description of every recovered record and in the text of every comment. Use any machine-queryable filter that `describe-entity` offers; otherwise a rerun scans records and comments of the project. If a person edits a marker away, that item is treated as new, which is acceptable and visible in the summary.
- **Drift.** The record stores a short hash of the behaviour it described (`[recover:<fp>:<hash>]`). If the code now hashes differently, one comment is raised with marker `[recover:drift:<fp>:<newhash>]`, so the same change is raised once and a further change is raised again. The record is never rewritten.
- **Which record a comment goes on.** The most specific record the question concerns. A cross-layer mismatch goes on the Feature (the page), naming both code locations. Code that maps to no record goes on the nearest Business Rule or FR, else on the placeholder Business Need. "Many scenarios depend on it" is measured as the number of recovered scenarios that reference the rule.
- **Hand-written records already in the project.** Never duplicate them. If an existing non-recovered Feature or FR clearly covers the same page or entity, skip creating it and list the overlap in the summary. A partly specified project is therefore safe to run against.
- **Roles that appear on a later run.** A "Main stakeholder" placeholder that already exists is left alone. New role-based Stakeholder Needs are created beside it, and one comment on the generic placeholder notes that roles are now visible. Nothing is re-linked automatically.
- **Write check first.** Before the long analysis, do a cheap write-permission check against BAssist (for example reading the project and testing that a write is allowed); stop early with a clear message if it fails.
- **Test fixture.** No sample project exists today. The builder creates a small fixture Shift solution with a seeded UI/API permission mismatch, a hard-coded value, and one unexplained rule, and needs a scratch BAssist project to write to.

## Acceptance (how we will know it works)

- Against a sample Shift solution, `--dry-run` writes nothing to BAssist and prints a ranked list.
- A real run creates placeholders, draft records with provenance, and open Assumptions, and never sets `agreed`.
- Sensitivity 0 files no comments; 100 files all; 30 fills a budget of 30% of questions, rounded up.
- A second identical run creates no duplicate records or comments.
- After closing N filed threads, a rerun at the same sensitivity files up to N further questions, none previously filed, highest score first. Raising sensitivity also files more.
- If BAssist rejects the token, the run stops with a clear message and writes no files.
- Cross-layer mismatches (for example UI/API permission disagreement) are detected on the sample.
