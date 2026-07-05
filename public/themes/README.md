# UI Theme Assets

Assets are organized per theme so you can switch Metronic versions independently.

| Theme | Path | Source |
|-------|------|--------|
| Metronic 8 (Bootstrap) | `public/themes/metronic8/assets/` | Copied from GenericLaravel |
| Metronic 9 (Tailwind) | `public/themes/metronic9/assets/` | Metronic v9.5 starter-kit |

Set the active theme in `.env`:

```
UI_THEME=metronic9
```

Views live under `resources/views/themes/{theme}/`.
Blade components resolve to `themes.{theme}.components.*` automatically.
