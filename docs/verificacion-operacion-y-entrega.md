# Verificación de operación y entrega

## Evidencia local

- PHPUnit: 21 pruebas, 1538 aserciones. Tablas temporales vacías y transporte de correo capturado; sin modificar asistentes existentes ni enviar mensajes externos.
- Concurrencia: 25 solicitudes al mismo pase producen 1 acceso y 24 duplicados; 25 pases distintos producen 25 accesos. Contador del evento: 26, igual al total de pases atendidos. La actualización del pase y del contador es transaccional.
- API: respuestas JSON para sesión ausente, método incorrecto y errores internos; no se muestran detalles internos al escáner. El escáner controla el tiempo de espera y conserva la posibilidad de verificar el estado después de un fallo de red.
- Cola: permisos, POST/CSRF, aislamiento por evento, reintento repetido, exclusión de duplicados, recuperación segura, bloqueo de procesos activos y revisión explícita de entregas inciertas.
- Pase: fuente incluida en las dependencias, sin depender de fuentes de Windows; texto ajustado por ancho real, plantilla contenida sin deformar, QR cuadrado con margen blanco.
- Correo: diseño adaptable, QR y portada incrustados por CID, versión de texto y pase PNG adjunto. MIME inspeccionado: HTML/texto alternativos, dos imágenes inline y un adjunto PNG.
- QR: lector independiente ZXing-C++ reconoce el identificador esperado en el pase completo, el QR inline, tamaños 180/240/280 y captura móvil del correo.
- Fecha: se conserva la hora del objeto del evento usando el formateador de PHP; se evita la conversión incorrecta observada en ICU 72.1 para México en octubre de 2026.
- Acciones del evento: registrar/escanear visibles, reporte/eliminación en menú; verificación previa a 320, 375, 768 y 1440 px y con teclado.

## Repetición

```sh
php vendor/bin/phpunit
php tests/concurrency.php
```

La prueba de concurrencia crea y elimina exclusivamente una base temporal con nombre aleatorio; requiere permisos para crear bases. No debe ejecutarse como prueba de carga sobre producción.

Las muestras visuales locales están en `tmp/delivery-proof/` y usan un asistente ficticio. No se incluyen en el repositorio.

## Pendiente del despliegue

Activar y verificar el programador en el servidor elegido, probar SMTP real y recepción en clientes de correo, comprobar cámara/impresión y ensayar conectividad y reinicios. El resultado local no equivale a una certificación en Gmail, Outlook ni en dispositivos físicos. Ver `cola-correos-evento.md` para la recuperación y revisión de envíos.
