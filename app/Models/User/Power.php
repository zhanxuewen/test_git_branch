<?php

namespace App\Models\User;

use App\Models\BaseModel;

class Power extends BaseModel
{
    protected $table = 'user_power';

    protected $fillable = ['group_label_id', 'label', 'code', 'route'];

    public function groupLabel()
    {
        return $this->belongsTo('App\Models\Label\Label', 'group_label_id', 'id');
    }

}