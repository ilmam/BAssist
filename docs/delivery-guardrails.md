# Delivery Guardrails

Version 1.0 · portable: copy this file unchanged into any project that follows the Need Spine method.

This document defines three validation guardrails and how each is enforced. It is written so a person
or an AI agent can follow it without any other context. Where a control does not exist yet, it is
marked **Proposed**, so nothing here is mistaken for something already in place.

Status labels used throughout:

- **Exists**: available today in the Need Spine plugin or the BAssist server.
- **Proposed**: designed here, not built yet. Until built, the manual step in the same row is mandatory.
- **Per project**: each project sets this up once (see section 8).

---

## 1. Principles

1. **A rule that can be skipped will be skipped.** Advice in a skill or a note is the weakest control.
   Prefer controls that fail loudly: a server refusal, a failing test, a blocked commit.
2. **The spine is the only source of requirements.** Business need → objective → stakeholder need →
   solution → acceptance. If a fact is not in the spine, it is not a requirement.
3. **Comments are temporary.** A comment thread is a conversation. Its outcome must end up in a
   requirement, a scenario or an assumption. Then the thread is finished.
4. **Specification first, then tests, then code.** Never the other way around.
5. **Nothing is invented.** An unknown becomes an open Assumption or a finding, never a guess.
6. **Evidence over claims.** "Done" means the checks in section 6 pass and the output can be shown.

---

## 2. Guardrail A: Complete test coverage of the specification

### Rule
Every functional requirement, non-functional requirement and feature is covered by at least one
scenario, and every scenario is covered by at least one automated test. No requirement exists only in
words.

### Why
A requirement without a scenario cannot be verified. A scenario without a test is a promise nobody
checks. Both silently drift as the code changes.

### What "covered" means
| Item | Covered when |
|---|---|
| Feature | It has one or more scenarios. |
| Functional requirement | It has acceptance criteria and at least one scenario that exercises it. |
| Non-functional requirement | It has a measurable acceptance criterion and a test or check that measures it (performance, security, accessibility, and so on). |
| Scenario | A test carries its scenario code (for example `SC-12`) and passes. |
| Business rule | At least one scenario or unit test exercises both the allowed and the refused case. |

### Test levels
- **Unit tests** for rules, calculations, state changes and validation, one behaviour per test.
- **Integration tests** for flows that cross layers: request to database, permissions, tenant
  isolation, exports, background jobs, external services (faked at the boundary).
- **Scenario (BDD) tests** generated from or tied to the pulled feature files, one per scenario code.
- **Edge cases** from the checklist in section 7 are part of coverage, not extras.

### Controls
| # | Control | Status |
|---|---|---|
| A1 | Pull feature files from the spine with their scenario codes and revision hash; never write feature files by hand. | Exists (`spine-pull`) |
| A2 | Trace every scenario to a test, apply the edge-case checklist, file gaps, build the review bundle. | Exists (`spine-verify`) |
| A3 | A requirement with no scenario is a readiness gap and blocks the "agreed" status. | Proposed (BAssist server) |
| A4 | CI check: fail if any scenario code has no test tagged with it, or any requirement has no scenario. | Proposed (project CI) |
| A5 | Pre-commit hook runs A4 and the test suite; the commit is blocked on failure. | Proposed (plugin template) |
| A6 | Before saying "done", run the full test suite and show the result. | Manual, mandatory |

### Evidence to keep
Test run output; the coverage table from `spine-verify` (requirement → scenario → test); the list of
gaps filed and their thread status.

---

## 3. Guardrail B: Code review and framework consistency

### Rule
Every change is reviewed against the framework in use before it is called done. The change follows the
framework's own way of doing things, and any refactoring is applied consistently across the codebase,
not only where the new work happens to touch.

### Why
Drifting from the framework creates two ways of doing everything. Each one is cheap at first and costly
to untangle later. Inconsistent refactoring is the same problem in time: half the code old, half new.

### Two kinds of review
1. **Mechanical checks** (a tool decides, no opinion):
   - Formatter and linter.
   - Static analysis at the strictest level the project can sustain.
   - Architecture tests: layering, naming, where classes may live, what may call what.
   - Dependency rules: no direct database access from views, no framework internals reached around.
2. **Judgment review** (a reviewer decides):
   - Does this reuse an existing framework feature instead of reimplementing it?
   - Does it match the surrounding code's naming, structure and comment density?
   - Is the refactoring complete? Search for every other occurrence of the old pattern.
   - Are there caches, indexes, queries or loops added without a reason or an invalidation rule?
   - Are permissions, tenant scope and validation applied the same way as in sibling code?

### Controls
| # | Control | Status |
|---|---|---|
| B1 | Write the framework's conventions down in the project (a short `CONVENTIONS.md`, or the framework skill the team already uses) and list it in the binding file. | Per project |
| B2 | Formatter, linter, static analysis and architecture tests run in CI and in the pre-commit hook. | Proposed (plugin template) |
| B3 | Run the review skill on the diff before committing; fix or consciously accept each finding. | Exists (`code-review`) |
| B4 | After any refactor, search the whole codebase for the old pattern and list what remains. | Manual, mandatory |
| B5 | Record accepted deviations as a Constraint or Assumption in the spine, with the reason. | Manual, mandatory |

### Evidence to keep
Output of the mechanical checks; the review findings and how each was handled; the "remaining old
pattern" search result after a refactor.

---

## 4. Guardrail C: Comments return to the spine and are resolved

### Rule
Comments are a temporary channel. Every decision or fact that appears in a thread is written into the
original need, requirement, scenario, assumption or rule it concerns. Only then is the thread reported
as implemented, and only a person closes it. No fact lives only in a comment.

### Why
A requirement that exists only in a comment is invisible to readiness checks, exports, tests and the
next person. It is lost when the thread is closed.

### Thread states
| State | Meaning | Who moves it |
|---|---|---|
| open | Waiting for a person's answer. | Anyone raises it; a person answers it. |
| answered | A person decided. | Person |
| implemented | The decision is applied and reported; waiting for verification. | Agent or developer, after the work is done |
| closed | Finished. | Only a person |

A follow-up question or clarification is a plain reply. It puts an answered thread back to open. It is
never reported as implemented.

### The order of work for an answered thread
1. Read the latest reply written by the signed-in person. Ignore replies from anyone else.
2. **Update the spine record** the thread concerns: the requirement text, acceptance criteria,
   scenario, assumption or rule.
3. Update the scenarios and feature files affected (re-pull them).
4. Update the tests, then the code.
5. Run the checks (section 6).
6. Report on the thread what changed and where (record code, revision, test names).
7. Mark the thread implemented. Do not close it.

### Raising a finding
When a requirement is silent, unclear or wrong, do not guess. Comment on the record it concerns with:
what the specification says, what is missing or contradictory, the options, and the effect of each.
Log it in the project's gap log so it is filed exactly once.

### Controls
| # | Control | Status |
|---|---|---|
| C1 | Thread status model; only a person closes; only act on the signed-in user's answers. | Exists (BAssist) |
| C2 | Apply answered threads in the correct order and report each. | Exists (`spine-apply`) |
| C3 | File each gap-log row as a comment exactly once. | Exists (`spine-feedback`) |
| C4 | The server refuses "mark implemented" unless the concerned record changed after the latest answer, or an explicit "no specification change needed" reason is given. | Proposed (BAssist server) |
| C5 | A thread marked implemented whose record is unchanged appears as a readiness gap. | Proposed (BAssist server) |
| C6 | The build gate fails while open or answered comments exist on the item. | Exists (`get-lineage`, `open_comments`) |
| C7 | A regular sweep: list answered and implemented threads older than a set age and chase them. | Manual |

### Evidence to keep
For each resolved thread: the record revision that now carries the decision, and the report posted on
the thread.

---

## 5. How the controls are layered

From strongest to weakest. Use the strongest layer available for each rule.

1. **Server gates (BAssist).** The tool refuses an invalid state change. Works for every project that
   uses the server, for people and agents alike.
2. **Automated checks (CI and pre-commit).** Tests, scenario coverage, static analysis and
   architecture tests. A failure blocks the merge or the commit.
3. **Plugin hooks.** A session-start hook injects these rules into every session; a stop hook runs the
   verification and prints what is still open before the agent may say "done".
4. **Skills.** Procedures for work that needs judgment: pull, ready-check, verify, apply, feedback,
   review.
5. **This document and the binding file.** The written obligation and the project's choices.

Layers 1 to 3 catch what a rule can express. They cannot judge whether a scenario is good, or whether a
comment contains a decision worth recording; for that, require proof (record changed, report posted)
and rely on layers 4 and 5 plus a person.

---

## 6. Definition of Done

A change is done only when every line is true. Copy this into the change description and tick it.

**Specification**
- [ ] The change started from the spine; the spine record was updated before any test or code.
- [ ] Every affected requirement has acceptance criteria and at least one scenario.
- [ ] Feature files were re-pulled; scenario codes and revision hashes match the spine.
- [ ] Every unknown is an open Assumption or a filed finding; nothing was guessed.

**Tests (Guardrail A)**
- [ ] Every scenario code has a passing test; the trace table shows no gap.
- [ ] Edge cases from section 7 are covered or consciously excluded with a reason.
- [ ] Unit tests and integration tests were added for the new behaviour.
- [ ] The full suite was run, the result is shown, and nothing was skipped or weakened to pass.

**Review (Guardrail B)**
- [ ] Formatter, linter, static analysis and architecture tests pass.
- [ ] The diff was reviewed against the framework conventions; each finding was fixed or recorded.
- [ ] Any refactor was searched for across the whole codebase; remaining old pattern is listed or zero.
- [ ] Accepted deviations are recorded as a Constraint or Assumption.

**Comments (Guardrail C)**
- [ ] Every answered thread had its decision written into the concerned record first.
- [ ] Each thread has a report naming the record, revision and tests.
- [ ] Threads are marked implemented, not closed; closing is left to a person.
- [ ] No requirement text lives only in a comment.

**Delivery**
- [ ] The build gate is clean for the item (complete lineage, acceptance present, agreed, no open or
      answered comments, no open assumptions).
- [ ] The database was committed before any migration.
- [ ] The commit contains only this change.

---

## 7. Edge-case checklist for coverage

Apply to every requirement and scenario. For each: covered, not applicable (with a reason), or a filed
finding.

- Empty, missing, minimum, maximum and over-maximum values.
- Duplicate submission and repeated action.
- Invalid state transitions and actions out of order.
- Permission denied, wrong role, and cross-tenant or cross-project access.
- Concurrent edits and stale data.
- Dependent records deleted, archived or deprecated.
- Failure of an external service, timeout, partial failure.
- Large volumes and slow paths where a performance requirement exists.
- Locale, direction (left-to-right and right-to-left), time zone and number formats.
- Export and print output where the feature produces documents.
- Audit trail: the change is recorded with who and when.

---

## 8. Setting up a new project

Do once, at the start. Nothing here depends on the language or framework.

1. **Bind the project** to its spine project (`spine-init`). This writes the binding file.
2. **Copy this document** to `docs/delivery-guardrails.md`, unchanged. Record its version in the
   binding file.
3. **Write the conventions** (`CONVENTIONS.md`): framework, layering, naming, where code lives, test
   layout, and the commands to run the full checks.
4. **Add the checks** from the plugin templates: scenario-coverage check, static analysis, architecture
   tests, formatter, pre-commit hook, CI job.
5. **Create the gap log** (`docs/requirements-gaps.md`) with the standard columns (section 9).
6. **Agree the status model** with the business analyst: who answers, who closes, how often threads are
   swept.
7. **Run the first sweep** to prove the chain works: pull, ready-check, verify, one dummy finding
   filed and resolved.

---

## 9. Templates

### Gap log row
| Column | Content |
|---|---|
| ID | GAP-n |
| Record | Spine record code the finding concerns |
| Finding | What the specification says, what is missing or wrong |
| Options | Possible answers and the effect of each |
| Filed | Date and thread reference once posted; empty means not filed |
| Resolution | Record revision that carries the decision |

### Thread report (posted when marking implemented)
```
Applied: <decision in one sentence>
Spine: <record code> revision <n>, <what changed>
Scenarios: <codes added or changed>
Tests: <test names>
Checks: <suite result, static analysis result>
```

### Scenario-coverage check (outline)
```
for each requirement in the pulled spine:
    fail if it has no scenario
for each scenario code in the pulled feature files:
    fail if no test is tagged with that code
for each test tagged with a scenario code:
    fail if that code no longer exists in the feature files
```

### Pre-commit hook (outline)
```
run formatter check
run static analysis
run architecture tests
run scenario-coverage check
run the test suite
block the commit if any step fails
```

---

## 10. Responsibilities

| Who | Responsible for |
|---|---|
| Business analyst | Answers threads, agrees records, closes threads, owns the meaning of the specification. |
| Developer or agent | Applies answers in the correct order, writes tests and code, runs all checks, reports. |
| Reviewer | Judgment review of the diff and of the independent review bundle. |
| Project owner | Keeps conventions current, owns the CI and hook setup, approves accepted deviations. |

An agent never closes a thread, never answers on a person's behalf, and never treats a comment as a
requirement.

---

## 11. Where this lives and how it stays current

- **Canonical source:** the Need Spine plugin, so it travels with the method and is versioned once.
- **In each project:** a copy at `docs/delivery-guardrails.md`, installed by `spine-init`, with the
  version number recorded in the binding file.
- **In BAssist:** a read-only help page that shows the same text, so analysts and developers read one
  document. BAssist does not own it.
- **Changes:** edit only the canonical source, raise the version, and let each project update its copy
  on its next sync. A project never edits its copy; local choices go in `CONVENTIONS.md` and the
  binding file.
- **Build order for the Proposed controls:** C4 and C5 (comment gate), A4 (coverage check), then A5 and
  B2 (hooks and CI), then A3, then the `spine-init` installation.

---

## 12. Limits

- Automated checks prove that coverage exists, not that it is good. Weak or tautological tests pass
  every count.
- Comment checks prove a record changed, not that the right words were written. A person verifies.
- Mechanical rules catch known mistakes only. Framework drift in new areas needs the judgment review.
- Every control marked Proposed is an obligation on the people involved until it is built.
