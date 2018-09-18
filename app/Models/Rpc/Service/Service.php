<?php

namespace App\Models\Rpc\Service;

use App\Models\BaseModel;

class Service extends BaseModel
{
    protected $table = 'rpc_service_service';
    
    protected $fillable = ['code', 'class_name', 'ioc_variables', 'ioc_repos'];
    
    public function api_list()
    {
        return $this->hasMany('App\Models\Rpc\Service\Api', 'service_id', 'id');
    }
    
}
