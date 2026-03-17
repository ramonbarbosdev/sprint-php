<?php

namespace SprintPHP\Core;

use SprintPHP\Http\Router;
use SprintPHP\Http\Response;
use Throwable;
use SprintPHP\Core\BaseKernel;

class Application
{
    private Router $router;
    private array $bootstrappers = [];

    public function __construct()
    {
        $this->router = new Router();
    }

    public function useKernel(BaseKernel $kernel): void
    {
        $kernel->boot();
    }

    // =========================
    // Controllers
    // =========================
    public function registerController(string $controller): void
    {
        $this->router->registerController($controller);
    }

    // =========================
    // Bootstrappers (tipo Spring Config)
    // =========================
    public function bootstrap(callable $callback): void
    {
        $this->bootstrappers[] = $callback;
    }

    public function boot(): void
    {
        foreach ($this->bootstrappers as $bootstrap)
        {
            $bootstrap($this);
        }
    }

    // =========================
    // Run
    // =========================
    public function run(): void
    {
        try
        {
            $uri = $this->resolveUri();
            $method = $_SERVER['REQUEST_METHOD'];

            $response = $this->router->dispatch($uri, $method);

            Response::success($response);
        }
        catch (Throwable $e)
        {
            ExceptionHandler::handle($e);
        }
    }

    // =========================
    // Helpers
    // =========================
    private function resolveUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // remove /api.php se existir
        return str_replace('/api.php', '', $uri);
    }
}
