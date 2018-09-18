<?php

namespace App\Models\Rpc\Service;

use App\Models\BaseModel;

class ApiCall extends BaseModel
{
    protected $table = 'rpc_service_service_api_call';
    
    protected $fillable = ['api_id', 'repository_id', 'function_id', 'function_name', 'params'];
    
    public function api()
    {
        return $this->belongsTo('App\Models\Rpc\Service\Api', 'api_id', 'id');
    }
    
    public function repository()
    {
        return $this->belongsTo('App\Models\Rpc\Repo\Repository', 'repository_id', 'id');
    }
    
    public function functions()
    {
        return $this->belongsTo('App\Models\Rpc\Repo\Functions', 'function_id', 'id');
    }
    
}
