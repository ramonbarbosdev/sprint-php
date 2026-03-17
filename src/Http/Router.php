<?php

namespace SprintPHP\Http;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;
use SprintPHP\Attributes\Controller;
use SprintPHP\Attributes\Get;
use SprintPHP\Attributes\Post;
use SprintPHP\Attributes\Put;
use SprintPHP\Attributes\Delete;
use SprintPHP\Attributes\Body;
use SprintPHP\Attributes\Middleware;
use SprintPHP\Attributes\Param;
use SprintPHP\Attributes\Query;
use SprintPHP\Lib\Validation\ValidationEngine;
use SprintPHP\Lib\Validator\DTO;

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
     * Registra um controller no Router, analisando seus atributos (Attributes)
     * para mapear automaticamente as rotas HTTP disponíveis.
     *
     * @author Ramon
     * @since 04/03/2026
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
     * Realiza o despacho da requisição recebida, identificando a rota correspondente
     * com base na URI e no método HTTP, executando middlewares e chamando a ação
     * do controller associada à rota.
     *
     * @author Ramon
     * @since 04/03/2026
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

            $params = $this->injectRequestDataIfNeeded($controller, $action, $method, $params);

            return $instance->$action(...$params);
        }

        throw new Exception("Rota não encontrada", 404);
    }

    /**
     * Resolve o método HTTP correspondente ao atributo da rota (Get, Post, Put, Delete),
     * retornando o método HTTP utilizado na requisição.
     *
     * @author Ramon
     * @since 04/03/2026
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
     * Obtém o prefixo definido no atributo Controller da classe analisada,
     * utilizado para compor o caminho base das rotas do controller.
     *
     * @author Ramon
     * @since 04/03/2026
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
     * Executa os middlewares definidos no controller ou no método da rota
     * antes da execução da ação correspondente.
     *
     * @author Ramon
     * @since 04/03/2026
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
                'method' => $middleware->method,
            ];
        }

        foreach ($methodReflection->getAttributes(Middleware::class) as $attr)
        {
            $middleware = $attr->newInstance();
            $middlewares[] = [
                'class' => $middleware->class,
                'method' => $middleware->method,
            ];
        }

        foreach ($middlewares as $middleware)
        {
            $class = $middleware['class'];
            $method = $middleware['method'] ?? 'handle';

            if (!method_exists($class, $method))
            {
                throw new \Exception("Middleware {$class}::{$method} não encontrado", 500);
            }

            $class::{$method}();
        }
    }

    /**
     * Realiza o match entre a URI da requisição e o padrão da rota registrada,
     * suportando parâmetros dinâmicos definidos no formato {param}.
     *
     * @author Ramon
     * @since 04/03/2026
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
    private function injectRequestDataIfNeeded(
        string $controller,
        string $action,
        string $httpMethod,
        array $params
    ): array
    {
        $reflection = new ReflectionMethod($controller, $action);

        $body = json_decode(file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : [];

        $query = $_GET ?? [];
        $routeParams = $params;

        $finalParams = [];

        foreach ($reflection->getParameters() as $param)
        {
            $type = $param->getType();
            $paramName = $param->getName();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin())
            {
                $className = $type->getName();

                $source = $this->resolveDtoSource($param, $httpMethod);

                $payload = match ($source)
                {
                    'query' => $query,
                    'param' => $routeParams,
                    default => $body,
                };

                $dtoValidate = new DTO;
                $dtoValidate->validateData($className, (array) $payload, null, null, true);

                $dto = new $className();

                foreach ($payload as $key => $value)
                {
                    if (property_exists($dto, $key))
                    {
                        $dto->$key = $this->castDtoValue($dto, $key, $value);
                    }
                }

                ValidationEngine::validateDTO($dto);

                $finalParams[] = $dto;
                continue;
            }

            if ($type instanceof ReflectionNamedType && $type->isBuiltin())
            {
                $value = null;

                if (!empty($param->getAttributes(Param::class)))
                {
                    $value = $routeParams[$paramName] ?? null;
                }

                elseif (!empty($param->getAttributes(Query::class)))
                {
                    $value = $query[$paramName] ?? null;
                }

                else
                {
                    $value = $body[$paramName] ?? null;
                }

                if (is_string($value))
                {
                    $value = trim($value, '"\'');
                }

                $value = $this->castPrimitiveValue($type, $value);

                if ($value === null && $param->isDefaultValueAvailable())
                {
                    $value = $param->getDefaultValue();
                }

                ValidationEngine::validateParameter($param, $value);

                if ($value === null && !$type->allowsNull())
                {
                    throw new Exception("Parâmetro {$paramName} é obrigatório", 400);
                }

                $finalParams[] = $value;
                continue;
            }

            $finalParams[] = null;
        }

        return $finalParams;
    }

    private function resolveDtoSource(ReflectionParameter $param, string $httpMethod): string
    {
        if (!empty($param->getAttributes(Param::class)))
        {
            return 'param';
        }

        if (!empty($param->getAttributes(Query::class)))
        {
            return 'query';
        }

        if (!empty($param->getAttributes(Body::class)))
        {
            return 'body';
        }

        return strtoupper($httpMethod) === 'GET' ? 'query' : 'body';
    }

    private function castDtoValue(object $dto, string $property, mixed $value): mixed
    {
        $reflection = new ReflectionObject($dto);

        if (!$reflection->hasProperty($property))
        {
            return $value;
        }

        $prop = $reflection->getProperty($property);
        $type = $prop->getType();

        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin())
        {
            return $value;
        }

        $typeName = $type->getName();

        if ($value === null)
        {
            return null;
        }

        return match ($typeName)
        {
            'int' => is_numeric($value) ? (int) $value : $value,
            'float' => is_numeric($value) ? (float) $value : $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value,
            'string' => is_scalar($value) ? (string) $value : $value,
            default => $value
        };
    }

    private function castPrimitiveValue(?ReflectionNamedType $type, mixed $value): mixed
    {
        if (!$type || !$type->isBuiltin())
        {
            return $value;
        }

        if ($value === '' || $value === null)
        {
            return null;
        }

        return match ($type->getName())
        {
            'int' => is_numeric($value) ? (int) $value : null,
            'float' => is_numeric($value) ? (float) $value : null,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string' => (string) $value,
            default => $value
        };
    }
}
