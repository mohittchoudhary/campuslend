<?php
/**
 * CampusLend Lightweight MVC Router
 * Supports HTTP methods, route parameters, and middleware pipelines
 */

namespace App\Helpers;

use Exception;

class Router
{
    private static array $routes = [];

    public static function get(string $path, array|callable $handler, array $middleware = []): void
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post(string $path, array|callable $handler, array $middleware = []): void
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    private static function addRoute(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $path = '/' . trim($path, '/');
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public static function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Strip query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        // Clean base path if running in subfolder
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '/' && $scriptName !== '\\' && str_starts_with($uri, $scriptName)) {
            $uri = substr($uri, strlen($scriptName));
        }

        $uri = '/' . trim($uri, '/');

        foreach (self::$routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convert route pattern with {param} into regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // Filter string keys for named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run Middleware Pipeline
                foreach ($route['middleware'] as $mw) {
                    self::runMiddleware($mw);
                }

                // Execute handler
                self::executeHandler($route['handler'], $params);
                return;
            }
        }

        // No match found -> 404
        http_response_code(404);
        $title = "404 - Page Not Found - CampusLend";
        ob_start();
        if (file_exists(__DIR__ . '/../views/pages/404.php')) {
            require __DIR__ . '/../views/pages/404.php';
        } else {
            echo "<h1>404 Not Found</h1><p>The requested page does not exist on CampusLend.</p>";
        }
        $content = ob_get_clean();
        if (file_exists(__DIR__ . '/../views/layouts/main.php')) {
            require __DIR__ . '/../views/layouts/main.php';
        } else {
            echo $content;
        }
    }

    private static function runMiddleware(string $middleware): void
    {
        $class = "\\App\\Middleware\\" . ucfirst($middleware) . "Middleware";
        if (class_exists($class)) {
            $instance = new $class();
            $instance->handle();
            return;
        }

        // Fallback checks
        switch ($middleware) {
            case 'auth':
                if (!is_logged_in()) {
                    redirect('/login', ['error' => 'Please sign in to access this page.']);
                }
                break;
            case 'guest':
                if (is_logged_in()) {
                    redirect(is_admin() ? '/admin/dashboard' : '/dashboard');
                }
                break;
            case 'verified':
                if (!is_logged_in()) {
                    redirect('/login');
                }
                if (!is_verified()) {
                    redirect('/verify-otp', ['warning' => 'Please verify your campus email first.']);
                }
                break;
            case 'admin':
                if (!is_logged_in()) {
                    redirect('/login', ['error' => 'Please sign in to access admin functions.']);
                }
                if (!is_admin()) {
                    http_response_code(403);
                    echo "<!DOCTYPE html><html><head><title>403 Forbidden - CampusLend Admin</title>";
                    echo "<style>body{font-family:system-ui,-apple-system,sans-serif;background:#0f172a;color:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;text-align:center;}</style></head><body>";
                    echo "<div style='max-width:480px;padding:32px;background:#1e293b;border-radius:16px;border:1px solid #334155;'>";
                    echo "<h1 style='color:#ef4444;font-size:3rem;margin:0 0 12px 0;'>403</h1>";
                    echo "<h2 style='margin:0 0 8px 0;font-size:1.4rem;'>Forbidden: Administrator Access Only</h2>";
                    echo "<p style='color:#94a3b8;font-size:0.95rem;line-height:1.6;margin-bottom:24px;'>You are logged in as a student. Only campus administrators have permission to access the control panel.</p>";
                    echo "<a href='/feed' style='display:inline-block;padding:10px 22px;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;'>Return to Campus Feed</a>";
                    echo "</div></body></html>";
                    exit;
                }
                break;
            case 'csrf':
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
                    http_response_code(419);
                    redirect($_SERVER['HTTP_REFERER'] ?? '/', ['error' => 'Page expired or invalid CSRF token. Please try again.']);
                }
                break;
        }
    }

    private static function executeHandler(array|callable $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, array_values($params));
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller {$controllerClass} not found.");
            }

            $controller = new $controllerClass();
            if (!method_exists($controller, $method)) {
                throw new Exception("Action {$method} not found on {$controllerClass}.");
            }

            call_user_func_array([$controller, $method], array_values($params));
            return;
        }

        throw new Exception("Invalid route handler specified.");
    }
}
