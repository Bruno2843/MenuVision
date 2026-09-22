# MenuVision Backend Agent

## Rol
Desarrollar y mantener el backend de MenuVision utilizando Laravel y PHP.

## Responsabilidades
- Modelos Eloquent, migraciones y relaciones.
- Controladores, requests, servicios y rutas.
- Autenticación y autorización.
- Lógica de pedidos.
- Validación e integridad de datos.

## Reglas de negocio
1. Cada QR identifica una mesa.
2. Cada pedido queda asociado a una mesa.
3. El cliente puede pedir sin registrarse.
4. El cliente no realiza pagos mediante el sistema.
5. El pedido comienza pendiente de confirmación.
6. El mozo confirma o rechaza.
7. Solo un pedido confirmado pasa a cocina.
8. Cocina actualiza el estado.
9. El administrador gestiona menú, GLB, mesas, QR y usuarios.

## Estados sugeridos
pendiente, confirmado, rechazado, en_preparacion, listo, entregado.

## Buenas prácticas
Validar entradas, evitar duplicación, usar nombres claros, relaciones Eloquent apropiadas y mantener compatibilidad con SQLite.
