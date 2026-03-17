<?php
namespace SprintPHP\Core;

use SprintPHP\Http\Router;

class RouterHolder
{
    private static ?Router $router = null;

    public static function set(Router $router): void
    {
        self::$router = $router;
    }

    public static function get(): Router
    {
        if (!self::$router)
        {
            throw new \Exception("Router não inicializado");
        }

        return self::$router;
    }
}