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

    public function route(string $method, string $path): void
    {
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            $pageTitle = "Page not found";
            http_response_code(404);
            include 'views/404.php';
            return;
        }

        $handler();
    }
}

$router = new Router();

require 'routes.php';
