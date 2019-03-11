<?php

namespace App\Http\Controllers;

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
        $logs = $this->handleMysqlLog($this->getMysqlLog($_day));
        $sql_s = [];
        $times = [];
        $bad_s = [];
        foreach ($logs as $log) {
            if (empty($log)) continue;
            $log = trim(str_replace('#', '', $log));
            $date = substr($log, 0, 19);
            if (preg_match('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', $date)) $log = substr($log, 19);
            if (preg_match('/\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d/', substr($log, -19))) $log = substr($log, 0, -19);
            if (count($exp = explode('Query_time:', $log)) == 1) {
                $bad_s[] = $log;
                continue;
            }
            list($sql, $other) = $exp;
            if (strstr($sql, '!40001 SQL_NO_CACHE')) continue;
            if (count($exp = explode('User@Host:', $other)) == 1) {
                $bad_s[] = $log;
                continue;
            }
            list($time, $other) = $exp;
            list($user, $host) = explode('@', $other);
            if (strstr($host, '10.30.176.166')) continue;
            $times[] = trim($time);
            $sql_s[] = ['sql' => trim($sql), 'user' => trim($user), 'host' => trim($host), 'date' => $date];
        }
        arsort($times);
        return view('slow.mysql', compact('times', 'sql_s', 'bad_s', '_day', '_sec'));
    }

    protected function handleMysqlLog($log)
    {
        preg_match('/<tbody>(.*)<\/tbody>/', $log, $match);
        if (!isset($match[1])) dd($log);
        $preg_time = '\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d';
        $_log = preg_replace('/Time: \d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d.\d{6}Z/', 'SplitTime', $match[1]);
        $_log = preg_replace('/<[\/]?pre>/', '', $_log);
        $_log = preg_replace('/<[\/]?t[rd]>/', '', $_log);
        $_log = preg_replace('/<td class="nowrap">/', '', $_log);
        $_log = preg_replace('/Id: \d{9}/', '', $_log);
        $_log = preg_replace('/线上MySQL\d: MySQL-slow log/', '', $_log);
        $_log = preg_replace('/Rows_examined: \d{7}' . $preg_time . '/', '', $_log);
        $_log = preg_replace('/' . $preg_time . 'SET timestamp=\d{10};' . $preg_time . '/', '', $_log);
        $_log = preg_replace('/Lock_time: \d.\d{6} Rows_sent: \d+/', '', $_log);
        $_log = preg_replace('/Rows_examined: \d+' . $preg_time . '/', '', $_log);
        return explode('# SplitTime', $_log);
    }

    protected function setDay($day, $id_x)
    {
        $time = Carbon::now()->subDays($day)->format('YmdHis');
        $data = ['idx' => 'web.item.graph', 'idx2' => $id_x, 'period' => 86400 * $day, 'stime' => $time, 'isNow' => 1];
        $url = 'http://zabbix.vanthink.cn:3780/zabbix.php?sid=4ff0c881f59d64c0&action=timeline.update&output=ajax';
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
        $url = 'http://zabbix.vanthink.cn:3780/history.php?sid=4ff0c881f59d64c0&form_refresh=1&'
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
        $items = 'itemids%5B152351%5D=152351&itemids%5B152040%5D=152040&itemids%5B252547%5D=252547';
        $time = Carbon::now()->subDays($day)->format('YmdHis');
        $timestamp = Carbon::now()->timestamp . '000';
        $url = 'http://zabbix.vanthink.cn:3780/jsrpc.php?sid=63fd9a3fd67d7a39&type=9&method=screen.get&timestamp=' . $timestamp . '&mode=2&screenid=&groupid=&hostid=0&pageFile=history.php&profileIdx=web.item.graph&profileIdx2=152351&updateProfile=&screenitemid=&period=' . (86400 * $day) . '&stime=' . $time . '&isNow=1&resourcetype=17&' . $items . '&action=showvalues&filter=&filter_task=0&mark_color=1';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$this->getZabbixToken()]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
