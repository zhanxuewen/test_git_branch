<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $table = $this->getRecord('tableIncrement', 'table', 'table', 'rows');
        $device = $this->getRecord('deviceUsageAmount', 'device', 'device', 'user_amount');
        $circle = $this->getCircle('tableIncrement', 'circle', 'monitor_table_increment');
        $data = array_merge_recursive($table, $device, $circle);
        return view('dashboard.dashboard', $data);
    }

    protected function getRecord($model, $label, $item, $count)
    {
        $sub_days = $this->getSubDays($model);
        $dates = json_encode($this->listSubDays($sub_days));
        $rows = $this->getRows($model, $sub_days, $item, $count);
        return ['rows' => [$label => $rows], 'dates' => [$label => $dates]];
    }

    protected function getRows($model, $sub_days, $item, $count)
    {
        return $this->builder->setModel($model)->selectRaw("`$item` as item, group_concat($count) as _count")
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->groupBy($item)->orderByRaw("max($count) desc")->take(10)->get()->toJson();
    }

    protected function getCircle($model, $label, $table_name)
    {
        $sub_days = $this->getSubDays($model);
        $dates = json_encode($this->listSubDays($sub_days));
        $start = Carbon::now()->subDays($sub_days)->toDateString();
        $tables = \DB::table($table_name)->where('created_date', date('Y-m-d'))->orderBy('rows', 'desc')->take(10)->pluck('table');
        $rows = [];
        foreach ($tables as $table) {
            $db_a = \DB::table($table_name)->selectRaw('created_date AS date, `rows`')
                ->where('table', $table)->where('created_date', '>=', $start);
            $db_b = \DB::table($table_name)->selectRaw('DATE_ADD(created_date, INTERVAL 1 DAY) AS date, `rows`')
                ->where('table', $table)->where('created_date', '>=', $start);
            $db_c = \DB::table(\DB::raw("({$db_a->toSql()}) AS a"))->mergeBindings($db_a)
                ->join(\DB::raw("({$db_b->toSql()}) AS b"), 'a.date', '=', 'b.date', 'left')->mergeBindings($db_b)
                ->selectRaw('a.date, a.rows AS this, b.rows AS last');
            $rate = "(CASE WHEN (c.last IS NULL OR c.last = 0) THEN 0.00 ELSE cast(c.this AS signed) - cast(c.last AS signed) END)";
            $circles = \DB::table(\DB::raw("({$db_c->toSql()}) AS c"))->mergeBindings($db_c)
                ->selectRaw("$rate AS rate")->get();
            $rows[] = ['item' => $table, '_count' => $circles->pluck('rate')->implode(',')];
        }
        return ['rows' => [$label => json_encode($rows)], 'dates' => [$label => $dates]];
    }


    protected function getSubDays($model)
    {
        $count = $this->builder->setModel($model)->distinct()->count('created_date');
        return $count > 14 ? 14 : $count;
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
}
