<?php
class Router {
    private $routes = [];

    public function addRoute($method, $route, $callback) {
        // Remove trailing slash from the route when adding
        $route = rtrim($route, '/');
        $this->routes[] = [
            'method' => strtoupper($method),
            'route' => $route,
            'callback' => $callback
        ];
    }

    public function handleRequest() {
        // Get the current URI and method
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // Get path without query string

        // Remove trailing slash from URI for uniform matching
        $uri = rtrim($uri, '/');

        $found = false;

        // Check if there's a matching route
        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && $route['route'] === $uri) {
                call_user_func($route['callback']);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->handle404();
        }
    }

    private function handle404() {
        header("HTTP/1.1 404 Not Found");
        echo "404 Not Found";
    }
}