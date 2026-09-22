<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

$app = new App\Application(dirname(__DIR__) . '/config');
$app->bootstrap();
Configure::write('debug', false);

// A separate connection with temporary tables shadows the configured schema.
// No application rows are copied, changed, truncated or deleted.
$config = ConnectionManager::getConfig('default');
unset($config['name']);
$config['persistent'] = false;
$config['cacheMetadata'] = false;
ConnectionManager::drop('test');
ConnectionManager::setConfig('test', $config);
ConnectionManager::alias('test', 'default');
