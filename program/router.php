<?php
declare(strict_types=1);

class Router {
    /**
     * @param array<string, array<string, callable>> $routes
     */
    public function __construct(
        private array $routes = [],
    ) {}

    /**
     * @param callable(): mixed $handler
     */
    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function route(string $method, string $uri): void
    {
        $path    = (string)(parse_url($uri, PHP_URL_PATH) ?: '/');
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            include 'views/404.php';
            return;
        }

        $handler();
    }
}

$router = new Router();

require 'routes.php';
