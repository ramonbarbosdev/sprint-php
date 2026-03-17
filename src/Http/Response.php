<?php

namespace SprintPHP\Http;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');

        echo json_encode($data);
        exit;
    }

    public static function success(mixed $data = null): void
    {
        self::json([
            "success" => true,
            "data" => $data
        ], 200);
    }

    public static function error(string $message, int $status = 500, mixed $details = null): void
    {
        self::json([
            "success" => false,
            "error" => $message,
            "details" => $details
        ], $status);
    }
}