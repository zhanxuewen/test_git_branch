<?php

namespace App\Models\Monitor;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends BaseModel
{

    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'branch';
    protected $hidden = ['deleted_at'];

    protected $fillable = ['group', 'sub_group', 'project', 'branch', 'url', 'label', 'extra', 'is_available'];

}
