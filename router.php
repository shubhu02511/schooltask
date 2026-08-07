<?php
// Core PHP Lightweight Router
class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => rtrim($path, '/'),
            'handler' => $handler
        ];
    }

    public function dispatch(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestUri = rtrim($requestUri, '/');

        if ($requestUri === '') {
            $requestUri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['path'] === $requestUri) {
                $handler = $route['handler'];
                if (is_array($handler)) {
                    try {
                        $className = $handler[0];
                        $action = $handler[1];
                        $controller = new $className();
                        $controller->$action();
                    } catch (Throwable $t) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => 'Controller Error: ' . $t->getMessage()]);
                    }
                } else {
                    call_user_func($handler);
                }
                return;
            }
        }

        // Return 404 response
        if (strpos($requestUri, '/api/') === 0) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'API endpoint not found: ' . $requestUri]);
            exit;
        }

        // Check if static file exists
        $filePath = __DIR__ . $requestUri;
        if (file_exists($filePath) && !is_dir($filePath)) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css'  => 'text/css',
                'js'   => 'application/javascript',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'svg'  => 'image/svg+xml',
                'json' => 'application/json'
            ];
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            readfile($filePath);
            exit;
        }

        // Fallback to static HTML
        if (file_exists(__DIR__ . '/index.html')) {
            readfile(__DIR__ . '/index.html');
            exit;
        }

        http_response_code(404);
        echo "404 Page Not Found";
    }
}
