<?php

namespace App\Models\Database;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Column extends BaseModel
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'database_columns';

    public $timestamps = false;

    protected $fillable = ['group_id', 'column', 'info', 'is_available'];

    public function group()
    {
        return $this->belongsTo('App\Models\Database\Group', 'group_id', 'id');
    }

}
