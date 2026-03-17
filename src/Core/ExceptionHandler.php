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
             var_dump($e->getErrors());
    die;
            Response::error(
                $e->getMessage() ?: "Erro de validação",
                400,
                $e->getErrors() ?? null
            );
            return;
        }

        if ($e->getCode() >= 400 && $e->getCode() < 600)
        {
            Response::error(
                $e->getMessage() ?: "Erro",
                $e->getCode()
            );
            return;
        }

        Response::error(
            $e->getMessage() ?: "Erro interno",
            500
        );
    }
}
