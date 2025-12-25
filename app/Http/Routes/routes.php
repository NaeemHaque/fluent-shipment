<?php

$router->namespace('FluentShipment\App\Http\Controllers')
	->withDefaultPolicy()
	->group(fn($router) => require_once __DIR__ . "/api.php");
