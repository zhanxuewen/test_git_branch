<?php

namespace App\Models\Rpc\DB;

use App\Models\BaseModel;

class Model extends BaseModel
{
    protected $table = 'rpc_db_module_model';
    
    protected $fillable = ['module_id', 'code', 'alias', 'class_name', 'label', 'table', 'fillable', 'timestamps', 'use_soft_deletes'];
    
    public function module()
    {
        return $this->belongsTo('App\Models\Rpc\DB\Module', 'module_id', 'id');
    }
    
    public function relation_list()
    {
        return $this->hasMany('App\Models\Rpc\DB\Relation', 'model_id', 'id');
    }
    
    public function repo_function_list()
    {
        return $this->hasMany('App\Models\Rpc\Repo\Functions', 'set_model_id', 'id');
    }
    
}
