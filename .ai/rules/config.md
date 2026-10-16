---
paths:
  - config/filesystems.php
---

# Config

## El disco `public` usa una URL relativa a la raíz, nunca APP_URL
`disks.public.url` es `'/storage'`, no `env('APP_URL').'/storage'`. La misma instalación se sirve por menuvision2.0.test, por 127.0.0.1 con `artisan serve` y por la IP de LAN desde cada celular que escanea un QR de mesa. Con APP_URL cocido en la URL, la foto y el .glb se piden a un host al que el dispositivo no llega y el <img> falla en silencio: la tarjeta queda con el marco negro. Con URL relativa, `asset()` la resuelve contra el host que sirvió la página.
