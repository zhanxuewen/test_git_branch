<?php

namespace App\Models\User;

use App\Models\BaseModel;

class RolePower extends BaseModel
{
    protected $table = 'user_role_power';
    
    protected $fillable = ['role_id', 'power_id'];
    
}