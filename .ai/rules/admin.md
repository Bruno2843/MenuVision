---
paths:
  - 'resources/views/admin/**/*.blade.php'
---

# Admin

## Hidden 0 input must come BEFORE its checkbox
PHP keeps the last value sent for a repeated field name. A hidden `name="campo" value="0"` placed AFTER `name="campo" value="1"` makes the checkbox a no-op, so every dish saved as agotado and every user locked out. Put the hidden input first. Locked in by a test that compares the offsets in the rendered HTML.
