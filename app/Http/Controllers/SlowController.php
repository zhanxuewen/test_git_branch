<?php

namespace App\Http\Controllers;

use ES;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SlowController extends Controller
{
    public function rpc(Request $request)
    {
        $_day = $request->get('day', 1);
        $_count = $request->get('count', 5);
        $_sec = $request->get('sec', 10);
        $this->setDay($_day, 33469);
        $log = $this->getLog('rpc');
        $_log = str_replace('&quot;', '"', $log);
        preg_match_all('/(method":"[a-zA-Z_0-9]+)/', $_log, $match);
        $res = [];
        $methods = [];
        foreach ($match[1] as $log) {
            $log = str_replace('method":"', '', $log);
            $methods[] = $log;
            $res[$log] = array_key_exists($log, $res) ? $res[$log] + 1 : 1;
        }
        arsort($res);
        preg_match_all('/(time":\d+.\d+\})/', $_log, $match);
        $time = [];
        foreach ($match[1] as $key => $log) {
            $func = $methods[$key];
            preg_match('/:(\d+).\d/', $log, $sec_);
            $sec = $sec_[1];
            $time[$func] = array_key_exists($func, $time) ? ($time[$func] < $sec ? $sec : $time[$func]) : $sec;
        }
        arsort($time);
        return view('slow.rpc', compact('res', 'time', '_day', '_count', '_sec'));
    }

    public function mysql(Request $request)
    {
        $_day = $request->get('day', 1);
        $_sec = $request->get('sec', 10);
        list($logs, $start) = $this->getMysqlLog($_day);
        $sql_s = [];
        $times = [];
        $bad_s = [];
        foreach ($logs as $log) {
            if (empty($log)) continue;
            if (!strstr($log->message, 'User@Host')) continue;
            list($date, $user, $host, $time, $sql) = $this->handleMysqlLog($log->message);
            if ($start->gt($date)) continue;
//            if (strstr($sql, '!40001 SQL_NO_CACHE')) continue;
//            if (strstr($host, '10.30.176.166')) continue;
            $times[] = $time;
            $sql_s[] = ['sql' => trim($sql), 'user' => $user, 'host' => $host, 'date' => $date];
        }
        arsort($times);
        return view('slow.mysql', compact('times', 'sql_s', 'bad_s', '_day', '_sec'));
    }

    protected function handleMysqlLog($log)
    {
        $log = preg_replace('/SET timestamp=\d+;\n/', '', $log);
        $items = explode("\n", $log);
        list($date, $user, $time) = $items;
        unset($items[0], $items[1], $items[2]);
        $sql = implode("\n", $items);
        preg_match('/\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d/', $date, $match);
        $date = str_replace('T', ' ', $match[0]);
        $user = explode(':', str_replace('Id', '', $user))[1];
        list($user, $host) = explode('@', $user);
        $time = str_replace('Lock_time', '', explode(':', $time)[1]);
        return [$date, trim($user), trim($host), trim($time), $sql];
    }

    protected function setDay($day, $id_x)
    {
        $time = Carbon::now()->subDays($day)->format('YmdHis');
        $data = ['idx' => 'web.item.graph', 'idx2' => $id_x, 'period' => 86400 * $day, 'stime' => $time, 'isNow' => 1];
        $url = 'http://zabbix.vanthink.cn/zabbix.php?sid=4ff0c881f59d64c0&action=timeline.update&output=ajax';
        $ch = curl_init($url);
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
        ];
        $url = 'http://zabbix.vanthink.cn/history.php?sid=4ff0c881f59d64c0&form_refresh=1&'
            . $params[$key] .
            '&filter_task=0&filter=&action=showvalues&plaintext=%E7%BA%AF%E6%96%87%E5%AD%97';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$this->getZabbixToken()]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    protected function getMysqlLog($day)
    {
        $d = Carbon::now();
        $dates = [$d->format('Ymd')];
        for ($i = 1; $i <= $day; $i++) {
            $dates[] = $d->subDay()->format('Ymd');
        }
        $logs = [];
        foreach ($dates as $date) {
            $table = 'logstash-mysql-slow-' . $date;
            $count = ES::table($table)->count();
            $logs = array_merge($logs, ES::table($table)->take($count)->get()->toArray());
        }
        return [$logs, $d];
    }
}
