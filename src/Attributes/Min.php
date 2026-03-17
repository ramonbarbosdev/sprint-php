<?php
namespace SprintPHP\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Min
{
    public function __construct(public int $value) {}
}