---
paths:
  - 'app/Filament/**/RelationManagers/*.php'
---

# Relation Managers

## Relation manager labels and icons
The relation manager TAB label comes from $title (getTitle() falls back to headline() of the relationship name — $pluralModelLabel does NOT affect the tab). Set all three: $title for the tab, $pluralModelLabel for table strings, and $icon mirroring the resource navigation icon (Heart, CalendarDays, FolderOpen). Labels: "Pets" (keep the English word, not "Animali"), "Appuntamenti", "Trattamenti". Exception: the create action label "Nuovo pet" in PetsRelationManager is intentional — do not "fix" it.
