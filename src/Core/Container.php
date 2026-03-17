<?php
namespace SprintPHP\Core;

class Container
{
    private static array $bindings = [];

    public static function bind(string $key, callable $resolver): void
    {
        self::$bindings[$key] = $resolver;
    }

    public static function get(string $key)
    {
        if (!isset(self::$bindings[$key]))
        {
            throw new \Exception("Dependência {$key} não registrada");
        }

        return self::$bindings[$key]();
    }
}