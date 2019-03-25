<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function table(Request $request)
    {
        $sub_days = $request->get('days', 14);
        $empty = $this->builder->setModel('tableIncrement')->groupBy('table')->havingRaw('max(rows) < 1000')->pluck('table');
//        $count = $this->builder->setModel('tableIncrement')->distinct()->count('created_date');
//        $sub_days = $count > 14 ? 14 : $count;
        $dates = json_encode($this->listSubDays($sub_days));
        $keys = [];
        $i = 0;
        $rows = [];
        $this->builder->setModel('tableIncrement')->selectRaw('`table`, group_concat(rows ORDER BY id) as _rows')
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->whereNotIn('table', $empty)
            ->groupBy('table')->orderByRaw('max(rows) desc')
            ->chunk(10, function ($tables) use (&$i, &$rows, &$keys) {
                $rows[$i] = $tables->toJson();
                $keys[] = $i;
                $i++;
            });
        $keys = json_encode($keys);
        return view('monitor.table', compact('rows', 'keys', 'dates', 'sub_days'));
    }

    public function circleTable(Request $request)
    {
        $tables = $this->builder->setModel('tableIncrement')->distinct()->orderBy('table')->pluck('table');
        $table = $request->get('table', $tables[0]);
        $start = $request->filled('start') ? $request->get('start') : null;
        $start = is_null($start) || $this->earlyThan($request, 14) ? Carbon::today()->subDays(14)->toDateString() : $start;
        $db_a = \DB::table('monitor_table_increment')->selectRaw('created_date AS date, `rows`')
            ->where('table', $table)->where('created_date', '>=', $start);
        $db_b = \DB::table('monitor_table_increment')->selectRaw('DATE_ADD( created_date, INTERVAL 1 DAY ) AS date, `rows`')
            ->where('table', $table)->where('created_date', '>=', $start);
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
            $data[] = 'http://zabbix.vanthink.cn:3780/chart2.php?graphid=' . $item . '&period=' . $period .
                '&stime=' . $time . '&isNow=1&profileIdx=web.graphs&profileIdx2=' . $item . '&width=847&sid=63fd9a3fd67d7a39';
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
        $accounts = empty($ids) ? [] : $this->fetchRows($this->getPdo($conn)->query($this->list_accounts($ids)));
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
            $items = $this->getTime(unserialize($values));
            $_group == 'token' ? $list[$token][$key] = $items : $list[$key][$token] = $items;
        }
        $count = [count($_keys), count($tokens)];
        foreach ($list as $k => &$item) {
            $item = json_encode($item);
        }
        $keys = json_encode(array_keys($list));
        $conn = $common['_conn'] == 'online' ? 'online' : 'dev';
        $accounts = empty($ids) ? [] : $this->fetchRows($this->getPdo($conn)->query($this->list_accounts($ids)));
        $date = $date->toDateString();
        $compact = compact('count', 'accounts', 'list', 'keys', 'date', '_group');
        return view('monitor.throttle.log', array_merge($compact, $common));
    }

    public function schedule(Request $request)
    {
        $day = $request->get('day', 1);
        $now = (int)(time() . '000');
        $ago = (int)((time() - 86400 * $day) . '000');
        $url = 'https://kibana.wxzxzj.com/elasticsearch/_msearch';
        $index = ['index' => ['logstash-managedown-schedule-*'], 'ignore_unavailable' => true, 'preference' => 1548307157438];
        $index = json_encode($index);
        $config = ['version' => true, 'size' => 5000, 'sort' => [['@timestamp' => ['order' => 'desc', 'unmapped_type' => 'boolean']]], '_source' => ['excludes' => []], 'aggs' => ['2' => ['date_histogram' => ['field' => '@timestamp', 'interval' => '30m', 'time_zone' => "Asia/Shanghai", 'min_doc_count' => 1]]], 'stored_fields' => ['*'], 'script_fields' => [], 'docvalue_fields' => ['@timestamp'], 'query' => ['bool' => ['must' => [['match_all' => []], ['range' => ['@timestamp' => ['gte' => $ago, 'lte' => $now, 'format' => 'epoch_millis']]]], 'filter' => [], 'should' => [], 'must_not' => []]], 'highlight' => ['pre_tags' => ['@kibana-highlighted-field@'], 'post_tags' => ['@/kibana-highlighted-field@'], 'fields' => ['*' => []], 'fragment_size' => 2147483647]];
        $config = str_replace(['\/', 'script_fields":[]', 'match_all":[]', '*":[]'], ['/', 'script_fields":{}', 'match_all":{}', '*":{}'], json_encode($config));
        $data = $this->scheduleCurlPost($url, "{$index}\n{$config}\n");
        $hits = json_decode($data)->responses[0]->hits;
        $total = $hits->total;
        $list = [];
        $hits = $total == 0 ? [] : array_reverse($hits->hits);
        foreach ($hits as $hit) {
            preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $message = $hit->_source->message, $match);
            $list[] = ['time' => $match[0], 'message' => $message];
        }
        return view('monitor.schedule', compact('total', 'day', 'list'));
    }

    protected function getTime($values)
    {
        $array = [];
        foreach ($values as $value) {
            $item = json_decode($value, true);
            $hour = (int)(explode(':', $item['now_at'])[0]);
            $array[$hour][] = $item;
        }
        return ['items' => $array, 'times' => array_keys($array)];
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

    protected function scheduleCurlPost($url, $data)
    {
        $token = 'Authorization: Basic dmFudGhpbms6NUUlcWQ4bXR3UUEkTTdSYg==';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['kbn-version: 6.2.4', 'content-type: application/x-ndjson', $token]);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
