<?php

namespace App\Models\Log;

use App\Models\BaseModel;

class Log extends BaseModel
{
    protected $table = 'logs';
    
    protected $fillable = ['section', 'log_type', 'account_id', 'content'];
    
    public function account()
    {
        return $this->belongsTo('App\Models\User\Account', 'account_id', 'id');
    }
    
}
