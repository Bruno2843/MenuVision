---
paths:
  - app/Support/ArchivosPlato.php
  - app/Support/MenuUrl.php
---

# Support

## Stage dish files, promote them only after the row commits
Dish files are named after the dish id, so replacing a photo (same extension) or a model (always) overwrites the very file a committed row still points at. Never write there directly. Write to `{ruta}.subiendo` (ArchivoPendiente), record the final path in the row inside the transaction, and `confirmar()` the move only after the commit; on failure `descartar()` the staged copy. Overwriting first and deleting on rollback destroys months-old media on an unrelated DB error.

## Borrar un archivo de plato pasa por `eliminarSiNoEstaEnUso()`
Los archivos se nombran por id de plato para que dos platos no compartan uno, pero filas viejas o escritas a mano pueden apuntar al mismo path. `eliminarSiNoEstaEnUso()` pregunta por `dishes.imagen` y `model3ds.archivo` antes de borrar; se llama DESPUÉS de que la fila desapareció (destroy, destroyModel, descartarReemplazada). `eliminarEnDisco()` queda como la operación cruda y no debe usarse para limpiar en nombre de un plato.

## La URL que codifica un QR sale de MenuUrl, nunca de route() en la vista
Un QR lo lee un teléfono, no el navegador que lo renderizó: `menuvision2.0.test` resuelve solo en la máquina con Herd y `127.0.0.1` en un celular apunta al propio celular. Por eso el origen es configuración (`QR_BASE_URL` en config/menuvision.php) y el path sigue saliendo de la named route. Reglas: en el QR va SOLO el origen, nunca `/menu/TOKEN`; un valor sin esquema http/https se ignora en vez de codificarse (scanners lo buscan como texto); vacío = cae a route() como antes. QrImageController y el panel de mesas son los únicos consumidores.
