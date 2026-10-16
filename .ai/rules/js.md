---
paths:
  - 'resources/js/**'
---

# Js

## model-viewer: importar dist prebuilt, nunca el entry lib/
@google/model-viewer se importa SIEMPRE por su bundle prebuilt `dist/model-viewer.min.js`, nunca por el entry `lib/`. Motivo: el entry lib reexporta símbolos del three del host y su peer range es ^0.183.0, incompatible con el three@0.185.1 del proyecto. El dist es autocontenido. Por eso el paquete está instalado con --legacy-peer-deps: un `npm ci` limpio falla sin ese flag. El runtime entra por import() dinámico en app.js, solo en el detalle de plato, para no inflar el bundle principal.
