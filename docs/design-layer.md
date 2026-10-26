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

- **Design** is a project folder in the sidebar (`config/navigation.php`, key `design`), after Requirements Modeling. Its children are **UI Designs** (`Screen`) and **Data Dictionaries** (moved here from Requirements Modeling: a data dictionary is a design of the solution's data). Other design kinds get their own children later.
- `Screen` follows the usual entity conventions: `#[RoutableAttribute]` model, repository, `ScreenData` / `ScreenViewData`, `config/crud.php` and `config/entity_icons.php` entries, role permissions seeded by the migration, a project dashboard count, and `#[Commentable]` / `#[Tracked]`.
- **Edit form** has two collapsible panels (`<x-section>`, remembered per browser): **Meta info** (title, project, requirement link, description, status) and **Elements & preview**. The second is where a design is built, as a tree like the C4 editor: a **panel**, **columns**, **column** or **table** holds its elements, shown indented, with a **+ Add** drop-down under each container for what it can hold (columns take panels or columns, a table takes rows, the rest take anything). The top **Add element** list (one drop-down: elements, then containers), **Insert pattern** and the typing box always add to the **end of the screen**; to add inside a container use the **+ Add** list under it (or type in its rows). Each row has ↑ ↓ (reorder), ← → (out of / into the container), **↔** (same line as the element above), **⋯** (line-group number, own requirement) and ✕. A **Search and filter** fold-out (closed by default, under the tree; search by label, filter by kind) narrows the tree; hidden rows are still saved. Labels are indented by depth.
- **Shortcuts** make it fast:
  - The *typing box* takes typed or pasted text and adds it to the end of the screen (press Enter). Shorthand: `Some text`, `[Save]` (button), `[Save] [Cancel]` (two buttons on one line), `"Enter name"` (input), `Name | "Enter name"` (a text and an input on one line), `[ ] Active`, `() Yes`, `^Branch^`, `--` (rule), `panel Title` / `columns` / `column` / `table` (containers), and **two-space indentation** to put lines inside the container above. Lines starting with `|` are table rows; consecutive ones make one table whose first line is the header.
  - Typing the same shorthand into a **Text** row converts it on Enter.
  - Keys in a row: **Enter** next row (a new table row inside a table, a first child inside a container), **Tab** / **Shift+Tab** move into / out of the container, **Alt+↑/↓** move, **↑/↓** go to the previous / next row, **Backspace** on an empty row deletes it.
  - **Insert pattern** adds ready-made outlines: login form, form with Save / Cancel, search and results table, two columns, list and details side by side.
- **Open in popup** (next to the preview heading, and on the details page) opens the wireframe in the app's shared modal, like the BPD diagram preview, **on the right side by default** (Side mode): zoom (− / % / + / Fit, drag to pan), the size switcher (small, medium, large, fullscreen, side) and the show-page toggle. The wireframe reflows to the panel, so **Fit** (and 100%) fill the panel's width and height; zooming in magnifies it and zooming out gives it more room. Zoom uses CSS `zoom` (a transform keeps the unscaled box and shows scroll bars when zoomed out) and the wireframe's stage is taken out of the panel's own height, so the modal and panel keep their height at every zoom level; scroll bars appear only when the magnified wireframe really is bigger than the panel. In **Side** mode you keep editing beside it and it redraws on every change (and when you edit the Salt text); from the edit form it shows what is in the form right now, unsaved edits included.
- **Live preview** sits beside the tree and is always on: every change is posted to `POST /screens/preview`, which assembles the Salt with `SaltScreenAssembler` (the same code that serves the page and Claude) and returns it for `salt-wireframe.js` to draw. Nothing is saved until **Save**.
- **The Salt text** is a collapsible panel under the preview, **closed by default** (open it to read, edit, copy or apply). It always holds the latest text, and its title shows "(edited, not applied)" if you leave unapplied edits in it. It is shown live: the exact `@startsalt … @endsalt` text that draws the screen and that Claude reads. It can be edited. While you edit it the preview draws from your text and the text box turns amber; **Apply to elements** rebuilds the element tree from the text (the inverse of the assembler), **Reset from elements** throws the edits away, **Copy** copies it. Applying keeps an element's id (and so its history and own requirement) when an element with the same kind and label is still there; anything else is created or removed. Reading is lenient: it understands the subset in the table above (frames, nested groups, `} | {` columns, `{#` tables, the widgets), and the screen's own title line is left to the Title field. A `columns` with a single child comes back as a `column`; it draws the same.
- **Saving** (`ScreenRepository`) replaces the screen's rows from the form in order. A row that already has an id keeps it (so its history survives), blank rows are ignored, and rows left out are removed. A payload with no `elements` key (for example an MCP `update-record` of the title) leaves the rows alone; the form always sends `elements[_present]`, so removing every row removes them all.
- **Details page** (`ScreenController@show`, `pages/screens/details`) is view-only: metadata, the mockup, and the element rows in the same filter bar + table, read-only. Edit goes through the usual Edit action.
- `ScreenElement` is a registered entity (so Claude can list and read rows with the MCP tools) but is not in the menu; people edit rows through the screen's form. `use_modals` is off for `Screen`: its pages are full pages.

## Reference attachment and conversion to Salt

A screen takes **attachments** (the edit form's *Attachments* panel, the same upload as other entities; `png`, `jpg`, `pdf`, `puml`, `salt`, `txt` and more, see `config/attachments.php`). They are a **reference for the whole screen**, usually a screenshot of a wireframe, or an HTML export of it (the best source for exact labels and field types); elements carry no attachments.

The point is that Claude reads the Salt, not the image, every time it works on a design. So the image is converted once:

1. Run the MCP command `/mcp__bassist__screen-from-attachment` (arguments: project, optional screen title), or ask Claude in plain words. A PDF is read as images of its first 4 pages when the server has Imagick, `pdftoppm` or Ghostscript; otherwise only its text layer, so attach a screenshot for a faithful conversion.
2. Claude calls `get-record` (Entity `Screen`; the result lists `attachments`), then `get-attachment` to see the image.
3. Claude writes the screen's elements with `update-record` (`data.elements`, nesting and kinds as in the tables above); rows that keep their id keep their history.
4. Claude reads the screen again and checks that the `salt` matches the screenshot.

After that the Salt is the design and the image stays only as a reference. BAssist does no image recognition itself; the conversion is Claude's, and what it saves is ordinary element rows.

## UI designs pack

The **full export pack** (`projects.export`) also has a **Design** section after the features: each screen with its status, the functional requirements it realizes, its description and its mockup, drawn in the browser from the screen's Salt text by the same renderer (`public/js/salt-wireframe.js`). It is listed in the pack's table of contents and shown to people who may view screens; a project without screens has no Design section. The dedicated UI designs pack below remains the way to get the Salt text itself.

A project's **Downloads** menu (project dashboard) has two entries for the design layer, the same way the full export pack is offered:

- **UI designs pack** (`GET /projects/{project}/ui-designs`): a printable page (Print to PDF) with a cover, a table of contents, and one page per screen: its status, the functional requirements it realizes, its description, the wireframe drawn, and the Salt text.
- **UI designs pack (ZIP)** (`GET /projects/{project}/ui-designs/download`): `README.md`, `index.md` (screens, status, realized requirements, the Salt of each), `screens/NN-name.puml` (one PlantUML Salt file per screen, the text of the design), and `wireframes.html`, a self-contained viewer (renderer and styles inlined) that opens offline.

Both need view permission on Project and Screen and respect the tenant. Built by `UiDesignPackService`. Only the screens of that project are included; an empty project gets a pack that says so.

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
