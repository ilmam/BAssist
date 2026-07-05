# Views structure

```
resources/views/
├── pages/                  # App pages (theme-agnostic)
├── template.blade.php      # Backward-compat shim → active theme layout
├── welcome.blade.php       # Standalone landing page
└── themes/
    ├── metronic8/
    │   ├── template.blade.php
    │   ├── layout/ partials/ components/
    │   ├── controls/form/  # Form field markup (Bootstrap)
    │   └── _stock/         # Unused Metronic demo files
    └── metronic9/
        ├── template.blade.php
        ├── partials/ components/
        └── controls/form/  # Form field markup (Tailwind / kt-*)
```

## Rules

- **Pages** use `<x-*>` components and `Form::field()` — no theme-specific CSS in pages.
- **Components** share the same PHP API; markup in `themes/{theme}/components/`.
- **Form controls** resolve via `ui_form_view()` → `themes/{theme}/controls/form/`.
- **Navigation** is defined in `config/navigation.php`.
- Switch themes via `UI_THEME` in `.env`.
