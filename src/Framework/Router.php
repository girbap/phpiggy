<?php

declare(strict_types=1);

namespace Framework;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function add(string $method, string $path, array $controller): void
    {
        $path = $this->normalizePath($path);
        $method = strtoupper($method);
        $this->routes[] = [
            'path'       => $path,
            'method'     => $method,
            'controller' => $controller,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        $path = "/{$path}/";
        $path = preg_replace('#[/]{2,}#', '/', $path);

        return $path;
    }

    public function dispatch(string $path, string $method, Container $container = null)
    {
        $path = $this->normalizePath($path);
        $method = strtoupper($method);

        foreach ($this->routes as $route)
        {
            if (!preg_match("#^{$route['path']}$#", $path) || $route['method'] !== $method)
            {
                continue;
            }

            [$class, $function] = $route['controller'];

            if ($container === null)
            {
                $controllerInstance = new $class;
            }
            else
            {
                $controllerInstance = $container->resolve($class);
            }

            $action = function () use ($controllerInstance, $function)
            {
                return $controllerInstance->$function();
            };

            foreach ($this->middlewares as $middleware)
            {
                if ($container === null)
                {
                    $middlewareInstance = new $middleware;
                }
                else
                {
                    $middlewareInstance = $container->resolve($middleware);
                }

                $action = function () use ($action, $middlewareInstance)
                {
                    return $middlewareInstance->process($action);
                };
            }

            $action();

            return;
        }
    }

    public function addMiddleware(string $middleware): void
    {
        $this->middlewares[] = $middleware;
    }
}
