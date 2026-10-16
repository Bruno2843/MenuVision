---
paths:
  - app/Models/Model3D.php
  - app/Models/Dish.php
---

# Models

## GLB en disco `public`; validar existencia antes de ofrecer AR
Los GLB viven en el disco `public` (storage/app/public), con `archivo` como ruta relativa (ej. 'models/pizza.glb'). El URL se resuelve con Storage::disk('public')->url() y `existe()` valida el archivo en disco antes de ofrecer el visor o el botón AR: una fila puede sobrevivir a su archivo. El controlador (MenuController::showDish) resuelve la URL y la pasa a la vista; la vista nunca lee disco ni rutas. Requiere `php artisan storage:link`.

## Dish photo lives in dishes.imagen, resolved only by MenuImagen
The photo is a path stored in `dishes.imagen` on the `public` disk, named `platos/{id}.{ext}`. `MenuImagen::paraDish()` is the only thing that should resolve a dish image: column first, then the legacy `public/platos/{slug}.*` convention, then the category fallback. Never build those URLs in Blade.

## `imagen` se busca en disco con `rutaImagenEnDisco()`, no con el valor crudo
Filas viejas pueden guardar la misma foto como ruta absoluta de Windows, como `storage/platos/x.jpg` o con `\`. `rutaImagenEnDisco()` normaliza SOLO la búsqueda (quita `storage/app/public/`, `public/`, `storage/`, normaliza separadores) y devuelve null si queda vacío; la columna no se reescribe por el lectura. `tieneImagen()` e `imagenUrl()` usan esa ruta, así una fila legacy sigue mostrando su foto sin tocar la base. El controlador también usa `rutaImagenEnDisco()` para borrar el archivo anterior.
