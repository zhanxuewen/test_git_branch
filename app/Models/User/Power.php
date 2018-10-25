<?php

namespace App\Models\User;

use App\Models\BaseModel;

class Power extends BaseModel
{
    protected $table = 'user_power';
    
    protected $fillable = ['group', 'group_label', 'label', 'code', 'route'];
    
}