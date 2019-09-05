<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function getConfig()
    {
        $configs = $this->builder->setModel('config')->get()->toArray();
        $conn_s = $this->getConnArray();
        $conn = $this->getRedis('analyze')->get($this->getUser('id') . '_sql_analyze_conn');
        $perPage = $this->getRedis('analyze')->get($this->getUser('id') . '_per_page') ?: 30;
        return view('system.config', compact('configs', 'conn_s', 'conn', 'perPage'));
    }

    public function postConfig(Request $request)
    {
        $_type = $request->get('config_type');
        if ($_type == 'personal') $this->personalConfig($request);
        return redirect('system/config');
    }

    protected function personalConfig(Request $request)
    {
        $redis = $this->getRedis('analyze');
        $user_id = $this->getUser('id');
        $keys = ['_sql_analyze_conn' => 'conn', '_per_page' => 'perPage'];
        foreach ($keys as $key => $param) {
            $redis->setex($user_id . $key, 60 * 60 * 24, $request->get($param));
        }

    }

    protected function getConnArray()
    {
        return [
            'core-dev' => 'Core Dev',
            'core-test' => 'Core Test',
            'learning-dev' => 'Learning Dev',
            'word_short-dev' => 'WordShorthand Dev'
        ];
    }

}
