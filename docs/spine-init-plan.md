# Plan: `spine-init` installs the delivery guardrails

Status: built in the need-spine plugin 0.4.0 (`templates/guardrails/`, skill `/spine-guardrails`, session
reminder, 32 scratch-project checks in `test-install.sh`). Changes from this plan:

- It is a separate skill, `/spine-guardrails`, that `/spine-init` offers as its last step. It can be run at
  any stage; a project already under way gets a frozen baseline (`.spine-coverage-baseline`), so only new
  gaps block.
- The plugin is the canonical source of every installed file. The copies in this repository are installed
  copies; do not edit them here.
- The record of version and checksums is `.spine-guardrails`, not a field in the binding file.
- No stop hook; the session reminder says whether the guardrails are missing or out of date.
- Not done: the plugin was edited where this machine keeps it (the app-managed folder), so the changes must
  be copied to wherever the plugin is published from, or they are lost on the next sync.

Original plan follows. Version 0.1.

## 1. Goal

When a new project is bound to BAssist with `/spine-init`, it also gets the delivery guardrails
(`docs/delivery-guardrails.md`): the document, the checks, the hook, the CI job, the gap log and the
session reminders. After that, nobody has to remember to copy anything, and an existing project can be
brought up to date with one command.

## 2. What exists today (verified in this environment)

| Piece | Where | State |
|---|---|---|
| Guardrails document | this repo, `docs/delivery-guardrails.md` | Committed, version 1.0 |
| Check runner, pre-commit hook, installer, CI templates | this repo, `scripts/` | Committed, tested |
| Offline scenario-coverage check | this repo, `scripts/spine-coverage-check.py` | Committed, tested |
| BAssist gates (C4, C5, A3) | this repo (the BAssist server) | Committed, tested |
| `spine-verify` skill with `trace_check.py` | this repo, `.claude/skills/spine-verify/` | **Untracked**, not committed anywhere |
| Plugin copy on this machine | `~/.claude/plugins/synced/.../need-spine` | Version 0.2.0 with six skills; it has **no** `spine-init`, `spine-apply` or `spine-verify` |
| Source of the live `spine-init` | not found | The skill list in the session shows `spine-init`, `spine-apply`, `spine-verify`, so a newer source exists elsewhere |

Two consequences:

1. **The plugin source must be located before any of this is built.** The plugin folder on this machine
   is an older synced copy; editing it would be overwritten and would not be the real source.
2. **I built an overlapping check without knowing about `trace_check.py`.** Both match scenarios to
   tests. They are not identical, so the plan keeps both with clear roles (section 6).

## 3. What `spine-init` installs

All of it comes from one folder inside the plugin, `templates/guardrails/`, so there is a single source:

| Installed into the project | Source | Behaviour on re-run |
|---|---|---|
| `docs/delivery-guardrails.md` | plugin template | Updated only if the project copy is unchanged |
| `scripts/spine-checks.sh`, `scripts/hooks/pre-commit`, `scripts/install-hooks.sh` | plugin template | Same |
| `scripts/spine-coverage-check.py` | plugin template | Same |
| `.spine-checks` | preset for the detected stack | Never overwritten; it is the project's own |
| `scripts/ci/<system>.yml` | the chosen CI system only | Created if missing |
| `docs/requirements-gaps.md` | empty log with the standard columns | Never overwritten |
| `.gitattributes` lines (`*.sh text eol=lf`, `scripts/hooks/* text eol=lf`) | snippet | Appended if missing |
| `.github/pull_request_template.md` with the Definition of Done | plugin template | Created if missing |
| Binding file entry `guardrails: <version> <checksum>` | `spine-init` | Rewritten on update |

## 4. How `spine-init` behaves

1. **Detect the stack** from the files present: `composer.json` (PHP/Laravel), `*.csproj` (.NET),
   `package.json` (Node), `pyproject.toml` (Python). Pick the matching `.spine-checks` preset; if nothing
   matches, write a file with only comments and tell the user to fill it in.
2. **Dry run first.** List every file it would create or change and ask for a yes. Nothing is written
   before that.
3. **Never overwrite local edits.** A file is replaced only if its checksum still matches the version
   recorded at install time. Otherwise show a diff and leave it.
4. **Hook installation is opt-in.** Installing changes how every commit behaves, so ask, then run
   `sh scripts/install-hooks.sh`.
5. **Ask which CI system** (GitHub Actions, Azure Pipelines, none) and copy only that template.
6. **Prove the chain once.** Run `sh scripts/spine-checks.sh fast`, show the result, and file nothing else.
7. **Idempotent.** A second run changes nothing; `--update` brings unchanged copies to the newest
   plugin version and reports the files it skipped.

## 5. Stack presets for `.spine-checks`

| Stack | `fast` | `full` |
|---|---|---|
| PHP / Laravel | `pint --test`, static analysis, architecture tests, coverage check | `php artisan test` |
| .NET (Shift) | `dotnet format --verify-no-changes`, coverage check | `dotnet test` |
| Node | linter, type check, coverage check | `npm test` |
| Python | `ruff`, coverage check | `pytest` |

Each preset is a starting point. The formatter line is commented out when the existing code is not clean
(this repo's case), so the first commit is not blocked by old formatting.

## 6. Two coverage checks, two roles

| | `spine-coverage-check.py` (A4, offline) | `trace_check.py` (from `spine-verify`) |
|---|---|---|
| Needs BAssist access | No, reads the pulled `.feature` files only | Yes, needs a fresh `get-gherkin` export |
| Runs in | Pre-commit and CI | Locally, on demand, before "done" |
| Catches | Scenario without a test, test naming a missing scenario, hand-written scenario, duplicate code | The same, plus stale `@rev` (spec changed since the test was written), unfiled gaps, and the review bundle |

CI must not need a BAssist token, so the offline check stays the CI gate; `spine-verify` stays the
deeper local check. Open question for the owner: keep both, or make `trace_check.py` also work offline
and retire the smaller script (section 9, decision 3).

## 7. Session behaviour (plugin hooks)

- **Session start hook.** If the binding file exists, print five lines: the three guardrails, the
  Definition of Done location, and a warning if the recorded guardrails version is older than the plugin's.
  If the file is missing, suggest `/spine-init`.
- **Stop hook (optional).** Before the agent ends a turn that changed files, run
  `sh scripts/spine-checks.sh fast` and show what is open. This is the strongest nudge but also the
  noisiest; see decision 2.

## 8. Testing `spine-init` itself

- Scratch repositories for each stack: a fresh install produces exactly the listed files.
- A second run changes nothing.
- A locally edited template is skipped on `--update` and reported.
- The dry run writes nothing.
- The hook installed by `install-hooks.sh` blocks a failing commit and allows a passing one (already
  proven for the runner in this repo).
- A project with no matching stack gets a commented `.spine-checks` and a clear message, not a silent pass.

## 9. Decisions needed from you

1. **Where is the real plugin source?** A path or repository. Nothing in section 3 can be built until
   this is known.
2. **Stop hook:** on by default, off by default, or not at all?
3. **Coverage checks:** keep both (recommended), or merge them into one that works offline?
4. **Hook installation:** ask each time (recommended), or install automatically?
5. **Where the project copy of the document lives:** `docs/delivery-guardrails.md` (as now) or a hidden
   `.spine/` folder.
6. **`spine-verify`:** commit it to this repository, or move it into the plugin where every project gets it?

## 10. Build order

1. Locate the plugin source (decision 1), and decide 3 and 6.
2. Move the templates into `templates/guardrails/` in the plugin and add the version header and checksums.
3. Extend `spine-init`: detect, dry run, install, record in the binding file.
4. Add `--update` and the skip-if-edited rule, with the scratch-repo tests.
5. Add the session start hook; add the stop hook if chosen.
6. Update the guardrails document to version 1.1: its section 8 (setting up a new project) and section 11
   (where it lives) describe the automatic install instead of the manual steps.

## 11. Risks

- **Template drift.** Mitigated by one source folder, a version, and checksum-based updates.
- **Blocking old code.** A formatter or static-analysis check that the existing code fails would block every
  commit; presets comment such lines out until the code is clean.
- **Hook bypass.** `git commit --no-verify` skips the hook; the CI full tier is the backstop and must be
  required for merging.
- **Windows line endings.** Shell scripts break with CRLF; the `.gitattributes` lines are part of the install.
- **Overwriting someone's work.** Only unchanged files are replaced, and every change is listed first.
