# UI Views — Conventions & Architecture

This document describes how views, themes, pages, modals, and components are organized in this project. Follow these conventions when adding models, customizing screens, or supporting additional themes.

## Design principles

1. **Write business screens once.** Pages describe *what* to show (list, form, details). Themes describe *how* it looks.
2. **Theme-neutral pages.** CRUD pages live under `resources/views/pages/` and must not contain Metronic-specific CSS classes or markup.
3. **Theme-specific components.** Visual differences between Metronic 8 and 9 are handled in `resources/views/themes/{theme}/`.
4. **Convention over configuration.** Drop a blade file in the expected path to override a generic view. Config is only needed for non-standard paths.
5. **Generic CRUD by default.** New routable models get list / form / details / modals automatically. Custom blades are optional.

---

## Architecture layers

```
┌─────────────────────────────────────────────────────────────┐
│  THEME SHELL (master layout, nav, footer, assets, modal host) │
│  themes/{theme}/template.blade.php, partials/, layout/       │
├─────────────────────────────────────────────────────────────┤
│  NEUTRAL PAGES (business content — write once)                │
│  pages/generic/*, pages/modals/*, pages/{resource}/*        │
├─────────────────────────────────────────────────────────────┤
│  THEME COMPONENTS (visual building blocks — per theme)        │
│  themes/{theme}/components/*, controls/form/*                 │
└─────────────────────────────────────────────────────────────┘
```

### Request flow (full page)

```
GET /categories
  → CrudController@index
  → model_page_view('Category', 'list')
  → pages/generic/list.blade.php   (or pages/categories/list if override exists)
  → @extends(ui_layout())          → themes/metronic9/template.blade.php
  → <x-datatable>                    → themes/metronic9/components/datatable.blade.php
```

### Request flow (modal fragment)

```
GET /categories/modal/5/edit  (with X-Modal-Request: 1)
  → CrudController@modalEdit
  → model_modal_view('Category', 'form')
  → pages/modals/form.blade.php
  → <x-modal-content>              → themes/metronic9/components/modal-content.blade.php
  → <x-form :inModal="true">       → themes/metronic9/components/form.blade.php
```

If the same modal URL is opened directly in the browser (no AJAX header), the controller falls back to the **full page** form via `model_page_view()`.

---

## Directory structure

```
resources/views/
├── pages/                              # Theme-neutral app pages
│   ├── generic/                        # Default CRUD pages (all models)
│   │   ├── list.blade.php
│   │   ├── form.blade.php
│   │   └── details.blade.php
│   ├── modals/                         # Default modal fragments (AJAX)
│   │   ├── view.blade.php              # Read-only details in modal
│   │   ├── form.blade.php              # Edit form in modal
│   │   └── delete.blade.php            # Delete confirmation in modal
│   └── {resource}/                     # Optional per-model page overrides
│       ├── list.blade.php              # e.g. pages/categories/list.blade.php
│       ├── form.blade.php
│       ├── details.blade.php
│       └── modals/                     # Optional per-model modal overrides
│           ├── view.blade.php
│           ├── form.blade.php
│           └── delete.blade.php
│
└── themes/
    ├── metronic8/
    │   ├── template.blade.php            # Master layout (HTML shell, assets, modal host)
    │   ├── layout/                     # Header, aside, footer structure
    │   ├── partials/                   # Sidebar, topbar, menu, footer
    │   ├── components/                 # <x-card>, <x-form>, <x-datatable>, etc.
    │   └── controls/form/              # Input markup (text, select, checkbox, …)
    └── metronic9/
        └── (same structure)
```

---

## Naming conventions

| Concept | Format | Example |
|---------|--------|---------|
| Model name (PHP) | StudlyCase | `Category` |
| Resource name (URLs, folders) | plural snake | `categories` |
| Page actions | `list`, `form`, `details` | — |
| Modal actions | `view`, `form`, `delete` | — |
| Route names | `{resource}.{action}` | `categories.index` |
| View dot notation | `pages.{resource}.{action}` | `pages.categories.list` |

Resource name is always:

```php
Str::plural(Str::snake($model)); // Category → categories
```

---

## View resolution

### Full pages — `model_page_view($model, $action)`

Defined in `app/helpers.php`. Used by `BaseController` for `index`, `create`, `edit`, and `show`.

**Resolution order:**

1. **Convention file** — `pages/{resource}/{action}.blade.php` if it exists  
   Example: `pages/categories/list.blade.php`
2. **Config escape hatch** — `config('crud.models.{Model}.views.{action}')` if set and view exists
3. **Generic default** — `pages/generic/{action}.blade.php`

```php
model_page_view('Category', 'list');    // → pages.generic.list (or override)
model_page_view('Category', 'form');    // → pages.generic.form
model_page_view('Category', 'details'); // → pages.generic.details
```

No config entry is required for conventional overrides — create the file and it is picked up automatically.

### Modal fragments — `model_modal_view($model, $action)`

Used by `BaseController` for `modalView`, `modalEdit`, and `modalDelete`.

**Resolution order:**

1. **Convention file** — `pages/{resource}/modals/{action}.blade.php` if it exists  
   Example: `pages/categories/modals/form.blade.php`
2. **Config escape hatch** — `config('crud.models.{Model}.modals.{action}')` if set and view exists
3. **Generic default** — `pages/modals/{action}.blade.php`

```php
model_modal_view('Category', 'view');   // → pages.modals.view
model_modal_view('Category', 'form');   // → pages.modals.form
model_modal_view('Category', 'delete'); // → pages.modals.delete
```

---

## Full page vs modal — why two wrappers?

A modal and a full page are **different delivery formats**, not different business logic.

| | Full page | Modal fragment |
|--|-----------|----------------|
| **View** | `pages/generic/form.blade.php` | `pages/modals/form.blade.php` |
| **Layout** | `@extends(ui_layout())` | None — HTML injected into modal container |
| **Wrapper** | `<x-form-card>` + back button | `<x-modal-content>` |
| **Controller** | `create()`, `edit()` | `modalEdit()` (AJAX) |
| **Shared core** | `<x-form>` | `<x-form :inModal="true">` |

This is the **only built-in duplication** in the view layer: thin wrappers around the same components. Form fields, validation, and routes come from the DTO and repository — not from duplicated blades.

**Note:** Create currently uses only the full page. There is no modal create flow.

Modal fallback behavior (`RespondsWithModal` trait): opening a modal URL directly in the browser renders the equivalent **full page** instead of a fragment.

---

## Theme system

Active theme is set in `.env`:

```
UI_THEME=metronic9
```

Config: `config/ui.php`

### What is theme-specific

| Piece | Location | Purpose |
|-------|----------|---------|
| Master layout | `themes/{theme}/template.blade.php` | HTML shell, CSS/JS assets, `@yield('main')` |
| Navigation | `themes/{theme}/partials/`, `layout/` | Sidebar, header, menu, footer |
| Modal host | `<x-modal>` in template | Empty container; AJAX injects fragments |
| Components | `themes/{theme}/components/` | Card, button, datatable, form, modal-content, … |
| Form controls | `themes/{theme}/controls/form/` | text, select, checkbox, file, … |

### What stays theme-neutral

| Piece | Location |
|-------|----------|
| CRUD pages | `pages/generic/*`, `pages/{resource}/*` |
| Modal content | `pages/modals/*`, `pages/{resource}/modals/*` |

Pages use `@extends(ui_layout())` and `<x-*>` components. Switching `UI_THEME` re-renders the same page with different component markup — no page duplication per theme.

### Helper functions

| Helper | Returns |
|--------|---------|
| `ui_theme()` | Active theme key (`metronic8`, `metronic9`) |
| `ui_layout()` | Theme master layout view name |
| `ui_asset($path)` | URL to theme asset prefix |
| `ui_component_view($name)` | View instance for a theme component |
| `ui_form_view($control)` | Path to a theme form control blade |

### Blade components

PHP classes in `app/View/Components/` use the `ResolvesThemeView` trait to render from `themes/{active}/components/`:

- `<x-card>`, `<x-form-card>`, `<x-form>`, `<x-datatable>`, `<x-details-view>`
- `<x-button>`, `<x-alert>`, `<x-modal>` (page-level modal host)
- `<x-modal-content>`, `<x-modal-dismiss>` (modal fragment chrome)

**Rule for pages:** use components and helpers — never hard-code theme CSS classes in `pages/`.

---

## Adding a new model

Most models require **no new view files**.

1. Create `App\Models\{Model}` with `#[RoutableAttribute]`
2. Create `App\Repositories\{Model}Repository` extending `BaseRepository`
3. Create `App\Data\{Model}Data` with `FormFieldAttribute` on properties
4. Optionally register nav/settings in `config/crud.php`

Field metadata is cached in production — see **[docs/dto-metadata.md](dto-metadata.md)** for cache/warm/clear commands.

The generic controller (`CrudController`), generic pages, and generic modals handle the rest.

---

## Customizing a model (optional)

### Override a full page

Create the file — no controller or config change needed:

```
resources/views/pages/products/list.blade.php
```

Override form or details the same way:

```
pages/products/form.blade.php
pages/products/details.blade.php
```

### Override a modal

```
resources/views/pages/products/modals/form.blade.php
```

### Non-standard view path (config)

Use only when the blade does not follow the `{resource}/{action}` convention:

```php
// config/crud.php
'Product' => [
    'views' => [
        'list' => 'pages.shared.hierarchical-list',
    ],
    'modals' => [
        'form' => 'pages.shared.quick-edit-modal',
    ],
],
```

Config is checked **after** the conventional file path. Prefer convention files whenever possible.

---

## What you should not duplicate

| Do not duplicate | Reason |
|------------------|--------|
| Pages per theme | Components absorb visual differences |
| Controllers per model | `CrudController` + `BaseController` are generic |
| Form field markup per model | Driven by DTO attributes and `<x-form>` |
| Modals per theme | Neutral modal pages + `<x-modal-content>` |

| Acceptable duplication | Reason |
|------------------------|--------|
| `pages/generic/form` vs `pages/modals/form` | Different delivery format (page vs AJAX fragment) |
| Per-model override blades | Opt-in when generic layout is insufficient |
| Theme components | One set per supported theme (Metronic 8, 9, …) |

---

## Controller reference

`App\Http\Controllers\BaseController` — generic CRUD actions.

| Action | View helper | Default view |
|--------|-------------|--------------|
| `index()` | `model_page_view($model, 'list')` | `pages/generic/list` |
| `create()` | `model_page_view($model, 'form')` | `pages/generic/form` |
| `edit()` | `model_page_view($model, 'form')` | `pages/generic/form` |
| `show()` | `model_page_view($model, 'details')` | `pages/generic/details` |
| `modalView()` | `model_modal_view($model, 'view')` | `pages/modals/view` |
| `modalEdit()` | `model_modal_view($model, 'form')` | `pages/modals/form` |
| `modalDelete()` | `model_modal_view($model, 'delete')` | `pages/modals/delete` |

Model name is resolved automatically in `CrudController` from the route name.

---

## Quick decision guide

```
Need to change how a list looks for one model?
  → Create pages/{resource}/list.blade.php

Need a tree instead of a datatable for categories?
  → Create pages/categories/list.blade.php

Need different modal chrome (header/footer styling)?
  → Edit themes/{theme}/components/modal-content.blade.php

Need a new input type styling?
  → Edit themes/{theme}/controls/form/{type}.blade.php

Need to support Metronic 10?
  → Add themes/metronic10/ (template, components, controls) — pages stay unchanged

Need a shared view used by multiple models?
  → Put blade in pages/shared/ and point to it via config crud.models.*.views.*
```

---

## Related files

| File | Role |
|------|------|
| `app/helpers.php` | `model_page_view()`, `model_modal_view()`, theme helpers |
| `app/Http/Controllers/BaseController.php` | CRUD + modal actions |
| `app/Http/Controllers/CrudController.php` | Route-driven model resolution |
| `app/Http/Controllers/Concerns/RespondsWithModal.php` | AJAX fragment vs full page |
| `config/ui.php` | Active theme and theme registry |
| `config/crud.php` | Model overrides, nav, optional view paths |
| `app/Support/CrudEntityRegistry.php` | Auto-discovery of routable models |

---

## Summary

- **Pages** = business content, theme-neutral, generic by default.
- **Themes** = shell (layout, nav, footer) + components + form controls.
- **Modals** = neutral content pages + theme modal components; not duplicated per theme.
- **Overrides** = optional files under `pages/{resource}/`; config only for edge cases.
- **Expansion** = new models plug into generics; custom blades are the exception, not the rule.
