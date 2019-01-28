<?php

namespace App\Models\Rpc;

use App\Models\BaseModel;

class TcpServer extends BaseModel
{
    protected $table = 'tcp_server';

    protected $fillable = ['alias', 'class_name', 'instance_id', 'instance_type'];

}
