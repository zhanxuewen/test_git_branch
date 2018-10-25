<?php

namespace App\Models\User;

use App\Models\BaseModel;

class AccountRole extends BaseModel
{
    protected $table = 'user_account_role';
    
    protected $fillable = ['account_id', 'role_id'];
    
}