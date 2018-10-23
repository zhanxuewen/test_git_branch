<?php

namespace App\Models\Monitor;

use App\Models\BaseModel;

class DeviceUsageAmount extends BaseModel
{
    protected $table = 'monitor_device_usage_amount';
    
    public $timestamps = false;
    
    protected $fillable = ['device', 'user_amount', 'created_date'];
    
}
