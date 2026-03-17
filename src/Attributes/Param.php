<?php

namespace SprintPHP\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Param
{
    public function __construct(
        public ?bool $required = null,
        public ?string $description = null,
        public mixed $example = null
    ) {}
}