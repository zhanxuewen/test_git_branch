<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $count    = $this->builder->setModel('tableIncrement')->distinct()->count('created_date');
        $sub_days = $count > 14 ? 14 : $count;
        $dates    = json_encode($this->listSubDays($sub_days));
        $keys     = [];
        $i        = 0;
        $rows     = [];
        $this->builder->setModel('tableIncrement')->selectRaw('`table`, group_concat(rows) as _rows')
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->groupBy('table')->orderBy('rows', 'desc')
            ->chunk(10, function ($tables) use (&$i, &$rows, &$keys) {
                $rows[$i] = $tables->toJson();
                $keys[]   = $i;
                $i++;
            });
        $keys = json_encode($keys);
        return view('dashboard.dashboard', compact('rows', 'keys', 'dates'));
    }
    
    protected function listSubDays($sub_days)
    {
        $subDay = Carbon::now()->subDays($sub_days);
        $days   = [];
        for ($i = 0; $i < $sub_days; $i++) {
            $days[] = $subDay->addDay()->toDateString();
        }
        return $days;
    }
}
