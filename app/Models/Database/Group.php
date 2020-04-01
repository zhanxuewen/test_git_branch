<?php

namespace App\Models\Database;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends BaseModel
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'database_groups';

    public $timestamps = false;

    protected $fillable = ['code', 'type', 'parent_id', 'info'];

}
