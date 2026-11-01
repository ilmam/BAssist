---
name: spine-verify
description: "Use after tests and code are written for a feature, before calling it done, or when the user says \"verify\", \"check the tests cover the spec\", \"third eye\", \"build the review bundle\", \"are the edge cases covered\", or runs /spine-verify. Traces every BAssist scenario to a test, applies the edge-case checklist, files gaps, and produces a bundle another AI agent can independently review."
argument-hint: "[feature code, or 'all']"
---

# Verify tests against the spec (closes the loop)

`/spine-pull` brings the spec down, `/spine-ready` gates the start, `/spine-feedback` and `/spine-apply` carry questions and answers. This skill is the check in the middle: does the code project actually prove what BAssist says, and what did the spec forget?

Never "done" while a scenario is untraced or a gap is unfiled.

## Steps

1. **Fresh export.** Call `get-gherkin` for the project (or feature) and save the JSON as-is to a scratch file (for example `spine-verify-report/export.json`). Run `/spine-pull` first if the feature files are missing or stale.
2. **Trace.** Run:
   ```
   python .claude/skills/spine-verify/trace_check.py --export spine-verify-report/export.json --features <features_dir> --tests <test dir> [--run-summary <test output file>]
   ```
   It blocks on: scenario not pulled, stale `@rev` (spec changed since the test was written), scenario with no test mentioning its `SC-n`, orphan or duplicate tags, untagged scenarios, open gaps not yet filed. A test must carry its scenario code (a tag, trait, attribute or comment such as `// SC-12`).
3. **Edge-case pass (judgement, not the script).** For each rule in the feature, walk: boundaries; null or empty input; duplicates and idempotency; permissions and data-level access; illegal state transitions; concurrency; time and time zones; failure of a dependency. A case the scenarios do not cover is a gap. Do not choose the behaviour: add a row to `docs/requirements-gaps.md` (next free `G-nn`, `Filed` empty) with what the app does today and a suggested default. Also list behaviour in the code that no scenario or business rule describes.
4. **File.** Run `/spine-feedback`. It posts each unfiled row once.
5. **Fix what the script blocked.** Missing tests: write them. Stale spec: re-pull, then update tests. Never edit scenarios locally.
6. **Re-run** step 2 until verdict PASS. Then write the bundle (below) if a second reviewer is wanted.

## Third-eye review bundle

`spine-verify-report/report.md` and `report.json` are produced by every run. `report.md` is self-contained: verdict, findings, a scenario-to-test table with spec and local revision hashes, the gap log, optional test output, a report id (hash), and instructions for the reviewing agent. Send `report.md` plus the test and source files it names.

The reviewing agent must be independent: a different model or a fresh session with no access to this conversation. Give it read-only access, no BAssist write tools. It returns findings as gap-log rows with evidence (file and line) and `verified` / `unverified`. Bring those rows into `docs/requirements-gaps.md` yourself, then `/spine-feedback`. A reviewer's finding is a question for the analyst, never a spec change.

Strip secrets, tokens and customer data from anything you send out.

## Honest limits

The script proves a test mentions a scenario, not that it passes or asserts the right thing. The reviewer, and the test run output, cover that. Edge-case completeness is a judgement and can never be proven.
