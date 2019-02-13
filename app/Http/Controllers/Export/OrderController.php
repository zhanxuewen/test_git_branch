<?php

namespace App\Http\Controllers\Export;

use App\Jobs\RecallOrderSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function listExcels(Request $request)
    {
        if ($request->has('day')) {
            $day = $request->get('day');
            RecallOrderSchedule::dispatch($day);
        }
        $now = Carbon::now();
        $month = explode('/', $request->get('month', $now->year . '/' . $now->month));
        $month = implode('/', array_map(function ($item) {
            return (int)$item;
        }, $month));
        $dir = storage_path('exports/order/') . $month;
        $files = is_dir($dir) ? scandir($dir) : [];
        $day = Carbon::parse($month . '/1');
        $m = $day->month;
        $list = [];
        for ($i = 0; $i < 32; $i++) {
            if ($day->isToday() || $day->month != $m) break;
            $list[$day->toDateString()] = ($item = $this->preg_in_array('/' . $day->format('Ymd') . '/', $files)) ? $item : '';
            $day->addDay();
        }
        return view('export.order', compact('list', 'month'));
    }

}
