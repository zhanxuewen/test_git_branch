<?php

namespace App\Models\Label;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Label extends BaseModel
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'label';

    protected $fillable = ['name', 'code', 'is_active', 'label_type_id', 'parent_id', 'level', 'power'];

    public function type()
    {
        return $this->belongsTo('App\Models\Label\Type', 'label_type_id', 'id');
    }

    public function labelSelf()
    {
        return $this->belongsTo('App\Models\Label\Label', 'parent_id', 'id');
    }

}
