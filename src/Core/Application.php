<?php

namespace SprintPHP\Core;

use SprintPHP\Http\Router;

class Application
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function registerController(string $controller)
    {
        $this->router->registerController($controller);
    }

    public function run()
    {
        try {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $method = $_SERVER['REQUEST_METHOD'];

            $response = $this->router->dispatch($uri, $method);

            header('Content-Type: application/json');
            echo json_encode($response);

        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                "error" => $e->getMessage()
            ]);
        }
    }
}