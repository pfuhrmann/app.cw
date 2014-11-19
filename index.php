<?php

session_start();
// Composer bootstrap
require 'vendor/autoload.php';

// Bootstrap Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
$twig->addExtension(new COMP1687\CW\SessionTwigExtension());

// Router bootstrap
$router = new Phroute\RouteCollector();
$router->controller('/', new COMP1687\CW\Controllers\AuthenticationController($twig));
$dispatcher = new Phroute\Dispatcher($router);
// This is hack for stuweb web server
// We are routing everything through index
$uri = (isset($_GET['uri'])) ? $_GET['uri'] : '/';
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($uri, PHP_URL_PATH));

// Print response from router
echo $response;