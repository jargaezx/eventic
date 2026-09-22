# Cola de correos por evento

En el detalle administrativo del evento, «Cola de correos» muestra:

- **Pendientes:** trabajos `pending`, `preparing` y `processing`, incluidos los que esperan un reintento automático.
- **Procesados:** trabajos `sent`. El servidor de correo aceptó el envío; no certifica recepción en la bandeja del destinatario.
- **Fallidos:** trabajos `failed`, después de agotar los intentos automáticos.
- **Atrasados:** subconjunto de pendientes que lleva al menos 15 minutos desde `available_at`, o desde `locked_at` si está en proceso. Los tiempos de espera programados no se consideran atraso antes de su vencimiento. Si faltan esas fechas, se usa la fecha de creación o modificación disponible.

También se indican los trabajos en proceso y cancelados. Las cifras son de los trabajos del evento, no del campo histórico `tickets.last_emailed`, y se consultan al cargar la página o pulsar «Actualizar estado». Los correos de prueba del diseñador siguen siendo síncronos y no aparecen en la cola.

## Reintento seguro

«Reintentar fallidos» requiere POST, CSRF y permiso `manageTickets` sobre el evento (propietario, superadministrador o staff activo con gestión del evento o registro de pases). Ver un evento no concede por sí mismo permiso de reintento.

Se reutiliza el trabajo fallido: vuelve a `pending`, reinicia `attempts` en cero, conserva `max_attempts` y destinatario, queda disponible de inmediato y limpia bloqueo y error. Se omiten pases inactivos, eliminados o de otro evento, tipos no soportados, trabajos sin intentos permitidos y registros con fecha de envío.

Para un mismo pase y destinatario se omiten los fallidos con otro trabajo pendiente/en proceso, entrega incierta o un envío posterior procesado. Si hay varios fallidos, se prioriza el más reciente. El reintento y el encolado existente bloquean el pase dentro de una transacción; la comprobación de estado evita reactivar el mismo trabajo mediante solicitudes simultáneas.

## Interrupciones del procesador

El comando toma un trabajo a la vez y mantiene un bloqueo MySQL por trabajo durante su ejecución. Cada inicio revisa bloqueos de más de 15 minutos; nunca recupera un trabajo cuyo procesador conserva el bloqueo activo.

- `preparing`: todavía no comenzó la entrega mediante el mailer. Tras una interrupción vuelve a pendientes de forma segura.
- `processing`: comenzó la entrega. Si el proceso termina sin registrar el resultado, pasa a `uncertain`; un error en esta fase también requiere revisión, porque SMTP puede haber aceptado el mensaje antes de perder la conexión.
- `uncertain`: aparece una advertencia independiente en el evento. No se reintenta automáticamente ni mediante «Reintentar fallidos». El operador debe revisar los registros del proveedor y confirmar si fue entregado o si procede reenviar. La acción requiere POST, CSRF, permiso de gestión y confirmación explícita. Si se confirma como enviado, `sent_at` registra el momento de la confirmación administrativa.

Esta precaución evita reenviar por una respuesta SMTP ambigua; no promete entrega exactamente una vez ni recepción en la bandeja de entrada.

El procesador existente continúa atendiendo la cola:

```sh
php bin/cake.php process_email_queue --limit 50
```

La acción web solo reencola; el procesador debe ejecutarse periódicamente en el servidor. No se requieren tablas ni migraciones nuevas. Al desplegar, detener los procesadores de la versión anterior y esperar a que terminen sus envíos antes de iniciar la versión nueva.

## Servidor o hosting de producción

Configurar en el programador del hosting una ejecución por minuto del comando anterior, con la ruta absoluta al PHP CLI y a `bin/cake.php`, directorio de trabajo de Eventic y salida a un registro fuera de `webroot`. El proceso debe usar la misma configuración de base de datos y SMTP que la web, y tener permiso de escritura en `tmp`, `logs` y los directorios de imágenes generadas. El programador debe reanudarse tras un reinicio. No se ha instalado un programador en la computadora local.

El proveedor todavía debe confirmar acceso SSH/panel, ruta de PHP, límites de ejecución y concurrencia. Ajustar el límite del comando al volumen de registro y al límite SMTP del proveedor. Revisar los atrasados durante el evento; aumentar la capacidad solo después de una prueba con el servicio real.

Antes de habilitarlo: verificar PHP y extensiones del proyecto, MySQL con soporte de bloqueos nombrados, SMTP, zona horaria y dominio de envío. Probar un pase en Gmail/Outlook y en el teléfono del operador, comprobar el QR con la cámara real y ensayar el reinicio del procesador. Las pruebas locales no certifican entregabilidad del proveedor.

## Verificación

```sh
php vendor/bin/phpunit
```

La suite requiere el esquema MySQL de Eventic actualizado y permiso para crear tablas temporales. Usa una conexión independiente con copias temporales vacías de las tablas y un transporte de correo de pruebas. No modifica registros existentes ni envía correos externos.

Se verifican contadores y atrasos por evento, reintento repetido, exclusión de duplicados y pases no disponibles, permisos de propietario/staff/usuario ajeno, POST y CSRF, renderizado de la vista y el comando existente en éxito, fallo, espera automática y cancelación. También se ejecutan el generador real del pase y el mailer, comprobando `last_emailed` y `email_attempt_count`.

La barra de acciones del detalle mantiene «Registrar» y «Escanear» visibles y agrupa reporte y eliminación en «Más acciones». Verificada en navegador a 320, 375, 768 y 1440 px, con navegación mediante teclado y confirmación de eliminación.
