---
paths:
  - app/Http/Controllers/Admin/UserController.php
  - app/Http/Controllers/Admin/TableController.php
---

# Controllers Admin

## Users are deactivated, never deleted; guard the last active admin
Accounts are deactivated, never deleted: an order has to keep pointing at whoever took it, so `Route::resource('usuarios')` excludes `destroy` on purpose. Three writes are refused: deactivating your own account, and deactivating or demoting the last ACTIVE administrator (a deactivated admin is not a safety net).

## Cambiar la IP no requiere tocar códigos; "Nuevo código" sí invalida stickers
`qr_codes` guarda solo el token (`code`), nunca la URL, y el PNG se dibuja en cada request desde `MenuUrl`. Cambiar `QR_BASE_URL` no escribe nada: basta con volver a descargar el PNG y los stickers viejos conservan token y mesa. El botón que genera un token nuevo ("Nuevo código" en admin/tables/index) es destructivo a propósito para un sticker perdido, lleva confirm y su mensaje de flash lo dice; no usarlo para "actualizar la IP".
