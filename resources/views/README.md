# Views structure

Full conventions, decisions, and developer guide: **[docs/ui-views.md](../../docs/ui-views.md)**

## Quick reference

```
resources/views/
├── pages/                          # Theme-neutral (business content)
│   ├── generic/                    # Default list, form, details
│   ├── modals/                     # Default modal fragments (view, form, delete)
│   └── {resource}/                 # Optional per-model overrides (e.g. categories/)
│
└── themes/
    ├── metronic8/
    │   ├── template.blade.php      # Master layout
    │   ├── layout/ partials/       # Nav, header, footer
    │   ├── components/             # <x-card>, <x-form>, <x-modal-content>, …
    │   └── controls/form/          # Input markup
    └── metronic9/
        └── (same structure)
```

## Rules

- **Pages** use `<x-*>` components — no theme-specific CSS in `pages/`.
- **Components** resolve to `themes/{ui_theme()}/components/`.
- **Form controls** resolve via `ui_form_view()` → `themes/{theme}/controls/form/`.
- **View overrides** are file-based: create `pages/{resource}/{action}.blade.php` — no config required.
- Switch themes via `UI_THEME` in `.env`.

## View resolvers

```php
model_page_view('Category', 'list');    // pages/categories/list → pages/generic/list
model_modal_view('Category', 'form');   // pages/categories/modals/form → pages/modals/form
```
