# Navigation without full reloads, and the appearance switch

**When do I care?** When a menu link or page behaves oddly after clicking around, when you add page scripts, or when you change the layout template.

## Menu clicks swap the page (`wire:navigate`)

Menu links carry `wire:navigate` (Livewire). Clicking one fetches the next page in the background and swaps the content; the browser does not reload, so the layout, fonts and scripts are not loaded again.

| Piece | Where |
|---|---|
| Menu links | `themes/metronic9/partials/menu-items.blade.php` (`wire:navigate` on every link) |
| Sidebar rendered once and kept | `template.blade.php`: `@persist('sidebar')`. On a navigate request (`X-Livewire-Navigate` header) the server does not build it at all |
| Modal host, help drawer, command palette kept | `@persist('dialogs')`, `@persist('palette')` |
| Active item and open folders | `resources/js/navigation.js` (`bassistSyncSidebar`) after each navigation |
| Metronic components re-initialised on the new content | `livewire:navigated` listener at the end of `template.blade.php` |

### Rules for page scripts

The body is replaced on every navigation, so **scripts in the body run again**:

- **No top-level `const` / `let` / `function` in an inline page script**: it throws "already declared" on the second visit. Wrap it in an IIFE.
- **Do not wait for `DOMContentLoaded`**: after the first page it never fires again. Use `if (document.readyState === 'loading') { addEventListener('DOMContentLoaded', boot) } else { boot() }` (the datatable does).
- **Run-once scripts** (libraries, the layout's inline script, Vite modules) carry `data-navigate-once`. Vite tags get it from `AppServiceProvider` (`Vite::useScriptTagAttributes`).
- Anything that keeps a reference to an element outside `@persist` goes stale; look the element up when you need it.
- Per-page scripts belong in `@push('scripts')` (they run each visit, which is what a page wants).

Only menu links use `wire:navigate`. Other links (a row's edit link, a form's Cancel) are normal links; add `wire:navigate` to a link when you want it to swap too.

A page that does not use the main layout (login, print) is reached with a normal link. If a navigation arrives without a sidebar, the page reloads once.

## BAssist: why the menu is fast

The workspace → project → folder tree used to be rebuilt on every page (128 nodes, about 150 KB of HTML, roughly 280 ms of PHP). Now:

| Change | Where | Effect |
|---|---|---|
| Light tree cached per user | `NavTreeBuilder::build()` (`Cache::remember`, 10 min) | key includes the user, their role's permissions and the count / last change of workspaces and projects, so a permission change, a new or renamed project shows up at once |
| Only the **active project's** folders are in the page | `NavTreeBuilder::withActiveContext()` | the other projects carry `lazy_url` (about 110 menu nodes instead of 810) |
| Other projects load when first opened | `NavProjectController` (`GET /navigation/projects/{project}`, route `nav.project`), `navigation.js` | one small request per project, cached too; tenant scope applies (a foreign project is a 404) |
| Full pages skip the sidebar on navigate | `template.blade.php` | a `wire:navigate` request never builds the menu |
| Active link follows the URL and project | `navigation.js` | a link with `project_id` only matches a page of the same project |

Lazy projects are used with the Metronic 9 theme only (Metronic 8 renders the whole tree).

### Pages that keep a full reload

Diagram editors and some pages bind their script once at load, so a swapped-in page would not be wired up. Their route-name prefixes are listed in `config/navigation.php` → `full_reload` (architectures, data dictionaries, state flows, swimlane flows, screens, traceability, diagrams, project export / UI designs / BABOK documents). `nav_item_swaps()` decides per link. To move such a page to swapping, make its script bind on `livewire:navigated` (or on `bassist:modal-loaded`, as the state-flow editor does) and remove its prefix from the list.

## Appearance (light / dark / system)

The theme already supports dark mode. The user menu (top right) has an **Appearance** row with Light / Dark / System. The choice is kept in the browser (`localStorage` key `kt-theme`), applied before paint, and "System" follows the operating system. Code: `resources/js/theme-mode.js`, markup in `themes/metronic9/partials/topbar.blade.php`.

## Tests

`tests/Feature/NavigationTest.php` (cache and invalidation, lazy projects, the endpoint and its tenant scope, which links swap, sidebar skipped on navigate).
