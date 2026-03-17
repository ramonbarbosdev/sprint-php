<?php

namespace SprintPHP\Lib\Validation;

use App\Api\Attributes\Max;
use App\Api\Attributes\Min;
use App\Api\Attributes\Required;
use App\Api\Exception\ValidationException;
use ReflectionClass;
use ReflectionParameter;

class ValidationEngine
{
    public static function validateParameter(ReflectionParameter $param, mixed $value): void
    {
        $errors = [];
        $name = $param->getName();

        if ($attr = $param->getAttributes(Required::class)[0] ?? null)
        {
            if ($attr->newInstance()->value && $value === null)
            {
                $errors[$name] = 'é obrigatório';
            }
        }

        if ($attr = $param->getAttributes(Min::class)[0] ?? null)
        {
            $min = $attr->newInstance()->value;

            if ($value !== null && $value < $min)
            {
                $errors[$name] = "deve ser maior ou igual a {$min}";
            }
        }

        if ($attr = $param->getAttributes(Max::class)[0] ?? null)
        {
            $max = $attr->newInstance()->value;

            if ($value !== null && $value > $max)
            {
                $errors[$name] = "deve ser menor ou igual a {$max}";
            }
        }

        if (!empty($errors))
        {
            throw new ValidationException($errors);
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
            throw new ValidationException($errors);
        }
    }
}