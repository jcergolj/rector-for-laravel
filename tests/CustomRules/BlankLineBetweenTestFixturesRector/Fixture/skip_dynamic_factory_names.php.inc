<?php

namespace DynamicFactoryNames;

class TokenTest extends \PHPUnit\Framework\TestCase
{
    public function test_dynamic_calls(): void
    {
        $factory = 'unrelated';
        $first = User::$factory();
        $second = User::$factory();
        $third = User::{$factory}()->create();
        $fourth = User::{$factory}()->create();
    }
}
