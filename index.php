<?php

// Composer bootstrap
require 'vendor/autoload.php';

// Router bootstrap
$router = new Phroute\RouteCollector();
$router->controller('/', 'COMP1687\CW\AppController');
$dispatcher = new Phroute\Dispatcher($router);
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Print response from router
echo $response;