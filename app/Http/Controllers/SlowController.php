<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Input;

class SlowController extends Controller
{
    public function rpc()
    {
        $_day   = Input::get('day', 1);
        $_count = Input::get('count', 5);
        $_sec   = Input::get('sec', 10);
        $this->setDay($_day, 33469);
        $log  = $this->getLog('rpc');
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
        return view('slow.rpc', compact('res', 'time', '_day', '_count', '_sec'));
    }
    
    public function mysql()
    {
        $_day = Input::get('day', 1);
        $_sec = Input::get('sec', 10);
        $this->setDay($_day, 29168);
        $log   = $this->getLog('mysql');
        $logs  = $this->handleMysqlLog($log);
        $sql_s = [];
        $times = [];
        $bad_s = [];
        foreach ($logs as $log) {
            if (empty($log)) continue;
            preg_match('/(\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d \d+ )/', $log, $match);
            $date = preg_replace('/ \d+ /', '', $match[1]);
            $log  = preg_replace('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d \d+ /', '', $log);
            $log  = preg_replace('/#/', '', $log);
            if (!strstr($log, 'Query_time: ')) {
                $bad_s[] = $log;
                continue;
            }
            list($sql, $other) = explode('Query_time: ', $log);
            if (strstr($sql, '!40001 SQL_NO_CACHE')) continue;
            list($time, $other) = explode(' User@Host: ', $other);
            list($user, $host) = explode(' @ ', $other);
            if (strstr($host, '10.30.176.166')) continue;
            $times[] = trim($time);
            $sql     = preg_replace('/;|(<br>)/', '', trim($sql));
            $sql_s[] = ['sql' => trim($sql), 'user' => trim($user), 'host' => $host, 'date' => $date];
        }
        arsort($times);
        return view('slow.mysql', compact('times', 'sql_s', 'bad_s', '_day', '_sec'));
    }
    
    protected function handleMysqlLog($log)
    {
        $_log = str_replace('&quot;', '"', $log);
        $_log = preg_replace('/ \d+ +\d+:\d+:\d+<br>/', '', $_log);
        $_log = preg_replace('/SET timestamp=\d+/', '', $_log);
        $_log = preg_replace('/Lock_time: \d+.\d+ Rows_sent: \d+  Rows_examined: \d+<br>/', '', $_log);
        $_log = preg_replace('/ Id: \d+<br>/', '', $_log);
        preg_match_all('/<pre>(.*)<\/pre>/', $_log, $match);
        return explode('# Time:', $match[1][0]);
    }
    
    protected function setDay($day, $id_x)
    {
        $time = Carbon::now()->subDays($day)->format('YmdHis');
        $data = ['idx' => 'web.item.graph', 'idx2' => $id_x, 'period' => 86400 * $day, 'stime' => $time, 'isNow' => 1];
        $url  = 'http://zabbix.vanthink.cn:3780/zabbix.php?sid=4ff0c881f59d64c0&action=timeline.update&output=ajax';
        $ch   = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$this->getZabbixToken()]);
        curl_exec($ch);
        curl_close($ch);
    }
    
    protected function getLog($key)
    {
        $params = [
            'rpc' => 'itemids%5B33469%5D=33469&itemids%5B33470%5D=33470&itemids%5B33475%5D=33475',
            'mysql' => 'itemids%5B29168%5D=29168&itemids%5B29169%5D=29169&itemids%5B30673%5D=30673'
        ];
        $url    = 'http://zabbix.vanthink.cn:3780/history.php?sid=4ff0c881f59d64c0&form_refresh=1&'
            .$params[$key].
            '&filter_task=0&filter=&action=showvalues&plaintext=%E7%BA%AF%E6%96%87%E5%AD%97';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$this->getZabbixToken()]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
