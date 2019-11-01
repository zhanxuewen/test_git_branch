<?php

namespace Tests;

use Tests\Query\Handler;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, Handler;

}
