<?php

namespace App\Console\Schedules\Order;

use Carbon\Carbon;
use App\Console\Schedules\BaseSchedule;

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
        $con_s = $this->getContract();
        $regions = $this->getRegions();
        $between = [$start->toDateString(), $end->toDateString()];
        $map = [
            'receipt' => '付款',
            'payment' => '收款',
            'monthPayment' => '月结',
            'offlinePayment' => '管理端代买',
            'schoolOfflinePayment' => '校长端代买',
            'offlineRefund' => '管理端代退',
            'schoolOfflineRefund' => '校长端代退',
        ];
        $report = [];
        $report[] = ['日期', '业务类型', '摘要', '金额', '学校ID', '学校名称', '省', '市', '区', '市场专员', '合作档'];
        $orders = \DB::table('school')
            ->selectRaw('school.id, name, accountant_statement.type, date, fee, content, nickname')
            ->join('accountant_statement', 'school.id', '=', 'accountant_statement.school_id')
            ->join('user_account', 'user_account.id', '=', 'school.marketer_id')
            ->where('has_rollback', '<>', 1)
            ->whereBetween('date', $between)
            ->orderBy('date')->get();
        foreach ($orders as $order) {
            $region = explode('/', isset($regions[$order->id]) ? $regions[$order->id] : '');
            $report[] = [
                'date' => $order->date,
                'type' => $map[$order->type],
                'con' => $order->content,
                'fee' => $order->fee,
                'id' => $order->id,
                'name' => $order->name,
                'she' => isset($region[0]) ? $region[0] : '',
                'shi' => isset($region[1]) ? $region[1] : '',
                'qu' => isset($region[2]) ? $region[2] : '',
                'nick' => $order->nickname,
                'cons' => isset($con_s[$order->id]) ? $con_s[$order->id] : '',
            ];
        }

        $filename = '对账单_' . $start->format('YmdHis') . '_' . $end->format('YmdHis');
        $path = 'accountant/' . $start->year;
        $file = $this->store($path . '/' . $filename, $report);
        if ($send) {
            list($object, $subject) = $this->getSubject($day);
            $this->email(['xuyayue@vanthink.org', 'xiemin68@163.com'], 'emails.export', ['object' => $object . '对账单'], $subject . ' Accountant Statement Export', realpath($file));
        }
    }

}
