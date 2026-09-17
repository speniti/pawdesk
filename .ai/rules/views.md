---
paths:
  - 'resources/views/**'
---

# Views

## Custom Tailwind utilities require the Filament theme + rebuild
Filament pages render only Filament's CSS; a custom theme is registered at resources/css/filament/pawdesk/theme.css (scans app/Filament/** and resources/views/filament/**) and wired via ->viteTheme() in PawDeskProvider. Tailwind classes used in custom blade views are no-ops until you run `npm run build`. Note: the PHP binary is containerized and cannot see node/npm, so `php artisan make:filament-theme` fails with "Node.js is not installed" — scaffold themes manually instead.
