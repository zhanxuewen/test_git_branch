<?php

namespace App\Models\Monitor;

use App\Models\BaseModel;

class OrderIncrement extends BaseModel
{
    protected $table = 'monitor_order_increment';

    public $timestamps = false;

    protected $fillable = ['type', 'count', 'created_date'];

}
