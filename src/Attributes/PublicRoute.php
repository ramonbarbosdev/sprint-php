<?php

namespace SprintPHP\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class PublicRoute
{
    public function __construct(public string $path) {}
}