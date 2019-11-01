<?php

namespace Tests\Unit;

use App\Helper\Builder;
use Tests\TestCase;
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
        $builder = new Builder();
        $query = $builder->setModel('log')->with('account')->orderBy('id', 'desc')->get();
        $this->showQueries();
        $this->assertTrue($this->queryCorrect());
    }
}
