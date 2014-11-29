<?php

session_start();

// Composer bootstrap
require 'vendor/autoload.php';

// Bootstrap Twig
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader, ['debug' => true]);
$twig->addExtension(new COMP1687\CW\SessionTwigExtension());
$twig->addExtension(new Twig_Extension_Debug());

// Router bootstrap
$router = new Phroute\RouteCollector();
// Authorization filter
$router->filter('auth', function() {
    if (!isset($_SESSION['user']) || $_SESSION['user']['active'] !== '1') {
        header("HTTP/1.0 403 Forbidden");

        return "You are not authorized to access this page!";
    }
});
$router->controller('/', new COMP1687\CW\Controllers\AuthenticationController($twig));
$router->controller('/', new COMP1687\CW\Controllers\ServicesController($twig), ['before' => 'auth']);
$router->controller('/', new COMP1687\CW\Controllers\SearchController($twig));
$router->controller('/', new COMP1687\CW\Controllers\AjaxController());
$dispatcher = new Phroute\Dispatcher($router);

// This is hack for stuweb web server
// We are routing everything through index instead url
// Wrong apache setup
$uri = (isset($_GET['uri'])) ? $_GET['uri'] : '/';
$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($uri, PHP_URL_PATH));

// Print response from router
echo $response;