<?php

namespace App\Models\User;

use App\Models\BaseModel;

class Role extends BaseModel
{
    protected $table = 'user_role';
    
    protected $fillable = ['code', 'label', 'is_active'];
    
    public function power()
    {
        return $this->belongsToMany('App\Models\User\Power', 'user_role_power', 'role_id', 'power_id');
    }
    
}