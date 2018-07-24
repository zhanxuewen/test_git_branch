<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Input;

class RpcController extends Controller
{
    public function slow()
    {
        $_day   = Input::get('day', 1);
        $_count = Input::get('count', 5);
        $_sec   = Input::get('sec', 10);
        $this->setDay($_day);
        $log  = $this->getLog();
        $_log = str_replace('&quot;', '"', $log);
        preg_match_all('/(method":"[a-zA-Z_0-9]+)/', $_log, $match);
        $res     = [];
        $methods = [];
        foreach ($match[1] as $log) {
            $log       = str_replace('method":"', '', $log);
            $methods[] = $log;
            $res[$log] = array_key_exists($log, $res) ? $res[$log] + 1 : 1;
        }
        arsort($res);
        preg_match_all('/(time":\d+.\d+\})/', $_log, $match);
        $time = [];
        foreach ($match[1] as $key => $log) {
            $func = $methods[$key];
            preg_match('/:(\d+).\d/', $log, $sec_);
            $sec         = $sec_[1];
            $time[$func] = array_key_exists($func, $time) ? ($time[$func] < $sec ? $sec : $time[$func]) : $sec;
        }
        arsort($time);
        return view('rpc.index', compact('res', 'time', '_day', '_count', '_sec'));
    }
    
    protected function setDay($day = 1)
    {
        $time = Carbon::now()->subDays($day)->format('YmdHis');
        $data = ['idx' => 'web.item.graph', 'idx2' => 33469, 'period' => 86400 * $day, 'stime' => $time, 'isNow' => 1];
        $url  = 'http://zabbix.vanthink.cn:3780/zabbix.php?sid=4ff0c881f59d64c0&action=timeline.update&output=ajax';
        $ch   = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie:zbx_sessionid=6b594637293a09024ff0c881f59d64c0']);
        curl_exec($ch);
        curl_close($ch);
    }
    
    protected function getLog()
    {
        $url = 'http://zabbix.vanthink.cn:3780/history.php?sid=4ff0c881f59d64c0&form_refresh=1&itemids%5B33469%5D=33469&itemids%5B33470%5D=33470&itemids%5B33475%5D=33475&filter_task=0&filter=&action=showvalues&plaintext=%E7%BA%AF%E6%96%87%E5%AD%97';
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie:zbx_sessionid=6b594637293a09024ff0c881f59d64c0']);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
