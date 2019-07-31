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
        list($logs, $start) = $this->getLog('rpc_server', $_day);
        $counts = [];
        $methods = [];
        $times = [];
        foreach ($logs as $log) {
            if ($start->gt($log->time)) continue;
            $log = str_replace(['info ', ' []'], ['', ''], $log->msg);
            $log = json_decode($log, true);
            $method = $log['method'];
            $time = round($log['time'], 3);
            $methods[$method][] = ['time' => $time, 'params' => count($log['params']) == 1 ? $log['params'][0] : $log['params']];
            $counts[$method] = array_key_exists($method, $counts) ? $counts[$method] + 1 : 1;
            $times[$method] = array_key_exists($method, $times) ? $this->greater($times[$method], $time) : $time;
        }
        arsort($counts);
        arsort($times);
        return view('slow.rpc', compact('methods', 'counts', 'times', '_day', '_count', '_sec', 'start'));
    }

    public function mysql(Request $request)
    {
        $_day = $request->get('day', 1);
        $_sec = $request->get('sec', 10);
        list($logs, $start) = $this->getLog('mysql-slow', $_day);
        $sql_s = [];
        $times = [];
        foreach ($logs as $log) {
            if (empty($log)) continue;
            if (!strstr($log->message, 'User@Host')) continue;
            list($date, $user, $host, $time, $sql) = $this->handleMysqlLog($log->message);
            if ($start->gt($date)) continue;
            $times[] = $time;
            $sql_s[] = ['sql' => trim($sql), 'user' => $user, 'host' => $host, 'date' => $date];
        }
        arsort($times);
        return view('slow.mysql', compact('times', 'sql_s', '_day', '_sec'));
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

    protected function getLog($name, $day)
    {
        $d = Carbon::now();
        $dates = [$d->format('Ymd')];
        for ($i = 1; $i <= $day; $i++) {
            $dates[] = $d->subDay()->format('Ymd');
        }
        $logs = [];
        foreach ($dates as $date) {
            $table = 'logstash-' . $name . '-' . $date;
            if ($name == 'mysql-slow') {
                $count = ES::table($table)->count();
                $logs = array_merge($logs, ES::table($table)->take($count)->get()->toArray());
            } else {
                $count = ES::table($table)->whereMatch('env', 'slowLog')->count();
                $logs = array_merge($logs, ES::table($table)->whereMatch('env', 'slowLog')->take($count)->get()->toArray());
            }
        }
        return [$logs, $d];
    }

    protected function greater($a, $b)
    {
        return $a > $b ? $a : $b;
    }
}
