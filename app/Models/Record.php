<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Relation;

class Record extends BaseModel
{
    protected $table = 'record';
    
    protected $fillable = ['name', 'object_type', 'object_id'];
    
    public function object()
    {
        Relation::morphMap([
            'bill' => \App\Models\Bill::class,
            'test' => \App\Models\Test::class,
        ]);
        return $this->morphTo();
    }
    
    public function bill()
    {
        return BelongsToMorph::build($this, Bill::class, 'object');
    }
    
    public function test()
    {
        return BelongsToMorph::build($this, Test::class, 'object');
    }
}
