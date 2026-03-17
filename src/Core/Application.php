<?php

namespace SprintPHP\Core;

use SprintPHP\Http\Router;
use SprintPHP\Http\Response;
use Throwable;

class Application
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function registerController(string $controller): void
    {
        $this->router->registerController($controller);
    }

    public function run(): void
    {
        try
        {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $method = $_SERVER['REQUEST_METHOD'];

            $response = $this->router->dispatch($uri, $method);

            Response::success($response);
        }
        catch (Throwable $e)
        {
            ExceptionHandler::handle($e);
        }
    }
}