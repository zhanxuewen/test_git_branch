<?php

namespace App\Models\System;

use App\Models\BaseModel;

class Config extends BaseModel
{
    protected $table = 'system_config';
    
    protected $fillable = ['type', 'label', 'key', 'value'];
    
}