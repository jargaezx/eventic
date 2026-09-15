# Reporte de Prueba de Carga 1000+

Fecha: 15/09/2026

## Escenario

Evento de prueba: Congreso Institucional de Innovación 104544

- Capacidad: 1200
- Pases emitidos: 1000
- Tipos de boleto: Entrada general y Entrada VIP
- Correos en cola: 1000
- Check-in objetivo: 800 asistencias
- Reportes probados: balance de ventas y asistencia

## Resultados

- Creación de 1000 pases con cola de correo: 7.034 s
- Envío de 1000 correos desde cola local con Mailpit: 208.652 s
- Vista detalle del evento: 534 ms
- Listado de registros con búsqueda/filtros: 211 ms
- Reporte operativo en pantalla: 237 ms
- Pantalla de escaneo admin: 321 ms
- Pantalla de escaneo staff: 170 ms
- Exportación balance Excel: 1.9 s
- Exportación asistencia Excel: 1.9 s
- Doble escaneo: bloqueado correctamente con estado 409 y mensaje de pase ya utilizado.

## Observaciones

La aplicación soporta un evento de 1000 participantes en operación normal. La cola evita que la venta quede bloqueada por renderizado del pase y SMTP.

El cuello de botella principal es el envío de correos con renderizado de imágenes. En local tomó cerca de 3.5 minutos para 1000 correos. Es aceptable si la tarea programada corre cada minuto, pero para eventos grandes conviene mantener el cron activo y procesar lotes frecuentes.

El escaneo secuencial funcionó correctamente. En una prueba artificial con 25 validaciones simultáneas desde el mismo navegador aparecieron respuestas sin JSON. No se reprodujo en validación individual ni secuencial. Para operación real con escaneo humano no parece bloqueante, pero conviene fortalecer la API para ráfagas y devolver JSON consistente ante cualquier error.

## Veredicto

El sistema puede operar un evento real de 1000+ participantes con la arquitectura actual, siempre que la cola de correo esté activa por cron.

Antes de eventos con varios accesos escaneando al mismo tiempo, conviene hacer una mejora puntual en robustez de API: envolver errores de la ruta de escaneo en JSON, registrar causa exacta y probar ráfagas concurrentes controladas.
