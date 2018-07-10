<?php

namespace App\Models;

class Bill extends BaseModel
{
    protected $table = 'bill';
    
    protected $morphClass = 'bill';
    
    protected $fillable = ['name'];
    
    public function record()
    {
        dd('dsa');
        return $this->morphOne('App\Models\Record', 'object');
    }
    
}
