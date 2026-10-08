# Design layer (screens as design, drawn from Salt)

**Status: proof of concept.** Application feature (BAssist-specific). Decisions below are settled; the "Deferred" list is deliberately open.

## Why

A screen is not a requirement. Requirements say **what is needed**; a design says **how it is realized**. BABOK keeps these apart (requirements analysis vs. design definition), so BAssist gives designs their own layer: a **sibling** to requirements, not a child, with its own identity and lifecycle.

Screens are the first kind of design. The same layer can later hold data structures, report layouts and so on.

## Notation and rendering

- **Notation: PlantUML Salt.** One text source is both what Claude reads and what renders as a low-fidelity mockup, so the two cannot drift. It sits in the PlantUML/C4 family BAssist already uses (see Architecture).
- **Rendering: client-side**, by `public/js/salt-wireframe.js`, a small renderer for the Salt subset the assembler emits. Laravel stores and serves the Salt text; the browser draws the mockup. No server-side renderer and no Java.
- **Why not PlantUML's own browser build?** Tested in a browser against `@plantuml/core` 1.2026.8 (TeaVM): it does not include Salt. `@startsalt` gives "Diagram not supported by this release of PlantUML"; `@startuml` + `salt` gives a syntax error. So the Salt text stays standard PlantUML (it renders unchanged in full PlantUML) and BAssist draws the subset itself.
- **Supported subset:** `{+` frame, nested `{ }` groups, `} | {` side by side, `{#` table, `<b>` title, `--` rules, ` | ` columns, `[button]`, `[ ] checkbox`, `() radio`, `^select^`, `"input"`, plain labels. Anything else is drawn as a label. Nested groups, `} | {` side-by-side joins and `{#` tables are supported. Fallbacks if this is not enough: run the real PlantUML jar in the browser with CheerpJ, or render on a server.

## Model

```
FunctionalRequirement ◄──── realizes ──── Screen (design)
                                            │ composed of (no traceability)
                                            ▼
                                       ScreenElement (design, one addressable row)
                                            ┆ optional own upstream link
                                            ▼
                                       FunctionalRequirement
```

| Entity | Role | Table |
|--------|------|-------|
| `Screen` | Design. Carries the upstream link to the functional requirement(s) it realizes. | `screens` |
| `ScreenElement` | Design, finer grain. One line per element, same line-by-line grain as the Mermaid diagrams. Belongs to its screen by composition only; a **container** element (panel, columns, column, table) holds other elements through `parent_id`. | `screen_elements` |
| link table | Screen ⇄ FunctionalRequirement, many-to-many (a design may realize several requirements). | `screen_functional_requirement` |

### Linking rules

1. **Designs only link upstream.** A screen points at the functional requirement(s) it realizes. Nothing points down from a requirement to a design column, and nothing points down from an element.
2. **Elements carry no traceability by default.** They belong to the screen by composition (`screen_id`).
3. **Exception:** an element that genuinely maps to its own requirement (for example a field tied to a validation rule) may carry its own `functional_requirement_id`.
4. A screen is a *realization*, not a spine level: it does not count toward the Need Spine readiness/lineage gates in the PoC. Whether "a functional requirement with no realizing design" should become a readiness gap is a later decision.

## Assembly

Element rows are composed into Salt **at render time** by `App\Services\SaltScreenAssembler` (same role as `SwimlaneMermaidGenerator` for swimlanes). Rows are the source of truth; the Salt text is never stored.



Ordering and position come from a simple hint for now:

- `position` — integer order within the screen.
- `row` *(optional hint)* — elements sharing the same `row` value are placed on one line, left to right.

`ScreenElement.kind` maps to Salt:

| kind | Salt | Notes |
|------|------|-------|
| `label` | `text` | |
| `input` | `"text        "` | |
| `button` | `[text]` | |
| `checkbox` | `[ ] text` | |
| `radio` | `() text` | |
| `select` | `^text^` | |
| `separator` | `--` | rule line, no label needed |
| `panel` | `{+ <b>title -- ... }` | **container**: a framed box titled by its label |
| `columns` | `{ {+ ...} \| {+ ...} }` | **container**: its children side by side, one column each (a leaf child becomes a column of its own) |
| `column` | `{ ... }` | **container**: an unframed group of stacked elements |
| `table` | `{# ... }` | **container**: a grid |
| `tablerow` | `a \| b \| c` | a line of a table; cells are split on `\|`; the first `tablerow` is the header (bold) |

### Nesting

A row sits inside a container by pointing at it (**Inside** in the form, `parent_id` in the database). Rules the form and the server both enforce: a parent must be a container and must come **earlier** in the row order (so there are no loops and no forward references; anything else falls back to the top level), and deleting a container deletes what it holds. Order inside a container is the global row order filtered to its children. The `row` hint groups only siblings.

For example, two panels side by side above a table:

```
Columns
  Panel "Auction"        (Inside: Columns)
    Label "Time left"    (Inside: Panel "Auction")
  Panel "Raise the bid"  (Inside: Columns)
    Button "Place bid"   (Inside: Panel "Raise the bid")
Table
  Table row "Bidder | Amount"
  Table row "You | 12,400,000"
```

Proper layout metadata (grids, groups, tabs, trees) comes later.

## Menu and screens in the UI

- **Design** is a project folder in the sidebar (`config/navigation.php`, key `design`), after Requirements Modeling. Its first child is **UI Designs** (`Screen`). Other design kinds get their own children later.
- `Screen` follows the usual entity conventions: `#[RoutableAttribute]` model, repository, `ScreenData` / `ScreenViewData`, `config/crud.php` and `config/entity_icons.php` entries, role permissions seeded by the migration, a project dashboard count, and `#[Commentable]` / `#[Tracked]`.
- **Edit form** is where a design is built. Besides the usual fields (the *Functional requirement* select under *Traceability* adds to the screen's realized set) it has the element rows as a list: a filter bar (search by label, filter by kind) over a table with Row, Kind, **Inside**, Label and an optional own requirement (labels are indented by depth), plus add, move up / down and delete. The row order is the Salt order. Filtering only hides rows in the table; hidden rows are still saved.
- **Live preview** sits beside the table and is always on: every change is posted to `POST /screens/preview`, which assembles the Salt with `SaltScreenAssembler` (the same code that serves the page and Claude) and returns it for `salt-wireframe.js` to draw. Nothing is saved until **Save**.
- **Saving** (`ScreenRepository`) replaces the screen's rows from the form in order. A row that already has an id keeps it (so its history survives), blank rows are ignored, and rows left out are removed. A payload with no `elements` key (for example an MCP `update-record` of the title) leaves the rows alone; the form always sends `elements[_present]`, so removing every row removes them all.
- **Details page** (`ScreenController@show`, `pages/screens/details`) is view-only: metadata, the mockup, and the element rows in the same filter bar + table, read-only. Edit goes through the usual Edit action.
- `ScreenElement` is a registered entity (so Claude can list and read rows with the MCP tools) but is not in the menu; people edit rows through the screen's form. `use_modals` is off for `Screen`: its pages are full pages.

## Page and API

- `GET /screens/{screen}/mockup` — Blade page that receives the assembled Salt and draws it client-side.
- The **Screen** view DTO exposes the assembled `salt` text, so the MCP `get-record` tool returns exactly what the browser draws. Element rows are also readable with `list-records` (`entity: ScreenElement`, filter `screen_id`).

## PoC slice

1. `Screen` + `ScreenElement` with minimal fields, hand-filled (seeders) for one UCAuction screen (`UcAuctionBiddingScreenSeeder`) and one demo-project screen ("My inquiries", in `DemoProjectSeeder`).
2. `SaltScreenAssembler`: rows → Salt.
3. Laravel page that renders the Salt client-side.
4. Check that Claude reads it back correctly through the plugin (`get-record` on the screen, `list-records` on its elements).

## Deferred

- Layout metadata (replaces the `row`/`position` hint).
- Raw rows vs. assembled Salt for Claude: a plugin setting. The PoC always returns both the rows (via `list-records`) and the assembled Salt (on the screen).
- Final schema: the PoC fields are minimal and may change; do not build on them yet.
- Readiness/lineage treatment of designs; other design kinds (data structures, report layouts).
- Fuller Salt coverage (grids, tabs, trees, groups) in the renderer, or a different renderer.
