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
            exec('/usr/share/php ' . base_path() . '/artisan recall:order:schedule ' . $request->get('day'));
            return redirect(\URL::current() . '?month=' . $month);
        }
        $dir = storage_path('exports/order/') . $month;
        $files = is_dir($dir) ? scandir($dir) : [];
        $day = Carbon::parse($month . '/1');
        $m = $day->month;
        $list = [];
        for ($i = 0; $i < 32; $i++) {
            if ($day->isToday() || $day->month != $m) break;
            $f_name = $day->format('YmdHis') . '_' . $day->format('Ymd') . '235959_Order';
            $list[$day->toDateString()] = ($item = $this->preg_in_array('/' . $f_name . '/', $files)) ? $item : '';
            $day->addDay();
        }
        $month_s = Carbon::parse($month . '/1');
        $month_e = Carbon::parse($month . '/1')->endOfMonth();
        $f_name = $month_s->format('YmdHis') . '_' . $month_e->endOfDay()->format('YmdHis') . '_Order';
        $monthly = ['file' => ($item = $this->preg_in_array('/' . $f_name . '/', $files)) ? $item : '',
            'day' => $month_s->toDateString() . ',' . $month_e->toDateString()];
        return view('export.order', compact('list', 'month', 'monthly'));
    }

    public function exportOrSend(Request $request)
    {
        if (strstr($file = $request->get('file'), ',')) {
            list($s, $e) = explode(',', $file);
            $day = Carbon::parse($s);
            $f_name = $day->format('YmdHis') . '_' . Carbon::parse($e)->endOfDay()->format('YmdHis');
        } else {
            $day = Carbon::parse($request->get('file'));
            $f_name = $day->format('YmdHis') . '_' . $day->format('Ymd') . '235959';
        }
        $month = $day->year . '/' . $day->month;
        $dir = storage_path('exports/order/') . $month;
        $files = is_dir($dir) ? scandir($dir) : [];
        $item = $this->preg_in_array('/' . $f_name . '/', $files);
        $file = storage_path('exports/order/') . $month . '/' . $item;
        if ($request->get('action') == 'export') {
            return response()->download($file, $item);
        } else {
            $subject = $day->toDateString() . ' Order Export';
            $this->email('xuyayue@vanthink.org', 'emails.export', ['object' => '每日线上'], $subject, realpath($file));
            return redirect()->back()->with('success', 'Email Send Success!');
        }
    }

    protected function preg_in_array($preg, $array)
    {
        foreach ($array as $item) {
            if (preg_match($preg, $item) > 0) return $item;
        }
        return false;
    }

}
