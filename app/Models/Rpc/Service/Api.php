<?php

namespace App\Models\Rpc\Service;

use App\Models\BaseModel;

class Api extends BaseModel
{
    protected $table = 'rpc_service_service_api';
    
    protected $fillable = ['service_id', 'function_name', 'modifier', 'params', 'has_transaction', 'return', 'author'];
    
    public function service()
    {
        return $this->belongsTo('App\Models\Rpc\Service\Service', 'service_id', 'id');
    }
    
    public function call_list()
    {
        return $this->hasMany('App\Models\Rpc\Service\ApiCall', 'api_id', 'id');
    }
    
}
