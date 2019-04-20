<?php namespace Ghobaty\FormThrottler\Tests;

use Ghobaty\FormThrottler\FormThrottlerServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [FormThrottlerServiceProvider::class];
    }
}
