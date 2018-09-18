<?php

namespace App\Models\Rpc\Repo;

use App\Models\BaseModel;

class Repository extends BaseModel
{
    protected $table = 'rpc_repo_repository';
    
    protected $fillable = ['code', 'module_id', 'class_name'];
    
    public function module()
    {
        return $this->belongsTo('App\Models\Rpc\DB\Module', 'module_id', 'id');
    }
    
    public function functions_list()
    {
        return $this->hasMany('App\Models\Rpc\Repo\Functions', 'repository_id', 'id');
    }
    
}
