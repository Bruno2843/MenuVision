---
paths:
  - 'resources/views/public/**'
  - resources/views/public/dish.blade.php
---

# Public

## Menú público: filtrado por aria-pressed, "Todas" no es data-category-link
El menú público (menu.blade.php + dish-card.blade.php) filtra las tarjetas en el cliente: cada categoría es un <button data-category-link data-categoria>, "Todas" es un <button data-category-all> deliberadamente SIN data-category-link, y las tarjetas usan <article data-dish-card data-categoria>. category-nav.js cambia aria-pressed (nunca aria-current ni scroll-spy) y esconde tarjetas con la clase hidden. La grilla es única (id="platos"), no una sección por categoría.

## Botón AR: bajo el precio, disabled hasta `load`, estados en la página
El botón AR va justo bajo el precio, ANTES de ingredientes, separado del "Volver al menú" (que está en el nav superior). Se renderiza solo si `$modelo3dUrl` no es null, y arranca con `disabled`: AR necesita el GLB en memoria, y ar-viewer.js lo habilita en el evento `load`. Los marcadores son data-ar-visor, data-ar-boton y data-ar-estado (este último con role=status aria-live=polite). Sin modelo se muestra el mensaje "Este plato todavía no tiene un modelo 3D disponible." y la foto.

## Toda imagen de plato declara `data-imagen-respaldo`
Las <img> de plato (tarjeta en dish-card.blade.php y detalle en dish.blade.php) llevan siempre `data-imagen-respaldo="{{ asset(MenuImagen::respaldoParaDish($dish)) }}"`, y el hero queda fuera porque es un asset del repo. imagen-respaldo.js lo intercambia una sola vez en el evento `error`: sin esto, una foto que el navegador no puede cargar deja el contenedor `bg-noche-950` como bloque negro, indistinguible de un plato sin foto. El respaldo es la escena de la categoría, no se guarda en la base y no reemplaza la foto válida.
