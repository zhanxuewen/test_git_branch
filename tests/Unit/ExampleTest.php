<?php

namespace Tests\Unit;

use Tests\TestCase;
use Luminee\Reporter\Models\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $query = Log::with('account')->orderBy('id', 'desc')->get();
        $this->showQueries();
        $this->assertTrue($this->queryCorrect());
    }
}
