---
paths:
  - 'app/Filament/Resources/**/Tables/*.php'
---

# Tables

## Relation column search: don't table-qualify searchable([...]) columns
On a relation column (e.g. `customer.full_name`), searchable([...]) column names are applied inside an exists subquery on the RELATED table: pass them relative to the related model (`['first_name', 'last_name']`), never table-qualified (`['customers.first_name', ...]`). Table-qualified names hit getJsonSafeColumnName() and become json_extract("customers", '$."first_name"'), which fails on SQLite/MySQL with "malformed JSON".
