<?php
declare(strict_types=1);

// Read-only checks. Never prints configuration values or credentials.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$failed = 0;
$check = function (string $label, bool $ok) use (&$failed): void {
    echo ($ok ? '[OK] ' : '[ERROR] ') . $label . PHP_EOL;
    if (!$ok) $failed++;
};
$check('PHP 8.2 o superior', version_compare(PHP_VERSION, '8.2.0', '>='));
foreach (['intl', 'mbstring', 'gd', 'zip', 'pdo_mysql', 'fileinfo', 'dom', 'xml'] as $extension) {
    $check('Extension ' . $extension, extension_loaded($extension));
}
if (!is_file($root . '/vendor/autoload.php')) { $check('Dependencias instaladas', false); exit(1); }
require $root . '/vendor/autoload.php';
try {
    (new App\Application($root . '/config'))->bootstrap();
    $check('Configuracion cargada', true);
    $check('Modo de produccion DEBUG=false', !Cake\Core\Configure::read('debug'));
    foreach (['tmp', 'logs', 'webroot/files'] as $directory) {
        $check('Directorio escribible por este usuario: ' . $directory, is_dir($root . '/' . $directory) && is_writable($root . '/' . $directory));
    }
    $db = Cake\Datasource\ConnectionManager::get('default');
    $check('Conexion MySQL', (int)$db->execute('SELECT 1')->fetch('num')[0] === 1);
    $columns = $db->getSchemaCollection()->describe('ticket_sale_requests')->columns();
    $check('Migracion de proteccion de ventas repetidas', !array_diff(['id','event_id','user_id','payload_hash','created'], $columns));
} catch (Throwable $exception) {
    $check('Arranque, base de datos o migraciones; consultar logs/error.log', false);
}
echo $failed ? 'Resultado: revisar errores antes de habilitar ventas.' . PHP_EOL : 'Resultado: comprobaciones locales del servidor satisfactorias.' . PHP_EOL;
exit($failed ? 1 : 0);
