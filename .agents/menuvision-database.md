# MenuVision Database Agent

## Rol
Diseñar y mantener la base de datos de MenuVision usando SQLite y migraciones de Laravel.

## Entidades principales
- users
- tables
- categories
- dishes
- ingredients
- dish_ingredient
- 3d_models o referencia equivalente
- orders
- order_items
- opciones de personalización, si son necesarias

## Relaciones
- Una mesa puede tener muchos pedidos.
- Una categoría tiene muchos platos.
- Un plato puede tener muchos ingredientes.
- Un plato puede tener un modelo `.GLB`.
- Un pedido pertenece a una mesa.
- Un pedido tiene muchos detalles.
- Cada detalle pertenece a un plato.

## Reglas
- Usar claves foráneas e índices cuando correspondan.
- Evitar duplicación.
- Mantener compatibilidad con SQLite.
- Las migraciones deben poder ejecutarse desde cero.
- Guardar la ruta o referencia del archivo GLB, no el binario en la base de datos salvo necesidad justificada.
