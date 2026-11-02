---
name: spine-recover
description: "Use when a project already has code but no written requirements and the user wants a draft spine recovered from it: \"reverse engineer the requirements\", \"document this existing system\", \"write the BDD features from the code\", \"recover the spine\", \"strip down this project\", or runs /spine-recover. Reads a Shift Framework solution and writes draft Features, Scenarios, Functional Requirements and Business Rules into BAssist, with placeholder needs above them and sensitivity-limited questions for the BA."
argument-hint: "[path to the solution] [--sensitivity 0-100, default 30] [--dry-run]"
---

# Recover a draft spine from existing Shift code

The method normally runs requirements first. This skill is for the other case: running code, no spec. It reads the code and writes what the system **does** into BAssist as `draft`. Code cannot tell us **why**, so everything above the solution level is a labelled placeholder that a BA must replace. Where the code does something that may be an accident or an undocumented policy, it asks a person through a comment.

Everything recovered is inferred, never agreed. Design: `docs/superpowers/specs/2026-11-01-reverse-engineer-spine-design.md`.

## Rules that never bend

- Never set any status to `agreed`; everything this skill writes is `draft`.
- Never close a thread, approve a record, deprecate or delete anything.
- Never edit the scanned code.
- Never invent a business reason. Unknown intent is an open Assumption or a question.
- BAssist is the system of record. If it is unreachable or the token is read-only, stop and say so. Do not fall back to files.
- Duplicates, ranking and the question budget are decided by `recover_cli.py`, not by you. Do not override its plan.

## Steps

Scratch files go in `spine-recover-work/` (never commit it). `CLI` below means `python .claude/skills/spine-recover/recover_cli.py`.

1. **Bind and preflight.** Read `.need-spine.json` for the BAssist project; if missing, suggest `/spine-init` once. Call `list-projects` and `get-readiness`. Then prove you can write, before any analysis: find the placeholder Business Need (below); if it does not exist, create it now. A refusal means a read-only token: stop with a clear message and write no files. With `--dry-run`, skip the write and say that write access was not checked.
2. **Scan.** `CLI scan <solution path> --out spine-recover-work/inventory.json`. It lists pages, action trees, DTO constraints, constants, refusals, data-level access, timers and queue consumers, and existing `.feature` files, plus the questions it can detect.
3. **Judgement pass.** Read the code behind each Feature the scanner found and add what a line scanner cannot:
   - For each user action (a button, a form submit), trace UI action → API endpoint → backend rules, and add a `scenario` item to `inventory.json` (`symbol` = `<feature symbol>#<action>`, `behaviour` = the Given/When/Then steps joined by ` / `, `parent_symbol` = the feature symbol). Cover the happy path, each refusal and each rule you traced. Reuse existing Reqnroll scenarios rather than rewriting them.
   - Add questions the scanner cannot see (a rule enforced only in the UI, behaviour inconsistent with similar code) to `questions`, with `symbol`, `reason` (a short slug plus `:detail`), `text`, `signals` (from: money, security, irreversible, cross_layer, hardcoded, inconsistent), `dependents`, `refs`.
   - State only what the code shows. Where intent is unclear, write the question, not a guess.
4. **Fetch what BAssist already holds.** Call `describe-entity` for each kind you will write (Feature, Scenario, Functional Requirement, Business Rule, Stakeholder Need, Assumption) before first use. Save `list-records` output as `spine-recover-work/records.json` (`[{id, kind, title, description}]`) and `list-comments` output as `comments.json` (`[{id, record_id, status, body}]`).
5. **Plan.** `CLI plan --inventory spine-recover-work/inventory.json --records spine-recover-work/records.json --comments spine-recover-work/comments.json --sensitivity <N> --out spine-recover-work/plan.json`. Invalid sensitivity exits 2: relay the message.
6. **Dry run stops here.** Print the summary line, the ranked `file_questions` (symbol, reason, one-line text) and what `create` would add. Write nothing.
7. **Write the records**, in lineage order, feature by feature so a stopped run keeps what it did:
   - Placeholders first, if missing (below).
   - Stakeholder Need per role in `roles` (title = tree name), linked to the placeholder Objective.
   - For each `plan.create` item: status `draft`, linked to its parent (Feature/FR/Rule → the Stakeholder Need whose action tree guards it, else "Main stakeholder"; Scenario → its Feature). Description begins `Inferred from code:`, then the behaviour, the code references (`file:line`), and the item's `marker` exactly as given. Look up the Status id for `draft` with `list-records`. Use fields from `describe-entity`; if a field does not exist, say it in the description.
   - Items in `plan.overlap`: do not create. List them in the summary.
8. **Raise drift.** For each `plan.drift`, one comment on `record_id`: what the record says, what the code does now, the refs, and the marker `[recover:drift:<id>:<new_hash>]`. Never edit the record.
9. **File questions.** For each `plan.file_questions`, `add-comment` on the record its `symbol` names (match the record whose description carries that item's marker). If the symbol matches no record, use the nearest Business Rule or FR for the same code; failing that, the placeholder Business Need. Comment template:

   ```
   What the code does: <one sentence>
   Where: <file:line> (and the second location for a cross-layer mismatch)
   Why this was ranked: <signals in words>
   Question for the BA: <one specific question>
   <marker from the plan>
   ```
10. **Summarise.** Print `plan.summary`, then list: possibly removed or renamed records (do nothing to them), overlaps with hand-written records, how many questions are below threshold, and that `/spine-pull` and `/spine-verify` are the next steps. Say plainly that all of it is draft and inferred.

## Placeholders

Created only if missing (find them by title), all `draft`, each with one **open Assumption** "Inferred from code, business intent unconfirmed.":

- Business Need: "Existing system behaviour (recovered from code, business need unconfirmed)"
- Business Objective under it: "Confirm and document current behaviour"
- Stakeholder Need "Main stakeholder" when roles are not visible in the code. If it already exists and roles later appear, leave it alone, create the role needs beside it, and add one comment on it saying roles are now visible. Nothing is re-linked automatically.

These keep `/spine-ready` and the build gate failing until a BA replaces them.

## Re-running

Safe to repeat. Records and comments are matched by their `[recover:...]` markers, so nothing is duplicated. Closing a comment in BAssist frees room in the question budget (N% of the questions whose threads are not closed); the next run files the next-highest unfiled questions. A closed question is never raised again, and applying its answer is `/spine-apply`'s job.

## Honest limits

The scanner is a line scanner for Shift conventions, not a compiler: it will miss logic that is built indirectly, and PII masking, drafts and protected rows are not detected yet. Scenarios written in the judgement pass are only as good as the code you traced. This gives a head start and a ranked list of questions, not a specification.
