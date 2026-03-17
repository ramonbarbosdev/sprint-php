<?php

namespace SprintPHP\Contracts;

interface MiddlewareInterface
{
    public function handle(): void;
}