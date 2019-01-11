<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function table()
    {
        $empty = $this->builder->setModel('tableIncrement')->groupBy('table')->havingRaw('max(rows) < 1000')->pluck('table');
//        $count = $this->builder->setModel('tableIncrement')->distinct()->count('created_date');
//        $sub_days = $count > 14 ? 14 : $count;
        $sub_days = 14;
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
        return view('monitor.table', compact('rows', 'keys', 'dates'));
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
