<?php

namespace SprintPHP\Lib\Validation;

use SprintPHP\Attributes\Max;
use SprintPHP\Attributes\Min;
use SprintPHP\Attributes\Required;
use SprintPHP\Exception\ValidationException;
use ReflectionClass;
use ReflectionParameter;

class ValidationEngine
{
    public static function validateParameters(string $controller, string $action, array $values): void
    {
        $reflection = new \ReflectionMethod($controller, $action);
        $errors = [];

        foreach ($reflection->getParameters() as $index => $param)
        {
            $value = $values[$index] ?? null;
            $name = $param->getName();

            // Required
            if ($attr = $param->getAttributes(Required::class)[0] ?? null)
            {
                if ($attr->newInstance()->value && $value === null)
                {
                    $errors[$name] = 'é obrigatório';
                    continue;
                }
            }

            // Min
            if ($attr = $param->getAttributes(Min::class)[0] ?? null)
            {
                $min = $attr->newInstance()->value;

                if ($value !== null && $value < $min)
                {
                    $errors[$name] = "deve ser maior ou igual a {$min}";
                    continue;
                }
            }

            // Max
            if ($attr = $param->getAttributes(Max::class)[0] ?? null)
            {
                $max = $attr->newInstance()->value;

                if ($value !== null && $value > $max)
                {
                    $errors[$name] = "deve ser menor ou igual a {$max}";
                    continue;
                }
            }
        }

        if (!empty($errors))
        {
            throw new ValidationException("Erro de validação", $errors);
        }
    }

    public static function validateDTO(object $dto): void
    {
        $errors = [];
        $reflection = new ReflectionClass($dto);

        foreach ($reflection->getProperties() as $property)
        {
            $name = $property->getName();
            $value = $property->getValue($dto);

            if ($attr = $property->getAttributes(Required::class)[0] ?? null)
            {
                if ($attr->newInstance()->value && $value === null)
                {
                    $errors[$name] = 'é obrigatório';
                }
            }

            if ($attr = $property->getAttributes(Min::class)[0] ?? null)
            {
                $min = $attr->newInstance()->value;

                if ($value !== null && $value < $min)
                {
                    $errors[$name] = "deve ser maior ou igual a {$min}";
                }
            }

            if ($attr = $property->getAttributes(Max::class)[0] ?? null)
            {
                $max = $attr->newInstance()->value;

                if ($value !== null && $value > $max)
                {
                    $errors[$name] = "deve ser menor ou igual a {$max}";
                }
            }
        }

        if (!empty($errors))
        {
            throw new ValidationException("Erro de validação", $errors);
        }
    }
}
