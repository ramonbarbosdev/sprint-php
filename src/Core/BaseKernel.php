<?php

namespace SprintPHP\Core;

abstract class BaseKernel
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function boot(): void
    {
        $this->registerControllers();
        $this->registerMiddlewares();
        $this->bootstrap();
    }

    protected function registerControllers(): void {}
    protected function registerMiddlewares(): void {}
    protected function bootstrap(): void {}
}