<?php

namespace App\Models\Rpc\Repo;

use App\Models\BaseModel;

class Functions extends BaseModel
{
    protected $table = 'rpc_repo_repository_functions';
    
    protected $fillable = ['repository_id', 'function_name', 'modifier', 'params', 'set_model_id', 'author'];
    
    public function repository()
    {
        return $this->belongsTo('App\Models\Rpc\Repo\Repository', 'repository_id', 'id');
    }
    
    public function setModel()
    {
        return $this->belongsTo('App\Models\Rpc\DB\Model', 'set_model_id', 'id');
    }
    
    public function apiCall_list()
    {
        return $this->hasMany('App\Models\Rpc\Service\ApiCall', 'function_id', 'id');
    }
    
}
