<?php

namespace App\Console\Schedules\Order;

use Carbon\Carbon;
use App\Helper\Helper;
use App\Console\Schedules\BaseSchedule;

class ExportOfflineList extends BaseSchedule
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        Helper::modifyDatabaseConfig('online');
        $start     = Carbon::today()->subWeek();
        $end       = Carbon::yesterday()->endOfDay();
        $marketers = $this->getManagers();
        $cont_s    = $this->getContract();
        $regions   = $this->getRegions();
        $parts     = $this->getParts();
        $report    = [];
        $report[]  = ['订单日期', '订单时间', '订单号', '学校ID', '学校名称', '省', '市', '区县', '市场专员', '加盟校', '合同档', '协议价', '昵称', '备注名', '学生手机', '金额', '状态', '补登', '天数', '计数'];
        $orders    = \DB::table('order_offline')
            ->selectRaw('order_offline.created_at, order_offline.id as offline_id, school.id, school.name, school.marketer_id, nickname, group_concat(DISTINCT vanclass_student.mark_name) as _mark_name, user.phone, pay_fee, days, pay_status, approval_code')
            ->join('user_account', 'user_account.id', '=', 'order_offline.student_id')
            ->join('user', 'user.id', '=', 'user_account.user_id')
            ->join('school', 'school.id', '=', 'order_offline.school_id')
            ->join('vanclass_student', 'order_offline.student_id', '=', 'vanclass_student.student_id', 'left')
            ->whereBetween('order_offline.created_at', [$start, $end])
            ->groupBy('order_offline.id')->get();
        foreach ($orders as $order) {
            $time     = Carbon::parse($order->created_at);
            $s_id     = $order->id;
            $region   = is_null($s_id) ? null : explode('/', $regions[$s_id]);
            $fee      = $order->pay_fee;
            $data     = [
                'date' => $time->format('Y-m-d'),
                'time' => $time->format('H:i:s'),
                'num' => $order->offline_id,
                'id' => $s_id,
                'name' => $order->name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'marketer' => is_null($order->marketer_id) ? null : $marketers[$order->marketer_id],
                'part' => isset($parts[$s_id]) ? $parts[$s_id] : null,
                'title' => isset($cont_s[$s_id]) ? $cont_s[$s_id] : null,
                'set_price' => $fee,
                'nickname' => $order->nickname,
                'mark_name' => $order->_mark_name,
                'phone' => substr_replace($order->phone, '****', 3, 4),
                'fee' => $fee,
                'status' => $order->pay_status == 'success' ? '正常' : '已退款',
                'off' => $order->approval_code == '' ? 1 : 0,
                'days' => $order->days,
                'count' => 1,
            ];
            $report[] = $data;
        }
        
        $refunds = \DB::table('order_offline_refund')
            ->selectRaw('order_offline_refund.created_at, order_offline.id as offline_id, school.id, school.name, school.marketer_id, nickname, group_concat(DISTINCT vanclass_student.mark_name) as _mark_name, user.phone, pay_fee, refund_fee, refund_days')
            ->join('order_offline', 'order_offline.id', '=', 'order_offline_refund.offline_id')
            ->join('user_account', 'user_account.id', '=', 'order_offline.student_id')
            ->join('user', 'user.id', '=', 'user_account.user_id')
            ->join('school', 'school.id', '=', 'order_offline.school_id')
            ->join('vanclass_student', 'order_offline.student_id', '=', 'vanclass_student.student_id', 'left')
            ->whereBetween('order_offline_refund.created_at', [$start, $end])
            ->groupBy('order_offline_refund.id')->get();
        foreach ($refunds as $order) {
            $time     = Carbon::parse($order->created_at);
            $s_id     = $order->id;
            $region   = is_null($s_id) ? null : explode('/', $regions[$s_id]);
            $fee      = $order->pay_fee;
            $data     = [
                'date' => $time->format('Y-m-d'),
                'time' => $time->format('H:i:s'),
                'num' => $order->offline_id,
                'id' => $s_id,
                'name' => $order->name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'marketer' => is_null($order->marketer_id) ? null : $marketers[$order->marketer_id],
                'part' => isset($parts[$s_id]) ? $parts[$s_id] : null,
                'title' => isset($cont_s[$s_id]) ? $cont_s[$s_id] : null,
                'set_price' => $fee,
                'nickname' => $order->nickname,
                'mark_name' => $order->_mark_name,
                'phone' => substr_replace($order->phone, '****', 3, 4),
                'fee' => -$order->refund_fee,
                'status' => '退款单',
                'off' => '',
                'days' => -$order->refund_days,
                'count' => -1,
            ];
            $report[] = $data;
        }
        
        $filename = $start->format('YmdHis').'_'.$end->format('YmdHis').'_Offline';
        $file     = $this->store($filename, storage_path('exports').'/offline', $report);
        $subject  = $start->format('Y-m-d').' - '.$end->format('Y-m-d').' Offline Export';
        $this->email('xuyayue@vanthink.org', 'emails.export', ['object' => '每周代交'], $subject, realpath($file));
    }
    
}
