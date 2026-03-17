<?php
namespace SprintPHP\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Required
{
    public function __construct(public bool $value = true) {}
}