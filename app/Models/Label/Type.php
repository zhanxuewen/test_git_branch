<?php

namespace App\Models\Label;

use App\Models\BaseModel;

class Type extends BaseModel
{
    protected $table = 'label_type';

    public $timestamps = false;

    protected $fillable = ['code', 'name'];

}
