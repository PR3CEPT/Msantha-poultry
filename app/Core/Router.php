<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    private array $globalMiddlewares = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function addRoute(
        string $method,
        string $path,
        array|callable $handler,
        array $middlewares = []
    ): self {
        $normalizedPath = '/' . trim($path, '/');
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $normalizedPath);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $normalizedPath,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];

        return $this;
    }

    public function addMiddleware(callable|string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                $middlewares = array_merge($this->globalMiddlewares, $route['middlewares']);
                return $this->runPipeline($middlewares, $request, function (Request $req) use ($route, $params) {
                    return $this->invokeHandler($route['handler'], $req, $params);
                });
            }
        }

        return $this->handleNotFound($request);
    }

    private function invokeHandler(array|callable $handler, Request $request, array $params): Response
    {
        $convertedParams = [];
        foreach ($params as $k => $v) {
            $convertedParams[$k] = (is_string($v) && ctype_digit($v)) ? (int) $v : $v;
        }
        $request->setParams($convertedParams);
        if (is_callable($handler) && !is_array($handler)) {
            $result = $handler($request, ...array_values($convertedParams));
        } elseif (is_array($handler)) {
            [$class, $action] = $handler;
            if (!class_exists($class)) {
                throw new RuntimeException("Controller class {$class} not found");
            }
            $controller = new $class();
            if (!method_exists($controller, $action)) {
                throw new RuntimeException("Action {$action} not found in {$class}");
            }
            $result = $controller->$action($request, ...array_values($convertedParams));
        } else {
            throw new RuntimeException("Invalid route handler format");
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    private function runPipeline(array $middlewares, Request $request, Closure $target): Response
    {
        $runner = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function (Request $req) use ($middleware, $next) {
                    if (is_callable($middleware)) {
                        return $middleware($req, $next);
                    }
                    if (is_string($middleware) && class_exists($middleware)) {
                        $instance = new $middleware();
                        return $instance->handle($req, $next);
                    }
                    return $next($req);
                };
            },
            $target
        );

        return $runner($request);
    }

    private function handleNotFound(Request $request): Response
    {
        if ($request->isJson()) {
            return Response::json(['error' => 'Not Found', 'path' => $request->uri()], 404);
        }

        try {
            $viewResponse = View::render('errors/404', ['title' => 'Page Not Found', 'path' => $request->uri()]);
            $viewResponse->setStatusCode(404);
            return $viewResponse;
        } catch (\Throwable) {
            return Response::html('<h1>404 Not Found</h1><p>The requested page was not found.</p>', 404);
        }
    }
}
