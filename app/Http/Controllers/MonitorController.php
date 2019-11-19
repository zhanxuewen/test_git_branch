<?php

namespace App\Http\Controllers;

use App\Stores\Logs;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function table(Request $request)
    {
        $sub_days = $request->get('days', 14);
        $project = $request->get('project', 'core');
        $empty = $this->builder->setModel('tableIncrement')->where('project', $project)->groupBy('table')->havingRaw('max(rows) < 1000')->pluck('table');
//        $count = $this->builder->setModel('tableIncrement')->distinct()->count('created_date');
//        $sub_days = $count > 14 ? 14 : $count;
        $dates = json_encode($this->listSubDays($sub_days));
        $i = 0;
        $rows = $keys = [];
        $this->builder->setModel('tableIncrement')->selectRaw('`table`, group_concat(rows ORDER BY id) as _rows')
            ->where('project', $project)
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->whereNotIn('table', $empty)
            ->groupBy('table')->orderByRaw('max(rows) desc')
            ->chunk(10, function ($tables) use (&$i, &$rows, &$keys) {
                $rows[$i] = $tables->toJson();
                $keys[] = $i;
                $i++;
            });
        $keys = json_encode($keys);
        return view('monitor.table', compact('rows', 'keys', 'dates', 'sub_days', 'project'));
    }

    public function circleTable(Request $request)
    {
        $project = $request->get('project', 'core');
        $tables = $this->builder->setModel('tableIncrement')->where('project', $project)->distinct()->orderBy('table')->pluck('table');
        $table = $request->get('table', $tables[0]);
        $start = $request->filled('start') ? $request->get('start') : null;
        $start = is_null($start) || $this->earlyThan($request, 14) ? Carbon::today()->subDays(14)->toDateString() : $start;
        $db_a = \DB::table('monitor_table_increment')->selectRaw('created_date AS date, `rows`')
            ->where('project', $project)->where('table', $table)->where('created_date', '>=', $start);
        $db_b = \DB::table('monitor_table_increment')->selectRaw('DATE_ADD( created_date, INTERVAL 1 DAY ) AS date, `rows`')
            ->where('project', $project)->where('table', $table)->where('created_date', '>=', $start);
        $db_c = \DB::table(\DB::raw("({$db_a->toSql()}) AS a"))->mergeBindings($db_a)
            ->join(\DB::raw("({$db_b->toSql()}) AS b"), 'a.date', '=', 'b.date', 'left')->mergeBindings($db_b)
            ->selectRaw('a.date, a.rows AS this, b.rows AS last');
        $rate = "(CASE WHEN (c.last IS NULL OR c.last = 0) THEN 0.00 ELSE cast(c.this AS signed) - cast(c.last AS signed) END)";
        $circles = \DB::table(\DB::raw("({$db_c->toSql()}) AS c"))->mergeBindings($db_c)
            ->selectRaw("c.date, $rate AS rate")->get();
        $dates = json_encode($circles->pluck('date')->toArray());
        $circles = json_encode($circles->pluck('rate')->implode(','));
        return view('monitor.circle_table', compact('tables', 'start', 'table', 'dates', 'circles'));
    }

    public function device()
    {
        $count = $this->builder->setModel('deviceUsageAmount')->distinct()->count('created_date');
        $sub_days = $count > 14 ? 14 : $count;
        $dates = json_encode($this->listSubDays($sub_days));
        $keys = [];
        $i = 0;
        $rows = [];
        $this->builder->setModel('deviceUsageAmount')->selectRaw('`device`, group_concat(user_amount ORDER BY id) as _rows')
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->groupBy('device')->orderByRaw('max(user_amount) desc')
            ->chunk(10, function ($tables) use (&$i, &$rows, &$keys) {
                $rows[$i] = $tables->toJson();
                $keys[] = $i;
                $i++;
            });
        $keys = json_encode($keys);
        return view('monitor.device', compact('rows', 'keys', 'dates'));
    }

    public function order()
    {
        $count = $this->builder->setModel('orderIncrement')->distinct()->count('created_date');
        $sub_days = $count > 14 ? 14 : $count;
        $dates = json_encode($this->listSubDays($sub_days));
        $keys = [];
        $i = 0;
        $rows = [];
        $this->builder->setModel('orderIncrement')->selectRaw('`type` as _type, group_concat(count ORDER BY id) as _rows')
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->groupBy('type')->orderByRaw('max(count) desc')
            ->chunk(10, function ($tables) use (&$i, &$rows, &$keys) {
                $rows[$i] = $tables->toJson();
                $keys[] = $i;
                $i++;
            });
        $keys = json_encode($keys);
        return view('monitor.order', compact('rows', 'keys', 'dates'));
    }

    public function zabbix(Request $request)
    {
        $day = $request->get('day', 1);
        $group = $request->get('group', 'mysql_cpu');
        $time = $time = Carbon::now()->subDays($day)->format('YmdHis');
        $period = !strstr($day, '.') ? 86400 * $day : 3600 * $day * 10;
        $ids = [
            'mysql_cpu' => [18017, 18048, 30110],
            'mysql_operation' => [18008, 18039, 30101],
            'web_cpu' => [712, 721, 1179, 1725],
            'web_nginx_conn' => [707, 716, 1171, 1717]
        ];
        foreach ($ids[$group] as $item) {
            $data[] = 'https://zabbix.wxzxzj.com/chart2.php?graphid=' . $item . '&period=' . $period .
                '&stime=' . $time . '&isNow=1&profileIdx=web.graphs&profileIdx2=' . $item . '&width=847&sid=4cdb6042db7bf42c';
        }
        return view('monitor.zabbix', compact('data', 'day', 'group'));
    }

    public function throttle(Request $request)
    {
        $_section = $request->get('section', 'record');
        $_conn = $request->get('conn', 'online');
        $common = compact('_section', '_conn');
        if ($_section == 'record') return $this->getThrottleRecord($request, $common);
        if ($_section == 'log') return $this->getThrottleLog($request, $common);
        return abort('404');
    }

    protected function getThrottleRecord(Request $request, $common)
    {
        $date = Carbon::parse($request->get('date', date('Y-m-d')));
        if (!is_null($op = $request->get('op', null))) $date = $date->$op();
        $_key = 'throttle_record_' . $date->format('Ymd');
        $conn = $common['_conn'] == 'online' ? 'online' : 'analyze';
        $list = json_decode($this->getReadRedis($conn)->get($_key));
        $keys = $_tokens = $ids = [];
        if (empty($list)) $list = [];
        foreach ($list as &$item) {
            list($method, $uri, $token) = explode('|', $item);
            $key = $method . '|' . $uri;
            if (!in_array($key, $keys)) $keys[] = $key;
            list($token, $count) = explode('#', $token);
            if (!in_array($token, $_tokens)) $_tokens[] = $token;
            if (!strstr($token, '.')) $ids[] = $token;
            $item = ['method' => $method, 'uri' => $uri, 'token' => $token, 'count' => $count];
        }
        $count = [count($keys), count($_tokens)];
        $conn = $common['_conn'] == 'online' ? 'online' : 'dev';
        $accounts = empty($ids) ? [] : $this->fetchRows($this->getConnPdo('core', $conn)->query($this->list_accounts($ids)));
        $date = $date->toDateString();
        $compact = compact('count', 'accounts', 'list', 'date');
        return view('monitor.throttle.record', array_merge($compact, $common));
    }

    protected function getThrottleLog(Request $request, $common)
    {
        $date = Carbon::parse($request->get('date', date('Y-m-d')));
        if (!is_null($op = $request->get('op', null))) $date = $date->$op();
        $_key = 'throttle_log_' . $date->format('Ymd');
        $conn = $common['_conn'] == 'online' ? 'online' : 'analyze';
        $hash = $this->getReadRedis($conn)->hgetall($_key);
        $_keys = $list = $tokens = $ids = [];
        $_group = $request->get('group', 'token');
        if (empty($hash)) $hash = [];
        foreach ($hash as $hKey => $values) {
            list($method, $uri, $token) = explode('|', $hKey);
            $key = $method . '|' . $uri;
            if (!in_array($key, $_keys)) $_keys[] = $key;
            if (!in_array($token, $tokens)) $tokens[] = $token;
            if (!strstr($token, '.')) $ids[] = $token;
            $items = $this->getTime(json_decode($values, true));
            $_group == 'token' ? $list[$token][] = ['label' => $key, 'rows' => $items] : $list[$key][] = ['label' => $token, 'rows' => $items];
        }
        $count = [count($_keys), count($tokens)];
        foreach ($list as $k => &$item) {
            $item = json_encode($item);
        }
        $keys = json_encode(array_keys($list));
        $conn = $common['_conn'] == 'online' ? 'online' : 'dev';
        $accounts = empty($ids) ? [] : $this->fetchRows($this->getConnPdo('core', $conn)->query($this->list_accounts($ids)));
        $date = $date->toDateString();
        $times = json_encode(array_keys($this->getTimes('day')));
        $compact = compact('count', 'accounts', 'list', 'keys', 'date', 'times', '_group');
        return view('monitor.throttle.log', array_merge($compact, $common));
    }

    public function schedule(Request $request)
    {
        $Store_Log = new Logs();
        $day = $request->get('day', 1);
        list($total, $list) = $Store_Log->schedule($day);
        return view('monitor.schedule', compact('total', 'day', 'list'));
    }

    protected function getTime($values)
    {
        $times = $this->getTimes('day');
        foreach ($values as $value) {
            $hour = (int)(explode(':', $value)[0]);
            $times[$hour] += 1;
        }
        return array_values($times);
    }

    protected function getTimes($type)
    {
        $types = ['day' => 24, 'hour' => 60];
        $times = [];
        for ($i = 1; $i <= $types[$type]; $i++) {
            $times[$i] = 0;
        }
        return $times;
    }

    protected function list_accounts($ids)
    {
        return "SELECT id, nickname, user_type_id, school_id FROM user_account WHERE id IN (" . implode(',', $ids) . ")";
    }

    protected function fetchRows($rows)
    {
        $record = [];
        if (empty($rows)) return $record;
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $item) {
                if (!is_numeric($key)) $data[$key] = $item;
            }
            $record[$data['id']] = $data;
        }
        return $record;
    }

    protected function listSubDays($sub_days)
    {
        $subDay = Carbon::now()->subDays($sub_days);
        $days = [];
        for ($i = 0; $i < $sub_days; $i++) {
            $days[] = $subDay->addDay()->toDateString();
        }
        return $days;
    }

    protected function earlyThan(Request $request, $days)
    {
        return Carbon::parse($request->get('start'))->lessThan(Carbon::today()->subDays($days));
    }
}
