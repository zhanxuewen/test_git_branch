<?php

namespace App\Http\Controllers\Export;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function listExcels(Request $request)
    {
        $now = Carbon::now();
        $month = explode('/', $request->get('month', $now->year . '/' . $now->month));
        $month = implode('/', array_map(function ($item) {
            return (int)$item;
        }, $month));
        if ($request->has('day')) {
            exec('php ' . base_path() . '/artisan recall:order:schedule ' . $request->get('day'));
            return redirect(\URL::current() . '?month=' . $month);
        }
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

    public function exportOrSend(Request $request)
    {
        $day = Carbon::parse($request->get('file'));
        $month = $day->year . '/' . $day->month;
        $dir = storage_path('exports/order/') . $month;
        $files = is_dir($dir) ? scandir($dir) : [];
        $item = $this->preg_in_array('/' . $day->format('Ymd') . '/', $files);
        $file = storage_path('exports/order/') . $month . '/' . $item;
        if ($request->get('action') == 'export') {
            return response()->download($file, $item);
        } else {
            $subject = $day->toDateString() . ' Order Export';
            $this->email('xuyayue@vanthink.org', 'emails.export', ['object' => '每日线上'], $subject, realpath($file));
            return redirect()->back()->with('success', 'Email Send Success!');
        }
    }

}
