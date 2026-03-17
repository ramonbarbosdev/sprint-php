<?php

namespace SprintPHP\Core;

use Throwable;
use SprintPHP\Http\Response;
use SprintPHP\Lib\Validation\ValidationException;

class ExceptionHandler
{
    public static function handle(Throwable $e): void
    {
        if ($e instanceof ValidationException)
        {
            Response::error(
                $e->getMessage(),
                400,
                $e->getErrors() ?? null
            );
        }

        if ($e->getCode() >= 400 && $e->getCode() < 600)
        {
            Response::error($e->getMessage(), $e->getCode());
        }

        Response::error($e->getMessage(), 500);
    }
}