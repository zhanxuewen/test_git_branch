<?php

namespace App\Models\Database;

use App\Models\BaseModel;

class Group extends BaseModel
{
    protected $table = 'database_groups';

    public $timestamps = false;

    protected $fillable = ['code', 'name', 'parent_id', 'is_available'];

}
