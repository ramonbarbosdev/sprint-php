<?php

namespace SprintPHP\Http;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;
use SprintPHP\Attributes\Body;
use SprintPHP\Attributes\Param;
use SprintPHP\Attributes\Query;
use SprintPHP\Lib\Validation\DTO;

class RequestBinder
{
    public function bind(string $controller, string $action, string $httpMethod, array $routeParams): array
    {
        $reflection = new ReflectionMethod($controller, $action);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $query = $_GET ?? [];

        $finalParams = [];

        foreach ($reflection->getParameters() as $param)
        {
            $type = $param->getType();
            $name = $param->getName();

            // =========================
            // DTO
            // =========================
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin())
            {
                $className = $type->getName();

                $payload = $this->resolvePayload($param, $httpMethod, $body, $query, $routeParams);

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

                $finalParams[] = $dto;
                continue;
            }

            // =========================
            // PRIMITIVO
            // =========================
            $value = $this->resolvePrimitive($param, $body, $query, $routeParams);

            $value = $this->castPrimitiveValue($type, $value);

            if ($value === null && $param->isDefaultValueAvailable())
            {
                $value = $param->getDefaultValue();
            }

            $finalParams[] = $value;
        }

        return $finalParams;
    }

    private function resolvePayload(ReflectionParameter $param, string $method, $body, $query, $route)
    {
        if ($param->getAttributes(Param::class)) return $route;
        if ($param->getAttributes(Query::class)) return $query;
        if ($param->getAttributes(Body::class)) return $body;

        return strtoupper($method) === 'GET' ? $query : $body;
    }

    private function resolvePrimitive(ReflectionParameter $param, $body, $query, $route)
    {
        $name = $param->getName();

        if ($param->getAttributes(Param::class)) return $route[$name] ?? null;
        if ($param->getAttributes(Query::class)) return $query[$name] ?? null;

        return $body[$name] ?? null;
    }

    private function castDtoValue(object $dto, string $property, mixed $value): mixed
    {
        $reflection = new ReflectionObject($dto);

        if (!$reflection->hasProperty($property)) return $value;

        $prop = $reflection->getProperty($property);
        $type = $prop->getType();

        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) return $value;

        return match ($type->getName())
        {
            'int' => is_numeric($value) ? (int) $value : null,
            'float' => is_numeric($value) ? (float) $value : null,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string' => (string) $value,
            default => $value
        };
    }

    private function castPrimitiveValue(?ReflectionNamedType $type, mixed $value): mixed
    {
        if (!$type || !$type->isBuiltin()) return $value;

        if ($value === '' || $value === null) return null;

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
