# Brief (the ask, as received)

**Status:** Built (first slice). Implemented: the Brief entity, attachments, lifecycle rules, versioning (`Brief::newVersion()`), the `brief_project` link with scope note, linking from both the brief and the project page, creating a project from an approved brief, a "Briefs" menu leaf per workspace, and a project list filter (`has_brief=yes|no`, `brief_id`). Tests: `tests/Feature/BriefTest.php`.
**Not built yet:** a "New version" button (the model method exists), the coverage view (parts of the ask no project owns), a has-brief column in the project list UI, the `BusinessNeed` "brief asked vs need underneath" field, ERD and data dictionary entries.
**Origin:** Design-thinking discussion (Tim Brown, *Change by Design*) plus a Gemini comparison. Combined plan below keeps only what fits BAssist.
**Principle:** Add a field or practice before adding an entity. The Brief is the only new entity. Design thinking shapes *how* analysis is done; the Spine stays the single record of what was decided.

---

## Why

Work reaches us as a **brief**: an email, a meeting, a document. It is usually solution-shaped ("build us an app for X"). The Spine currently starts at Business Need, which is already an *analysed* statement. The raw ask is recorded nowhere, so the reframing from ask to need is invisible, and "didn't we already look at this?" has no answer.

A Brief is **not** an early Business Need. The brief is what the sponsor gave us; the need is what analysis found underneath. Keeping them separate is what makes the reframing visible and helps in scope disputes.

## Position in the model

```
Workspace
  └── Brief  (the ask, as received)
        ├── no link              → dead end (declined / on hold, with reason)
        ├── link → Project A     → one project
        └── link → Project A, B  → several projects
                    │
                    └── BusinessNeed → BusinessObjective → StakeholderNeed → ... (Spine, unchanged)
```

- The Brief sits **above Project**, owned by the Workspace (Tenant > Workspace > Project already exists).
- A project links up to a brief. The link is **optional** (internal or small projects need no brief).
- Everything inside a project is unchanged.
- The brief **outlives the project**: its stated success measures are checked against reality after delivery (later phase).

## Fields

| Field | Type | Notes |
|-------|------|-------|
| `title` | string | Short name; display field |
| `workspace_id` | FK | Tenant-scoped via `BelongsToTenant` |
| `ask` | text, nullable | The sponsor's words, **as received**. Not rewritten. Required unless an attachment carries the ask |
| `source_type` | string | email / meeting / document / other |
| `source_note` | string, nullable | Who, when, where (link or reference) |
| `stated_constraints` | text, nullable | Budget, time, platform, **only as the sponsor stated** |
| `stated_success_measures` | text, nullable | Only if the sponsor gave any |
| `open_questions` | text, nullable | What we still need to ask |
| `status_id` | FK | See lifecycle |
| `status_reason` | text, nullable | **Required** when declined or on hold |
| `version` | unsigned int | See versioning |
| `received_at` | date | |

What does **not** belong in a brief: SMART objectives, current-state analysis, RACI, solution design. Those are outputs of analysis and live in the existing entities.

## Attachments

Briefs usually arrive with files (the original email, a PDF, a spreadsheet, slides, screenshots), so attachments are a **first-class part of the Brief**, not an afterthought.

- Reuse the existing polymorphic attachment mechanism: `use HasAttachments` plus `#[Attachable]` on the model, as on `BusinessNeed`, `Feature` and `Screen`. No new tables.
- The attachments *are* the source of record. `ask` may be a short paraphrase or a pasted extract, but the original file stays untouched as received.
- **Versioning:** each brief version carries its own attachments. A re-brief with new files makes a new version; earlier versions keep theirs, read-only.
- A brief may have an `ask` with no text at all if the whole ask is in an attachment. So `ask` is **not required** when at least one attachment exists (validation: `ask` or an attachment).
- Attachments are shown on the Brief details page and the "From brief" panel on linked projects.
- Later (optional): note which attachment supports which success measure or constraint. Not in v1.

## Lifecycle

`received → under review → approved | declined | on hold`

- **approved**: accepted as the basis for projects and needs.
- **declined / on hold**: valid end states, kept as history; a reason is required.
- No ghost project is created for a declined brief.

Implementation: reuse the `Status` master and `HasEntityStatus` as other entities do.

## Versioning (lean, decision pending)

The original ask must stay visible even after a re-brief.

- **Lean: new version.** A re-brief creates a new version row (same brief key, `version + 1`); earlier versions are read-only. Gives "what was asked originally and how it changed".
- Alternative: edit in place and rely on `#[Tracked]` history. Simpler, but the original is only visible through history.

## Link to projects: `brief_project`

Many-to-many pivot, not a plain `brief_id` on Project.

| Column | Notes |
|--------|-------|
| `brief_id`, `project_id` | Unique pair |
| `scope_note` | Which part of the ask this project takes |

Why a pivot: records the *split decision*, enables a **coverage view** (parts of the ask no project owns), allows a merged request (one project, two briefs), and avoids a later migration. Cost is small.

Alternative (smaller first step): nullable `brief_id` on `projects`. Loses the scope note and many-to-many.

## Projects with and without a brief

Both ways of starting a project are normal. Nothing about a project depends on having a brief.

| Start | How it happens |
|-------|----------------|
| **From a brief** | Create or pick an approved brief, then create the project from it (pre-fills name and scope note), or link an existing project to it |
| **Without a brief** | Create the project directly, exactly as today. No brief step, no prompt, no warning |

Rules:

1. `brief_project` is the only link, so "no brief" simply means no rows. There is no flag, no placeholder brief and no required field on Project.
2. **Nothing blocks or warns** on a project without a brief: not the readiness gate, not the status dashboard, not the BABOK pack export. The coverage check (rule 4 below) only applies to projects that *have* a brief.
3. A brief can be **linked later**. A project that started without one can be linked to a brief afterwards (for example when the sponsor's original email turns up), with a scope note. The link can also be removed.
4. Project list and filters: add a "Brief" column or filter (has brief / no brief). Informational only.
5. Existing projects stay valid with no migration: all current projects are simply "no brief".
6. A project can be created from any brief that is not **declined** (a declined brief is a dead end). Linking an existing project works for any brief.
7. Menu: each workspace has two sub-folders, **Briefs** (the list plus each brief) and **Projects**. A brief page lists its linked projects, each a link to the project.

## Rules and behaviour

1. A brief's `ask` is never silently overwritten (versioning rule above).
2. A Business Need may record **"what the brief asked" vs "the need underneath"** (field on `BusinessNeed`, not a new entity). A need found later may say it did not come from the brief.
3. Re-brief shows impact: list projects linked to the brief, for Change Request context.
4. Readiness (soft, "encourage, don't police"): a project linked to an approved brief with parts of the ask not covered by any scope note shows a coverage gap. No hard locks.

## Entity conventions to follow

Mirror `StakeholderNeed`: `#[RoutableAttribute]`, `#[Commentable]`, `#[Tracked]`, `#[Attachable]` (the original email or document), `BelongsToTenant`, `HasEntityStatus`, `HasEntityNumber` (prefix e.g. `BR`, to be confirmed against existing prefixes). Add a **Brief** navigation folder, a "From brief" panel on the Project page, and an entry in the data dictionary and ERD.

## Out of scope for this entity (tracked as separate slices)

- Needs fields (context, workaround, pain, evidence level) on `StakeholderNeed`.
- Point-of-view and "how might we" framing on `BusinessNeed` / `BusinessObjective`.
- Design options and prototype validation on `Screen` (see [design-layer.md](design-layer.md)); soft gate: no BDD scenarios for a UI/workflow until its prototype is validated.
- Skill: brief capture (30-minute briefing agenda) in `need-spine`; coverage in `spine-status`.
- Closing the loop: compare stated success measures with outcomes after delivery (P3, Phase 2).

## Open decisions

| # | Decision | Lean |
|---|----------|------|
| 1 | Version the brief, or edit in place? | Version |
| 2 | Pivot with scope note, or plain `brief_id`? | Pivot |
| 3 | Brief required before a project? | Optional |
| 4 | One project from two briefs? | Allow |
| 5 | `HasEntityNumber` prefix and status set | Confirm with existing masters |
| 6 | Owner level: Workspace, or Tenant? | Workspace |

## Caveats

- Tim Brown's definition of a brief (starting framework: constraints, benchmarks, objectives) is from memory of the book; verify wording before quoting it in user-facing docs.
- BABOK does not, to our knowledge, define a brief or treat design thinking as a perspective. Verify before citing task or technique numbers.
