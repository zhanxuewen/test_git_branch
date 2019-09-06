<?php

namespace App\Models\Database;

use App\Models\BaseModel;

class Column extends BaseModel
{
    protected $table = 'database_columns';

    public $timestamps = false;

    protected $fillable = ['group_id', 'column', 'info', 'is_available'];

    public function group()
    {
        return $this->belongsTo('App\Models\Database\Group', 'group_id', 'id');
    }

}
