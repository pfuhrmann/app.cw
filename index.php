<?php

// Composer bootstrap
require 'vendor/autoload.php';

// Bootstrap Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);

// Router bootstrap
$router = new Phroute\RouteCollector();
$router->controller('/', new COMP1687\CW\AppController($twig));
$dispatcher = new Phroute\Dispatcher($router);
// This is hack for stuweb webserver
$uri = (!empty($_GET['uri'])) ? $_GET['uri'] : '/';
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($uri, PHP_URL_PATH));

// Print response from router
echo $response;