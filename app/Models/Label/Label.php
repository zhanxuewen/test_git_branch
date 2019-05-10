<?php

namespace App\Models\Label;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Label extends BaseModel
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'label';

    protected $fillable = ['name', 'code', 'label_type_id', 'parent_id', 'level', 'power', 'is_available'];

    public function type()
    {
        return $this->belongsTo('App\Models\Label\Type', 'label_type_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Label\Label', 'parent_id', 'id');
    }

}
