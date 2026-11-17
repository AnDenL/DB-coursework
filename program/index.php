<?php
declare(strict_types=1);

require_once "router.php";

$router->route($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
