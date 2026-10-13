# Actualización de EventIC en el VPS

Esta entrega incluye reservas de boletos por punto de venta, límites de venta, consulta de pases por usuario, mejoras de la pantalla de venta y protección contra solicitudes repetidas. También actualiza dependencias de producción a versiones corregidas dentro de las ramas compatibles actuales.

## Preparación

1. Identificar el directorio actual de EventIC y la versión de PHP utilizada tanto por el sitio como por cron. Utilizar PHP 8.2 o superior; la verificación local se realizó con PHP 8.3.
2. Guardar el commit actualmente desplegado con `git rev-parse HEAD`.
3. Hacer un respaldo verificable de MySQL y de `config/.env`, `config/app_local.php` y `webroot/files`. Conservar también una copia de la versión funcional de la aplicación, incluido `vendor`, para recuperación. Guardar los respaldos fuera del directorio público.
4. Revisar `git status --short`. Si hay cambios en el VPS, conservarlos y revisarlos antes de actualizar. No utilizar `git reset --hard` ni `git clean`.
5. Poner el sitio en mantenimiento mediante la configuración del servidor web, suspender temporalmente el cron de EventIC y esperar a que termine cualquier trabajador de correo activo. Esto evita operaciones con una mezcla de código viejo y nuevo.

La migración nueva agrega `ticket_sale_requests`. No elimina boletos ni modifica cuotas, usuarios o contraseñas existentes. La contraseña común de las cuentas de prueba fue un cambio exclusivo de la base local y no viaja en Git.

## Actualización

Desde el directorio de EventIC en el VPS, ejecutar en este orden y detenerse si algún paso falla:

```bash
git pull --ff-only origin main
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
php bin/cake.php migrations migrate
php bin/cake.php migrations status
php bin/cake.php cache clear_all
php bin/deployment_check.php
composer audit --locked --no-dev
git rev-parse HEAD
```

Usar el ejecutable PHP correcto si el servidor tiene varias versiones. Composer debe usar la misma versión. No ejecutar `composer update` en producción: `composer.lock` fija las versiones verificadas.

Comprobar que `CreateTicketSaleRequests` aparezca como `up`. El chequeo debe terminar sin errores. Revisar permisos de `tmp`, `logs` y `webroot/files` para el usuario del servidor web y el de cron; no aplicar permisos 777. Mantener la configuración SMTP, credenciales, dominio y salt existentes. Configurar `DEBUG=false` y comprobar que el sitio sirva desde `webroot` con HTTPS.

Si PHP-FPM/OPcache no recarga archivos automáticamente, recargar el servicio PHP correspondiente usando el mecanismo de administración del VPS. No se fija un nombre de servicio porque depende de la instalación.

## Verificación antes de reabrir

Usar un evento de prueba del VPS, separado de los eventos reales.

- Superadministrador inicia sesión y puede crear/configurar eventos.
- Vendedor ve solo sus eventos y pases, sus cuotas y disponibles por tipo.
- Checador entra por Staff sin ciclo de redirecciones y abre el escáner.
- Emitir un pase y comprobar que aparece una sola vez con un correo pendiente.
- Intentar emitir sin cupo libre: operación bloqueada sin consumir reservas ajenas.
- Confirmar que los totales y precios coinciden al agregar o quitar pases y cambiar de tipo.
- Escanear el pase y repetir el QR: primer ingreso válido, segundo duplicado.
- Cancelar otro pase de prueba sin uso y comprobar que el cupo vuelve a la reserva de su vendedor.
- Abrir una ficha ajena desde un vendedor: acceso bloqueado.
- Revisar `logs/error.log` y la cola de correos.

Reactivar el cron existente y verificar un envío de prueba. Si se ejecuta manualmente un lote, hacerlo con el cron aún suspendido:

```bash
php bin/cake.php process_email_queue --limit 50
```

El worker procesa la cola existente, no solo el evento de prueba. Confirmar antes que sea apropiado enviar los trabajos pendientes. Al finalizar la verificación, reabrir el sitio y confirmar la ejecución normal del cron.

## Recuperación

Si falla una migración o verificación, mantener el sitio en mantenimiento y el cron suspendido. Revisar el error antes de reabrir.

Para volver al commit registrado antes de actualizar:

```bash
git switch --detach COMMIT_ANTERIOR
php bin/cake.php cache clear_all
```

`COMMIT_ANTERIOR` debe reemplazarse por el SHA guardado. Antes de ejecutar el comando de caché, restaurar el directorio `vendor` de la copia funcional anterior. El código anterior podía depender de paquetes declarados de desarrollo para leer `.env`, por lo que no conviene reconstruirlo con `--no-dev`. Recargar PHP si corresponde. Conservar `ticket_sale_requests`: la tabla adicional es compatible con el código anterior. No borrar sus registros ni revertir su migración después de ventas nuevas. No restaurar un respaldo viejo de MySQL sobre ventas posteriores sin una conciliación previa. Restaurar toda la base solo si se verificó que no hubo operaciones nuevas desde el respaldo.

## Evidencia de preparación

El informe de venta/asignación está en `docs/Resultados de pruebas de venta y asignacion.md`. Las pruebas de concurrencia crean bases aisladas y requieren permiso para crear/eliminar esas bases; no se deben ejecutar automáticamente en producción.

Referencias de comandos y despliegue: [CakePHP 5: despliegue](https://book.cakephp.org/5/en/deployment.html), [CakePHP 5: instalación](https://book.cakephp.org/5/en/installation.html). Los comandos de caché y migraciones también se verificaron con el CLI del proyecto.
