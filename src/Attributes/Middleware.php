<?php

namespace SprintPHP\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middleware
{
    public function __construct(public string $class, public string $method = 'handle')
    {
    }
}
