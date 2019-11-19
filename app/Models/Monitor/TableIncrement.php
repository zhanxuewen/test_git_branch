<?php

namespace App\Models\Monitor;

use App\Models\BaseModel;

class TableIncrement extends BaseModel
{
    protected $table = 'monitor_table_increment';

    public $timestamps = false;

    protected $fillable = ['project', 'table', 'rows', 'auto_increment_id', 'level', 'created_date'];

}
