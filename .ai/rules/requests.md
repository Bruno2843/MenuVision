---
paths:
  - 'app/Http/Requests/**/*.php'
---

# Requests

## Import the enum in every FormRequest that names it
A bare `Role::class` inside a namespaced class resolves against that namespace unless imported, so `Rule::enum(Role::class)` in `App\Http\Requests\Admin` silently becomes the nonexistent `App\Http\Requests\Admin\Role` and only fails at validation time with a confusing `Class not found`. Always `use App\Enums\Role;` in every request that references the enum, including subclasses that re-declare `rules()`.
