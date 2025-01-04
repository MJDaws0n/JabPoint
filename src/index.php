<?php
GLOBAL $fileRoot;
$fileRoot = dirname(__FILE__).DIRECTORY_SEPARATOR.'..';

// Autoload
require_once $fileRoot.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

// Include includes
require_once $fileRoot.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'include.php';

// Handle routes
$router = new Router;

// Load routes
require_once $fileRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'routes.php';

$router->handleRequest();
?>
