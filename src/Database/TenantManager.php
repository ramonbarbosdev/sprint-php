<?php

namespace SprintPHP\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class TenantManager
{
    private static ?string $current = null;

    public static function set(string $connection): void
    {
        self::$current = $connection;

        Capsule::connection($connection)->getPdo(); 

        Capsule::setAsGlobal();
        Capsule::getInstance()->getDatabaseManager()->setDefaultConnection($connection);
    }

    public static function get(): ?string
    {
        return self::$current;
    }
}