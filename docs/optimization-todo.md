# Optimization todo

Ideas parked for later. Nothing here is started.

## Load editor and diagram code on demand

**Problem.** Every page downloads the JavaScript for all features, including the code editor
(about 585 KB) and the four diagram scripts, even on pages that use none of them (home, lists).
The `@vite([...])` list in `resources/views/themes/metronic9/template.blade.php` and
`metronic8/template.blade.php` loads them all.

**Why not just trim the list.** The app uses Livewire page navigation (`data-navigate-once`),
so scripts load once on the first page and are kept. A page that skips the editor script would
break when the user later navigates to a form that needs it.

**Proposed fix.** Keep one small script on every page that watches for editor and diagram
elements (`[data-code-editor]` and the diagram roots). When one appears, load the heavy code
with a dynamic `import()`. It is fetched once and kept for later pages, whether the user
arrives by link or by navigation.

**Cost and care.**
- A short pause the first time an editor opens.
- `resources/js/code-editor.js` also handles the tags inputs on every page, so that part must
  stay in the always-loaded script.
- Needs a manual browser check; the automated tests do not run JavaScript.

**Priority.** Low. Least urgent of the speed items.

## Other parked items

- **Traceability matrix on big projects:** collapsible sections that load their children when
  opened (touches `TraceabilityMatrixService` and the CSV export, which share the data).
- **Loading placeholders:** Livewire lazy loading with skeleton panels for the dashboard,
  matrix and acceptance plan.
- **Splitting large templates** (`projects/export`, `swimlane_flows/.../elements-editor`,
  `traceability/matrix`) into partials. Decided against for now: no speed or visual gain, and
  the export page holds the print layout.
