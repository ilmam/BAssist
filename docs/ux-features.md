# UX features: home, readiness, lineage, traceability, power tools

**When do I care?** When you change or extend the screens added on top of the CRUD layer: the home page, the readiness panel, badges / chips / empty states, the lineage rail, the traceability graph, or the list power tools. Comments, approvals and history have their own page: [collaboration.md](collaboration.md).

Everything here is **application** code (not the reusable CRUD framework). View conventions still follow [ui-views.md](ui-views.md): no theme classes in `pages/`, shared look in components + `public/themes/metronic9/assets/css/bassist.css` (all new classes are prefixed `ba-`).

## Map

| Feature | Entry point | Data / logic | View(s) | JS |
|---------|-------------|--------------|---------|----|
| Home ("My work") | `GET /` → `HomeController@index` (route `home`) | `ProjectReadinessService`, `ApprovalService::awaitingReview()`, `CommentService::mentionsFor()` | `pages/home.blade.php`, `pages/partials/home-item-row.blade.php` | — |
| Readiness by BABOK folder | `ProjectDashboardController@show` | `ProjectReadinessService::forProject()` | `pages/projects/dashboard.blade.php` | — |
| Lineage rail + next step | every details page / view modal | `SpineCascadeService::for()` → `['lineage']` | `pages/partials/spine-cascade.blade.php` → `pages/partials/lineage.blade.php` | — |
| Traceability coverage + graph | `TraceabilityController@index` (`?view=graph`) | `TraceabilityGraphService` | `pages/traceability/matrix.blade.php`, `partials/graph.blade.php` | `traceability-graph.js` |
| Change-request impact | CR details / view modal | `ChangeRequestAffectedService::cascadeFor()` | `pages/change_requests/partials/cascade.blade.php` | — |
| Ctrl+K palette | `GET /quick-search` | `QuickActionsController@search` | `partials/command-palette.blade.php` | `command-palette.js` |
| Inline / bulk status & priority | `PATCH /quick-update/{model}/{id}`, `POST /quick-update/{model}` | `QuickActionsController` | `themes/metronic9/components/datatable.blade.php` | `list-power.js` |
| Saved list views | — (browser `localStorage`) | — | `pages/generic/list.blade.php` | `list-power.js` |

## UI system

One colour language for status, priority and risk levels.

- **Tones** live in `ui_status_tones()` (`app/helpers.php`): normalised code → `success | warning | danger | info | neutral`. `ui_status_tone($value)` resolves a name or code ("Need Revision", `under_review`, "Won't"). The datatable prints the same map into JS, so PHP and list badges never drift. **Add a new status or priority code here** and it is coloured everywhere.
- **Components** (theme-neutral, `resources/views/components/`):

| Component | Use |
|-----------|-----|
| `<x-status-badge :value="$name" />` or `tone="danger"` with a slot | Any status / priority / severity label |
| `<x-code-chip :code="…" :href="…" :modal="…" />` | Entity codes (FR-12). With `modal` it opens the shared modal |
| `<x-empty-state icon title hint compact>` + `<x-slot:actions>` | Empty lists and "nothing here" panels |
| `<x-progress-ring :pct="72" size="56" />` | Readiness / coverage rings (`null` = not started) |

- **Lists:** `pages/generic/list.blade.php` builds the entity empty state (hint = first paragraph of `resources/help/{resource}.md` via `HelpRegistry::summaryForModel()`) and passes it as `$options['emptyStateHtml']`. Columns whose data field ends in `status`, `priority`, `impact`, `likelihood`, `response` or `severity` (optionally `.name` / `.code`) render as badges automatically; the `code` column renders as a chip.
- **Precompiled CSS caveat:** Metronic's `styles.css` is prebuilt, so arbitrary Tailwind utilities (e.g. `xl:grid-cols-2`, some `mb-*`) may not exist. For new layouts add a `ba-*` class to `bassist.css` instead of relying on an unused utility.
- **Metronic 8:** the `ba-*` styles and the datatable changes are in the Metronic 9 theme only.

## Readiness

`ProjectReadinessService::forProject()` returns `items` (gaps with `count > 0`), `severity`, `spine`, `score` and `folders`.

- Each check is tagged with a BABOK folder through `FOLDER_FOR_CHECK` (keys match `config/navigation.php` → `hierarchy.project_folders[*].key`). **A new check needs an entry there**, otherwise it lands in `other`.
- `CREATE_FIX_FOR_CHECK` maps "nothing captured yet" checks to the entity whose create modal fixes them; the dashboard then shows **Add** instead of **Review**.
- Folder health = passing checks ÷ applicable checks. The headline `score` is still spine coverage (unchanged).
- The home page calls `forProject()` for the 6 most recently updated projects on every load (`HomeController::PROJECT_LIMIT`). Cache there if it becomes slow.

## Lineage rail

`SpineCascadeService::for()` still returns `parents`, `gaps`, `groups`, and now also `lineage`:

```php
['steps' => [...5 levels...], 'complete' => int, 'total' => int,
 'next' => ['title', 'why', 'action' => ['label', 'url']] | null,
 'others' => [...], 'quick' => [...], 'also' => [...]]
```

- Levels are fixed in `LEVELS` (1 Business Need → 5 Acceptance). Step `state` is one of `done`, `current`, `missing`, `blocked` (a higher parent that can only follow once the nearer link exists), `optional`, `later`.
- The **next step** is the first actionable gap. Its "why" text comes from `GAP_REASONS` → `ui.lineage_why_*`. **A new gap key needs a reason string** or the card shows no explanation.
- Gaps added by the lineage layer (not in the original cascade): `no_acceptance` (FR/NFR without acceptance criteria) and `no_packaging` (stakeholder need with no FR/NFR/feature).
- Quick actions use `createUrl()` with prefill query keys. Only the keys whitelisted in `BaseController` (`stakeholder_need_id`, `feature_id`, `change_request_id`, …) are applied.
- The shared partial renders the rail on full pages and the compact chain when `inModal` is true, so no per-entity Blade changes are needed.

## Traceability

- `TraceabilityGraphService::coverage($rows)` → share of matrix rows with each spine level filled. `graph($rows)` → Mermaid `flowchart LR` text plus a `links` map (node id → view-modal URL).
- Labels are escaped for Mermaid (`#quot;`, `#lt;` …) in `label()`; keep new node text going through it.
- More than `MAX_NODES` (180) nodes → the view asks for a project filter instead of drawing.
- `traceability-graph.js` renders with `securityLevel: 'strict'` and adds `data-modal-url` to nodes; the theme's delegated modal handler does the rest.

## Power tools

- **Search** (`QuickActionsController::SEARCHABLE`): model → `[class, code prefix, title column]`. A query like `FR-12` is parsed as prefix + number; anything else is a `LIKE` on the title. **Add an entity here** to make it searchable. Results respect `entity_can(view)` and tenant scope.
- **Quick update:** only fields in `QUICK_FIELDS` (`status_id`, `priority_id`) and only if fillable on the model. Authorises `EntityAccess::UPDATE`. It updates the single column on the model (not through the repository) so payload normalisers such as `SolutionPackagingParent::normalize()` cannot clear parent links. Bulk is capped at 500 ids and updates per record so model events fire.
- **Datatable:** the list page sets `$options['quickEdit'] = QuickActionsController::optionsFor($model)` when the user may update. That turns on the checkbox column, editable badges and the bulk bar; `list-power.js` wires them after the `ba:datatable-ready` event.
- **Palette commands** are built server-side in `partials/command-palette.blade.php` (pages + "New …" for creatable entities).

## Assets and caches

- New Vite entries: `traceability-graph.js`, `command-palette.js`, `list-power.js`, `comments.js`, `review.js` (see `vite.config.js`). After pulling, run `npm run build` (or `npm run dev`); a stale `public/build/manifest.json` gives *Unable to locate file in Vite manifest*.
- If a changed Blade file does not show up (seen on OneDrive-synced folders), run `php artisan view:clear`.
- New tables need `php artisan migrate`.

## Tests

`tests/Feature/HomeAndReadinessUiTest.php`, `LineageTest.php`, `TraceabilityViewsTest.php`, `QuickActionsTest.php` (plus `CommentsTest.php` and `ReviewAndHistoryTest.php` for collaboration).
