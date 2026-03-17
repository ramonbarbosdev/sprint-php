<?php

namespace SprintPHP\Http;

use Exception;
use ReflectionClass;
use SprintPHP\Attributes\Controller;
use SprintPHP\Attributes\Delete;
use SprintPHP\Attributes\Get;
use SprintPHP\Attributes\Middleware;
use SprintPHP\Attributes\Post;
use SprintPHP\Attributes\Put;
use SprintPHP\Http\RequestBinder;
use SprintPHP\Lib\Validation\ValidationEngine;

class Router
{
    private array $routes = [];
    private string $globalPrefix = '';
    private array $controllers = [];

    public function getControllers(): array
    {
        return $this->controllers;
    }

    /**
     * Registra controllers e mapeia rotas via Attributes
     */
    public function registerController(string $controller): void
    {
        $this->controllers[] = $controller;

        $reflection = new ReflectionClass($controller);
        $controllerPrefix = $this->getControllerPrefix($reflection);

        foreach ($reflection->getMethods() as $method)
        {
            foreach ($method->getAttributes() as $attribute)
            {
                $instance = $attribute->newInstance();
                $httpMethod = $this->resolveHttpMethod($attribute->getName());

                if (!$httpMethod)
                {
                    continue;
                }

                $this->routes[] = [
                    'method' => $httpMethod,
                    'path' => $this->globalPrefix . $controllerPrefix . $instance->path,
                    'controller' => $controller,
                    'action' => $method->getName()
                ];
            }
        }
    }

    /**
     * Dispatcher principal
     */
    public function dispatch(string $uri, string $method)
    {
        foreach ($this->routes as $route)
        {
            if ($route['method'] !== $method)
            {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);

            if ($params === false)
            {
                continue;
            }

            $controller = $route['controller'];
            $action = $route['action'];

            $instance = new $controller();

            $this->executeMiddlewares($controller, $action);

            $binder = new RequestBinder();
            $params = $binder->bind($controller, $action, $method, $params);

            ValidationEngine::validateParameters($controller, $action, $params);

            return $instance->$action(...$params);
        }

        throw new Exception("Rota não encontrada", 404);
    }

    /**
     * Resolve método HTTP
     */
    private function resolveHttpMethod(string $attribute): ?string
    {
        return match ($attribute)
        {
            Get::class => 'GET',
            Post::class => 'POST',
            Put::class => 'PUT',
            Delete::class => 'DELETE',
            default => null
        };
    }

    /**
     * Prefixo do controller
     */
    private function getControllerPrefix(ReflectionClass $reflection): string
    {
        $attributes = $reflection->getAttributes(Controller::class);

        if (!$attributes)
        {
            return '';
        }

        return $attributes[0]->newInstance()->prefix;
    }

    /**
     * Executa middlewares
     */
    private function executeMiddlewares(string $controller, string $action): void
    {
        $controllerReflection = new ReflectionClass($controller);
        $methodReflection = $controllerReflection->getMethod($action);

        $middlewares = [];

        foreach ($controllerReflection->getAttributes(Middleware::class) as $attr)
        {
            $middleware = $attr->newInstance();
            $middlewares[] = [
                'class' => $middleware->class,
                'method' => $middleware->method ?? 'handle',
            ];
        }

        foreach ($methodReflection->getAttributes(Middleware::class) as $attr)
        {
            $middleware = $attr->newInstance();
            $middlewares[] = [
                'class' => $middleware->class,
                'method' => $middleware->method ?? 'handle',
            ];
        }

        foreach ($middlewares as $middleware)
        {
            $class = $middleware['class'];
            $method = $middleware['method'];

            $instance = new $class();

            if (!method_exists($instance, $method))
            {
                throw new Exception("Middleware {$class}::{$method} não encontrado", 500);
            }

            $instance->{$method}();
        }
    }

    /**
     * Match de rota com suporte a parâmetros {id}
     */
    private function matchRoute(string $routePath, string $uri)
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $routePath, $paramNames);

        $pattern = preg_replace('/\{[a-zA-Z_][a-zA-Z0-9_]*\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches))
        {
            array_shift($matches);

            $namedParams = [];

            foreach ($matches as $index => $value)
            {
                $name = $paramNames[1][$index] ?? (string) $index;
                $namedParams[$name] = urldecode($value);
            }

            return $namedParams;
        }

        return false;
    }
}
