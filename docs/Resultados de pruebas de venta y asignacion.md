# EventIC: resultados de venta y asignación

Fecha: 12 de octubre de 2026. Entorno: instalación local de EventIC, PHP 8.3 y MySQL.

## Resultado

Las pruebas finales no mostraron sobreventa ni consumo de reservas ajenas. Se corrigió un fallo que permitía duplicar una venta al repetir la misma solicitud cuando había cupo.

La suite completa pasó: **34 pruebas, 1,609 comprobaciones**. La comprobación adicional de ventas ejecutó **13 escenarios con 30 operaciones**, incluyendo hasta **12 procesos simultáneos**. También se ejecutaron 50 validaciones concurrentes del escáner.

## Venta y asignación

| Escenario | Resultado final |
|---|---|
| 12 operaciones compiten por el último boleto del evento | 1 emisión y 11 rechazos. Un boleto y un correo en cola. |
| Dos vendedores compiten por el último boleto de un tipo | Una emisión. El segundo intento se rechaza. |
| Dos operaciones del mismo vendedor compiten por su última unidad de cuota | Una emisión. Se respeta el límite del vendedor. |
| Dos vendedores utilizan su propia reserva al mismo tiempo | Ambos emiten sin consumir la reserva del otro. |
| Administrador intenta utilizar reservas ajenas | Operación rechazada, sin boleto ni correo. |
| Venta de un lote mayor al cupo | Se rechaza completo, sin emisión parcial. |
| Dos asignaciones compiten por el último lugar | Una asignación aceptada y otra rechazada. |
| Una asignación compite con una venta | Solo una operación ocupa el lugar. |
| La misma solicitud se repite simultáneamente | Ambos intentos reconocen la misma operación. Se conserva un boleto y un correo. |
| Se repite la solicitud tras perder la respuesta | Se conserva la emisión inicial. |
| Se reutiliza una solicitud con datos distintos | Se rechaza la solicitud alterada. |
| Una nueva operación intencional usa los mismos datos del asistente | Se admite como operación distinta si hay cupo. |
| Un vendedor pierde su asignación antes de emitir | Venta rechazada. |

Los contadores del evento coincidieron con los boletos activos en cada escenario. Los correos mencionados son trabajos en cola; estas pruebas no enviaron mensajes SMTP.

## Cancelaciones, cuotas y fallos

- Cancelar un boleto devuelve la unidad a la reserva del vendedor que lo emitió. No la entrega al cupo libre mientras conserve su cuota.
- Repetir la cancelación no vuelve a descontar contadores.
- Reducir una cuota global o por tipo por debajo de lo ya emitido se rechaza.
- Una asignación que supera la capacidad revierte sus cambios.
- Reducir correctamente una cuota libera únicamente el cupo pendiente correspondiente.
- Se provocó un fallo durante la emisión del segundo boleto de un lote. Se revirtieron los boletos, los correos en cola, los contadores y el identificador de la operación.
- El vendedor no puede listar, abrir ni reenviar pases de otro vendedor. Los perfiles autorizados conservan consulta del evento.

## Escaneo concurrente

| Prueba | Resultado |
|---|---|
| 25 operaciones sobre el mismo QR | 1 acceso válido y 24 duplicados. |
| 25 operaciones sobre QR diferentes | 25 accesos válidos. |
| Conteo final | 26 boletos con asistencia y contador del evento de 26. |

## Revisión de la vista de ventas

Se revisó el flujo en navegador a 1440 px y 375 px. Las verificaciones visuales no implicaron emitir boletos en los eventos existentes.

- Resumen e importe sincronizados al agregar, quitar y cambiar tipos.
- Disponibilidad por tipo: disponibles, en captura y restantes.
- Precio y unidades restantes visibles debajo del selector de cada pase.
- Tipos agotados protegidos en los selectores y emisión bloqueada si el lote supera el cupo.
- Una captura de 99 entradas generales con 98 disponibles se bloqueó. Al cambiar una a VIP se retiró el error.
- Avisos de cupo cerca del botón de emisión y de la disponibilidad.
- Carga por Excel plegable para reducir el espacio ocupado durante la venta manual.
- Cabecera y resumen compactos en móvil, sin desbordamiento horizontal. Botón Agregar pase de 44 px de altura.
- Botón con estado de emisión y bloqueo de envío repetido. Los datos capturados se conservan si falla la venta.

La vista es adecuada para el flujo actual de venta manual y carga masiva. La validación de interfaz acompaña a los controles del servidor, que vuelven a comprobar el cupo al confirmar.

## Correcciones realizadas

1. Protección persistente para reconocer la misma solicitud y evitar ventas/correos duplicados.
2. Bloqueo coherente del evento durante venta, asignación, retiro de vendedor, cancelación y guardado de configuración.
3. Revisión de la asignación activa del vendedor dentro de la transacción de venta.
4. Mejoras de presentación, validación y conservación de datos del formulario.

## Alcance y despliegue

Las pruebas de venta concurrente utilizaron procesos independientes que invocan la lógica real de emisión/asignación contra una base vacía aislada. Se copiaron estructuras, no datos de eventos existentes, y se eliminó esa base al terminar. Las pruebas automatizadas utilizaron tablas temporales. No se probaron las condiciones de red, recursos o configuración del VPS.

El código está preparado para despliegue; aún no se ha aplicado al VPS. Antes de habilitarlo debe aplicarse la migración nueva `CreateTicketSaleRequests`, que guarda los identificadores de operaciones para reconocer reintentos. La migración ya se aplicó y verificó localmente. Tras desplegar corresponde realizar una prueba breve del flujo completo en el VPS, incluido el procesamiento de correos. El procedimiento está en `docs/despliegue-vps.md`.

La preparación de la entrega también verificó instalación y arranque con dependencias exclusivamente de producción. Se actualizaron CakePHP a 5.1.10, Authentication a 3.3.7, PHPSpreadsheet a 3.10.8 y Flysystem a 3.36.0. La auditoría de dependencias de producción terminó sin avisos conocidos, y se repitieron satisfactoriamente las 34 pruebas y los 13 escenarios de venta con estas versiones.

Las comprobaciones se pueden repetir con `vendor/bin/phpunit`, `tests/sales_concurrency.php` y `tests/concurrency.php` usando PHP.
