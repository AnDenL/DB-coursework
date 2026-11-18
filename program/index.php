<?php
declare(strict_types=1);

require_once "db.php";
require_once "router.php";

$path    = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$router->route($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
