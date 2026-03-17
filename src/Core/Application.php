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
    private array $middlewares = [];

    public function __construct()
    {
        $this->router = new Router();
    }

    public function useKernel(BaseKernel $kernel): void
    {
        $kernel->boot();
    }

    public function use(string $middleware): void
    {
        $this->middlewares[] = $middleware;
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
            RouterHolder::set($this->router);

            $uri = $this->resolveUri();
            $method = $_SERVER['REQUEST_METHOD'];

            $this->runMiddlewares();

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

        return str_replace('/api.php', '', $uri);
    }

    private function runMiddlewares(): void
    {
        foreach ($this->middlewares as $middleware)
        {
            if (!class_exists($middleware))
            {
                throw new \Exception("Middleware {$middleware} não encontrado");
            }

            $instance = new $middleware();

            if (!method_exists($instance, 'handle'))
            {
                throw new \Exception("Middleware {$middleware}::handle não existe");
            }

            $instance->handle();
        }
    }
}
