<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function getConfig()
    {
        $configs = $this->builder->setModel('config')->get()->toArray();
        $conn = $this->getRedis('analyze')->get($this->getUser('id') . '_sql_analyze_conn');
        return view('system.config', compact('configs', 'conn'));
    }

    public function postConfig(Request $request)
    {
        $_type = $request->get('config_type');
        if ($_type == 'personal') $this->personalConfig($request);
        return redirect('system/config');
    }

    protected function personalConfig(Request $request)
    {
        $conn = $request->get('conn');
        $redis = $this->getRedis('analyze');
        $user_id = $this->getUser('id');
        $key = $user_id . '_sql_analyze_conn';
        $redis->setex($key, 60 * 60 * 24, $conn);
    }

}
