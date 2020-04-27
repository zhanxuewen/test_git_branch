<?php

namespace App\Models\Log;

use App\Models\BaseModel;

class Navicat extends BaseModel
{
    protected $table = 'log_navicat';

    public $timestamps = false;

    protected $fillable = ['account_id', 'project', 'connection', 'query', 'time', 'created_at'];

    public function account()
    {
        return $this->belongsTo('App\Models\User\Account', 'account_id', 'id');
    }

}
