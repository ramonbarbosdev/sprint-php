<?php

namespace SprintPHP\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseManager
{
    private static Capsule $capsule;

    public static function init(array $connections): void
    {
        self::$capsule = new Capsule();

        foreach ($connections as $name => $config)
        {
            self::$capsule->addConnection($config, is_string($name) ? $name : null);
        }

        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();

        self::$capsule->getDatabaseManager()->setDefaultConnection('default');
    }

    public static function getCapsule(): Capsule
    {
        return self::$capsule;
    }
}
