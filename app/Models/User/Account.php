<?php

namespace App\Models\User;

use Hash;
use App\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticateAbleContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizeAbleContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Account extends BaseModel implements AuthenticateAbleContract,
    AuthorizeAbleContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;
    
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    
    protected $table = 'user_account';
    
    protected $hidden = ['password'];
    
    protected $fillable = ['username', 'password', 'avatar'];
    
    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = e($value);
    }
    
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    
    public function setAvatarAttribute($value)
    {
        $this->attributes['avatar'] = e($value);
    }
    
}