<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $count    = $this->builder->setModel('tableIncrement')->distinct()->count('created_date');
        $sub_days = $count > 14 ? 14 : $count;
        $subDay   = Carbon::now()->subDays($sub_days);
        $begin    = $subDay->toDateString();
        $days     = [];
        for ($i = 0; $i < $sub_days; $i++) {
            $days[] = $subDay->addDay()->toDateString();
        }
        $dates  = json_encode($days);
        $levels = $this->builder->setModel('tableIncrement')->distinct()->orderBy('level', 'desc')->lists('level');
        foreach ($levels as $level) {
            $rows[$level] = $this->builder->setModel('tableIncrement')
                ->selectRaw('`table`, group_concat(rows) as _rows')->where('level', $level)->where('created_date', '>', $begin)
                ->groupBy('table')->orderBy('rows', 'desc')->take(10)->get()->toJson();
        }
        $levels = json_encode($levels);
        return view('dashboard.dashboard', compact('rows', 'levels', 'dates'));
    }
}
