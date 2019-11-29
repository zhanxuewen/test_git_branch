<?php

namespace App\Console\Schedules\Order;

use Carbon\Carbon;
use App\Console\Schedules\BaseSchedule;
use App\Console\Common\ZXZJ\SchoolAccountant;

class ExportAccountantStatement extends BaseSchedule
{
    /**
     * Execute the console command.
     *
     * @param $day
     * @param $send
     * @return void
     */
    public function handle($day = [], $send = true)
    {
        \DB::setPdo($this->getConnPdo('core', 'online'));
        $start = Carbon::parse($day['start']);
        $end = Carbon::parse($day['end'])->endOfDay();
        $report = SchoolAccountant::get($start->toDateString(), $end->toDateString());
        $filename = '对账单_' . $start->format('YmdHis') . '_' . $end->format('YmdHis');
        $path = 'accountant/' . $start->year;
        $file = $this->store($path . '/' . $filename, $report);
        if ($send) {
            list($object, $subject) = $this->getSubject($day);
            $this->email(['xuyayue@vanthink.org', 'xiemin68@163.com'], 'emails.export', ['object' => $object . '对账单'], $subject . ' Accountant Statement Export', realpath($file));
        }
    }

}
