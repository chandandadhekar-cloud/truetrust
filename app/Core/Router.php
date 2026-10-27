<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function group(string $prefix, array $middleware, callable $define): void
    {
        [$prevPrefix, $prevMw] = [$this->groupPrefix, $this->groupMiddleware];
        $this->groupPrefix .= $prefix;
        $this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
        $define($this);
        [$this->groupPrefix, $this->groupMiddleware] = [$prevPrefix, $prevMw];
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $full = rtrim($this->groupPrefix . $path, '/') ?: '/';
        $regex = preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', static function ($m) {
            return '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')';
        }, $full);
        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
        ];
    }

    /** @return array{handler: array, params: array, middleware: array} */
    public function match(string $method, string $path): array
    {
        $allowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $method && !($method === 'HEAD' && $route['method'] === 'GET')) {
                $allowed = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return ['handler' => $route['handler'], 'params' => $params, 'middleware' => $route['middleware']];
        }
        throw new HttpException($allowed ? 405 : 404);
    }
}
