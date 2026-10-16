---
paths:
  - 'app/Http/Controllers/**/*.php'
---

# Controllers

## Eager-load constraint closures get a Relation, not a Builder
A `with(['rel' => fn (Builder $q) => ...])` closure receives the RELATION, not a Builder. Typing the param as `Illuminate\Database\Eloquent\Builder` throws a TypeError the moment any related row exists, so the screen 500s only on non-empty data. Type it as `Illuminate\Database\Eloquent\Relations\Relation`.
