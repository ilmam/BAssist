# `/spine-recover` Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `/spine-recover` skill that reads an existing Shift Framework solution and writes a draft, clearly-inferred spine (plus sensitivity-limited BA questions) into BAssist, safely re-runnable.

**Architecture:** Deterministic work lives in small stdlib-only Python modules (identity/markers, question scoring and budget, reconciliation against BAssist, Shift scanner, cross-layer checks) with unit tests. A `SKILL.md` drives the judgement work (writing Given/When/Then, drafting questions) and calls BAssist MCP tools; the CLI hands it a machine-checked action plan, so the model never decides duplicates, ranking or budget.

**Tech Stack:** Python 3 stdlib (`unittest`, `re`, `hashlib`, `json`), same style as `.claude/skills/spine-verify/trace_check.py`; Markdown skill file; BAssist MCP tools.

**Spec:** `docs/superpowers/specs/2026-11-01-reverse-engineer-spine-design.md`

## Global Constraints

- Skill folder: `.claude/skills/spine-recover/`; command `/spine-recover [path-to-solution] [--sensitivity N] [--dry-run]`; default sensitivity 30.
- Python stdlib only; no pip dependencies. Tests run with `python -m unittest discover -s .claude/skills/spine-recover/tests -v`.
- The skill never sets any status to `agreed`, never closes a thread, never edits the scanned code, never deletes or deprecates records.
- Everything recovered is `draft`; each placeholder level has one open Assumption, text: "Inferred from code, business intent unconfirmed."
- Placeholder Business Need title: "Existing system behaviour (recovered from code, business need unconfirmed)"; Objective: "Confirm and document current behaviour"; fallback stakeholder: "Main stakeholder".
- Markers: record description `[recover:<id>:<hash>]`; question comment `[recover:q:<id>]`; drift comment `[recover:drift:<id>:<newhash>]`. Line numbers are never part of an id.
- Sensitivity: integer 0 to 100; budget = ceil(N% of non-closed questions); open, answered and implemented threads occupy the budget; closed threads free it and are never re-raised.
- If BAssist is unreachable or the token is read-only: stop with a clear message, write no files, do a write-permission check before the long analysis.
- Uses only existing BAssist concepts (Business Rule, Assumption, comment, status); no new record kinds.

## Review Focus

Most likely to bite first:

1. Arabic or other non-ASCII text in titles, symbols and comments (Toyota Iraq UIs): ids and markers must be stable and survive round-trip.
2. Windows CRLF line endings and BOMs in `.cs` / `.razor` files: scanner results must be identical to LF files.
3. Sensitivity given as `-5`, `150`, `abc` or `30.5`: clear error or clamp (see Task 2), never a crash or a silent wrong budget.
4. Solution with no pages, or no permissions: empty inventory runs cleanly, summary says zero, placeholders only.
5. Run stopped halfway then rerun: no duplicates, remaining items created, nothing re-filed.

---

## File Structure

All under `.claude/skills/spine-recover/`:

| File | Responsibility |
|---|---|
| `recover_core.py` | `item_id`, `behaviour_hash`, marker build/parse |
| `questions.py` | impact scoring, budget, selection |
| `reconcile.py` | desired items vs existing BAssist records/comments → actions |
| `shift_scan.py` | Shift solution → inventory JSON; cross-layer question detection |
| `recover_cli.py` | `scan`, `plan` commands wiring the modules; summary text |
| `SKILL.md` | workflow, placeholders, comment template, BAssist tool usage, dry-run |
| `tests/test_*.py`, `tests/fixture_shift/` | unit tests and a small fixture Shift solution |

Data contracts used between modules (all JSON-serialisable dicts):

- **Item**: `{kind: "feature"|"scenario"|"fr"|"rule"|"role", symbol: str, behaviour: str, title: str, parent_symbol: str|None, refs: [{file: str, line: int}]}`
- **Question**: `{symbol: str, reason: str, text: str, signals: [str], dependents: int, refs: [...]}` where `symbol` names the item the comment attaches to.
- **Existing records** (from BAssist `list-records`): `{id, kind, title, description}`. **Existing comments** (from `list-comments`): `{id, record_id, status, body}` with status in `open|answered|implemented|closed`.

---

### Task 1: Identity and markers

**Files:** Create `recover_core.py`, `tests/test_core.py`

**Interfaces:**
- Produces: `item_id(kind: str, symbol: str) -> str` (12 hex chars); `behaviour_hash(behaviour: str) -> str` (8 hex chars); `build_marker(id_, hash_) -> str`, `build_question_marker(id_) -> str`, `build_drift_marker(id_, new_hash) -> str`; `parse_markers(text: str) -> list[dict]` returning `{type: "record"|"question"|"drift", id, hash}`.

- [ ] **Step 1: Write failing tests** in `tests/test_core.py` (unittest): same `kind`+`symbol` gives same id regardless of surrounding whitespace, case of `kind`, `\r\n` vs `\n`, and Unicode normalisation form (NFC vs NFD of `"طلب الشراء"`); different kinds give different ids; `behaviour_hash("a  and  b")` equals `behaviour_hash("a and b\r\n")`; `parse_markers` finds a record marker, a question marker and a drift marker embedded in prose, and returns `[]` for text with none or a malformed `[recover:`.
- [ ] **Step 2:** Run `python -m unittest discover -s .claude/skills/spine-recover/tests -v`. Expected: FAIL (module missing).
- [ ] **Step 3: Implement** the functions in `recover_core.py`. Normalise with NFC, `casefold` on kind, collapse whitespace, strip; SHA-256 truncated.
- [ ] **Step 4:** Rerun. Expected: PASS.
- [ ] **Step 5: Commit** `feat(spine-recover): stable ids and markers`.

### Task 2: Question scoring, budget and selection

**Files:** Create `questions.py`, `tests/test_questions.py`

**Interfaces:**
- Consumes: `item_id`, `build_question_marker`, `parse_markers` from Task 1.
- Produces: `WEIGHTS: dict[str,int]` = `{money:5, security:5, irreversible:4, cross_layer:4, hardcoded:3, inconsistent:2}`; `score(q: dict) -> int` (sum of weights for q's signals plus `min(q["dependents"], 5)`); `parse_sensitivity(raw: str|int) -> int` (raises `ValueError` with message containing "0 to 100" for non-integers or out of range); `select(questions: list[dict], threads: list[dict], sensitivity: int) -> dict` returning `{"file": [questions to file, ranked], "occupied": int, "closed": int, "unfiled_left": int, "budget": int}`.

- [ ] **Step 1: Write failing tests**: `parse_sensitivity` rejects `"-5"`, `"150"`, `"abc"`, `"30.5"` with the message above and accepts `"0"`, `100`. `select` with 10 questions, no threads: sensitivity 0 files none; 100 files all 10; 30 files 3; 25 files 3 (rounded up). With 10 questions, sensitivity 30, three already filed as open threads: files none (budget 3 occupied). Close two of them (status `closed`): total non-closed becomes 8, budget `ceil(2.4)=3`, occupied 1, so files 2 highest-scored unfiled; closed questions never reappear. Equal scores order by question id ascending, same result on repeated calls. A thread is matched to a question by `build_question_marker(item_id("question", f"{symbol}#{reason}"))` appearing in its body.
- [ ] **Step 2:** Run tests; expect FAIL.
- [ ] **Step 3: Implement** per the contracts above.
- [ ] **Step 4:** Run; expect PASS.
- [ ] **Step 5: Commit** `feat(spine-recover): question scoring and budget`.

### Task 3: Reconciliation against BAssist

**Files:** Create `reconcile.py`, `tests/test_reconcile.py`

**Interfaces:**
- Consumes: Task 1 functions; Item and existing-record/comment shapes above.
- Produces: `reconcile(items: list[dict], records: list[dict], comments: list[dict]) -> dict` with keys `create` (items to create), `skip` (already recovered, unchanged), `drift` (items whose stored hash differs, each with `old_hash`, `new_hash`, `record_id`, skipped if a drift comment with `new_hash` already exists), `possibly_removed` (records with a recover marker whose id no item produces), `overlap` (items with no marker match but an existing non-recovered record of the same kind whose title equals the item title, case-insensitively).

- [ ] **Step 1: Write failing tests**: empty project → everything in `create`; second run with the records the first run would have made → all `skip`, `create` empty; one record's stored hash differs → one `drift`; same drift with an existing drift comment for that new hash → none; a renamed symbol → new item in `create`, old record in `possibly_removed`; hand-written Feature titled "Order List" and a recovered item titled "order list" → `overlap`, not `create`; a record whose marker was edited out → treated as new (in `create`); half the items already created (partial run) → the rest in `create`, no duplicates.
- [ ] **Step 2:** Run; expect FAIL.
- [ ] **Step 3: Implement** `reconcile`. Index records by parsed record markers; overlap check only for records without markers.
- [ ] **Step 4:** Run; expect PASS.
- [ ] **Step 5: Commit** `feat(spine-recover): reconcile with existing BAssist state`.

### Task 4: Shift scanner and fixture solution

**Files:** Create `shift_scan.py`, `tests/fixture_shift/**`, `tests/test_scan.py`

**Interfaces:**
- Consumes: Item contract. The implementer should read the `shift-framework`, `shift-blazor-ui`, `shift-entity-data` and `shift-entity-features` skills for how pages, entities, access trees and rules look, then pin the patterns in the fixture so the tests, not memory, define them.
- Produces: `scan(root: Path) -> dict` returning `{"items": [Item...], "roles": [str], "api_actions": {...}, "ui_actions": {...}}`. Item kinds per the spec's Shift mapping table: pages (`ShiftList`/`ShiftEntityForm` components, nav entries) → `feature`; access trees/roles → `role`; entities and DTO constraints → `fr`; repository hooks, save rules, status transitions, data-level access, attention/overdue, PII → `rule`; Functions timers/queue consumers → `feature`; existing Reqnroll scenarios → `scenario` items marked in `behaviour` as "existing test".

- [ ] **Step 1: Build the fixture** `tests/fixture_shift/`: a tiny solution with one entity (`Order`: required `CustomerName`, a status enum, a hard-coded discount cap `0.20` with no comment), one list page and one form page for it, one access tree with two actions (`OrdersRead`, `OrdersCancel`), one API endpoint that requires `OrdersCancel`, a UI button for cancel that checks only `OrdersRead` (the seeded UI/API mismatch), one timer function, one Reqnroll `.feature`, and one page whose title is Arabic. Write one file with CRLF endings and a BOM.
- [ ] **Step 2: Write failing tests**: items contain exactly the expected features, roles, rules and FRs for the fixture (assert symbols and kinds, not full text); the CRLF/BOM file yields the same items as an LF copy of it; Arabic title is preserved verbatim in `title`; an empty directory returns `{"items": [], "roles": [], ...}` without raising; refs carry file and line.
- [ ] **Step 3:** Run; expect FAIL.
- [ ] **Step 4: Implement** `scan` with regex/line scanning (no .NET parsing). Read files with `utf-8-sig` and universal newlines.
- [ ] **Step 5:** Run; expect PASS.
- [ ] **Step 6: Commit** `feat(spine-recover): Shift inventory scanner and fixture`.

### Task 5: Cross-layer and suspicious-behaviour questions

**Files:** Modify `shift_scan.py`; Create `tests/test_questions_detect.py`

**Interfaces:**
- Consumes: scan result from Task 4.
- Produces: `detect_questions(scan_result: dict) -> list[dict]` (Question contract). Reasons and signals: `ui-api-mismatch` (`cross_layer`, plus `security` when the API action is stricter), `unused-action` (action tree entry no UI references; `security`), `ui-only-rule` (`inconsistent`), `hardcoded-value` (`hardcoded`; add `money` when the name or neighbours mention price, discount, tax, amount), `destructive-action` (`irreversible`). `dependents` = number of scenario items whose behaviour references the rule's symbol. Cross-layer mismatches attach to the page's feature symbol and name both file locations in `text`.

- [ ] **Step 1: Write failing tests** against the fixture: the seeded cancel mismatch yields one `ui-api-mismatch` question on the order list feature with `security` and `cross_layer` signals and both refs; the `0.20` cap yields `hardcoded-value` with `money`; a fixture with no mismatches yields none; output order is deterministic.
- [ ] **Step 2:** Run; expect FAIL.
- [ ] **Step 3: Implement** `detect_questions`.
- [ ] **Step 4:** Run; expect PASS.
- [ ] **Step 5: Commit** `feat(spine-recover): detect cross-layer questions`.

### Task 6: CLI and summary

**Files:** Create `recover_cli.py`, `tests/test_cli.py`

**Interfaces:**
- Consumes: Tasks 1 to 5.
- Produces: `python recover_cli.py scan <root> --out inventory.json` (writes `{"items","roles","questions"}`); `python recover_cli.py plan --inventory inventory.json --records records.json --comments comments.json --sensitivity N [--out plan.json]` writing `{"create","skip","drift","possibly_removed","overlap","file_questions","summary"}` where `summary` is one line, e.g. `42 questions found, 8 filed, 34 below threshold; 61 records to create, 12 already existed`. Exit code 2 with a message on invalid sensitivity.

- [ ] **Step 1: Write failing tests**: scan then plan on the fixture with empty records/comments produces a non-empty `create` and exactly `ceil(30% × questions)` in `file_questions`; running plan again with records/comments built from that plan's output produces empty `create` and empty `file_questions`; `--sensitivity 0` gives empty `file_questions` but unchanged `create`; invalid sensitivity exits 2; plan on an empty-solution inventory prints a summary stating zero items without error.
- [ ] **Step 2:** Run; expect FAIL.
- [ ] **Step 3: Implement** the CLI with `argparse`.
- [ ] **Step 4:** Run the whole suite; expect PASS.
- [ ] **Step 5: Commit** `feat(spine-recover): scan and plan CLI`.

### Task 7: The skill file

**Files:** Create `SKILL.md` (frontmatter in the style of `spine-verify/SKILL.md`: `name: spine-recover`, a trigger-rich `description`, `argument-hint`)

**Interfaces:**
- Consumes: CLI from Task 6; BAssist tools `list-projects`, `get-readiness`, `describe-entity`, `list-records`, `create-record`, `add-comment`.
- Produces: the workflow the model follows.

- [ ] **Step 1: Write the skill** with these sections: purpose and honest limits (inferred, not agreed); binding via `.need-spine.json`; **preflight** (read the project, confirm write access, stop with message otherwise); steps 1 to 7 of the spec in order, using `recover_cli.py scan` then fetching existing records/comments to JSON then `plan`; how to write Given/When/Then from a traced action path and what fields each created record gets (status `draft`, description beginning "Inferred from code:", code refs, `[recover:<id>:<hash>]`); the placeholder block with exact titles and Assumption text from Global Constraints; comment template ("What the code does / Where / Why ranked / Question for the BA" plus `[recover:q:<id>]`); drift and overlap handling; `--dry-run` prints the plan and writes nothing; summary lines, including possibly removed and overlaps; rules never to break (the Global Constraints list); one line suggesting `/spine-pull` and `/spine-verify` next.
- [ ] **Step 2: Review the file against the spec's "How it works" and "Anti-goals" sections** and fix any step that lets the model decide duplicates, ranking or budget itself.
- [ ] **Step 3: Commit** `feat(spine-recover): skill workflow`.

### Task 8: End-to-end acceptance on a scratch BAssist project

**Files:** Modify `docs/README.md` (one line pointing to the skill); add results to the spec's acceptance checklist as notes.

**Needs from the user:** a scratch BAssist project with a write-capable token (the connector currently returns 401).

- [ ] **Step 1: Dry run** against `tests/fixture_shift` with `--dry-run`; verify nothing was written (`list-records` unchanged) and a ranked list printed.
- [ ] **Step 2: Real run** at sensitivity 30; verify placeholders and open Assumptions exist, no record is `agreed`, comments count equals the summary's filed number.
- [ ] **Step 3: Rerun** unchanged; verify zero new records or comments.
- [ ] **Step 4:** Close one filed thread in BAssist, rerun; verify exactly one further question filed and none repeated. Raise sensitivity to 100, rerun; verify the rest are filed.
- [ ] **Step 5:** Run with a read-only token; verify it stops before analysis with the clear message and writes no files.
- [ ] **Step 6: Commit** docs and record the results in the spec's acceptance section.

---

## Self-review notes

- **Spec coverage:** mapping table, placeholders, provenance, sensitivity budget, rerun, drift, overlap, preflight, dry-run, fixture and acceptance each map to Tasks 1 to 8.
- **One clarification of the spec:** the spec's fingerprint includes a "behaviour key" and also stores a drift hash. Here the id uses `kind` + `symbol` (the stable identity, including a rule's name or condition key chosen by the scanner) and the behaviour is hashed separately, so a behaviour change is detected as drift instead of looking like a new item.
