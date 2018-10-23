<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $table  = $this->getRecord('tableIncrement', 'table', 'table', 'rows');
        $device = $this->getRecord('deviceUsageAmount', 'device', 'device', 'user_amount');
        $data   = array_merge_recursive($table, $device);
        return view('dashboard.dashboard', $data);
    }
    
    protected function getRecord($model, $label, $item, $count)
    {
        $sub_days = $this->getSubDays($model);
        $dates    = json_encode($this->listSubDays($sub_days));
        $rows     = $this->getRows($model, $sub_days, $item, $count);
        return ['rows' => [$label => $rows], 'dates' => [$label => $dates]];
    }
    
    protected function getRows($model, $sub_days, $item, $count)
    {
        return $this->builder->setModel($model)->selectRaw("`$item` as item, group_concat($count) as _count")
            ->where('created_date', '>', Carbon::now()->subDays($sub_days)->toDateString())
            ->groupBy($item)->orderBy($count, 'desc')->take(10)->get()->toJson();
    }
    
    
    protected function getSubDays($model)
    {
        $count = $this->builder->setModel($model)->distinct()->count('created_date');
        return $count > 14 ? 14 : $count;
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
