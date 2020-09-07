<?php

namespace App\Http\Controllers;

use ES;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Elasticsearch\Common\Exceptions\Missing404Exception;

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
            if (strstr($log->message, 'slowLog.INFO: debug')) continue;
            list($at, $info) = explode(' slowLog.INFO: info ', $log->message);
            $at = trim($at, '[]');
            if ($start->gt($at)) continue;
            $json = json_decode(str_replace(' []', '', $info), true);
            $method = $json['method'];
            $time = round($json['time'], 3);
            $methods[$method][] = ['time' => $time, 'at' => $at, 'params' => count($json['params']) == 1 ? $json['params'][0] : $json['params']];
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
            $h_name = $this->showHostname($log->beat['hostname']);
            list($date, $user, $host, $time, $sql) = $this->handleMysqlLog($log->message);
            if ($start->gt($date)) continue;
            $times[] = $time;
            $sql_s[] = ['sql' => trim($sql), 'user' => $user, 'host' => $host, 'date' => $date, 'h_name' => $h_name];
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
        $date = Carbon::parse(str_replace('T', ' ', $match[0]));
        $date = $date->toDateTimeString();
//        $date = $date->addHours(8)->toDateTimeString();
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
            try {
                ES::table($table)->count();
            } catch (Missing404Exception $e) {
                continue;
            }
            if ($name == 'mysql-slow') {
                $count = ES::table($table)->count();
                $logs = array_merge($logs, ES::table($table)->take($count)->get()->toArray());
            } else {
                $count = $this->queryRpcSlow($table)->count();
                if ($count > 300) $count = 300;
                $_logs = $this->queryRpcSlow($table)->select(['time', 'message'])->take($count)->get()->toArray();
                $logs = array_merge($logs, $_logs);
            }
        }
        return [$logs, $d];
    }

    protected function showHostname($h_name)
    {
        $name = explode('-', $h_name)[0];
        if ($name == 'wxzxzj') return '<span class="label" style="background-color: #5775a7">' . $name . '</span>';
        if ($name == 'kids') return '<span class="label" style="background-color: #2bc271">' . $name . '</span>';
        return '<span class="label" style="background-color: #a38732">' . $name . '</span>';
    }

    protected function queryRpcSlow($table)
    {
        return ES::table($table)->whereMatch('message', 'slowLog.INFO: info')->whereMatch('message', 'time');
    }

    protected function greater($a, $b)
    {
        return $a > $b ? $a : $b;
    }
}
