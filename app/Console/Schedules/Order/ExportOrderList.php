<?php

namespace App\Console\Schedules\Order;

use Carbon\Carbon;
use App\Helper\Helper;
use App\Console\Schedules\BaseSchedule;

class ExportOrderList extends BaseSchedule
{
    /**
     * Execute the console command.
     *
     * @param $day = ''
     * @param $send
     * @return void
     */
    public function handle($day = '', $send = true)
    {
        Helper::modifyDatabaseConfig('online');
        if (is_array($day)) {
            $start = Carbon::parse($day['start']);
            $end = Carbon::parse($day['end'])->endOfDay();
        } else {
            $start = $day == '' ? Carbon::yesterday() : Carbon::parse($day);
            $end = $day == '' ? Carbon::yesterday()->endOfDay() : Carbon::parse($day)->endOfDay();
        }
        $marketers = $this->getManagers();
        $set_prices = $this->setPrices();
        $cont_s = $this->getContract();
        $regions = $this->getRegions();
        $parts = $this->getParts();
        $platform = [2 => '家长端', 5 => '学生端', 6 => '优惠页'];
        $app = [1 => '微信X', 2 => '支付宝', 3 => 'IOS'];
        $report = [];
        $report[] = ['订单日期', '订单时间', '支付通道', '支付方式', '订单号', '学校ID', '学校名称', '省', '市', '区县', '市场专员', '加盟校', '合同档', '协议价', '昵称', '备注名', '学生手机', '金额', '拼团', '卡类别', '计数', '退款时间'];
        $orders = \DB::table('order')
            ->selectRaw('out_trade_no, trade_type, is_group_order, school.id, school.name, school.marketer_id, nickname, group_concat(DISTINCT vanclass_student.mark_name) as _mark_name, user.phone, pay_fee, commodity_name, commodity_id, refunded_at, finished_at')
            ->join('user_account', 'user_account.id', '=', 'order.student_id')
            ->join('user', 'user.id', '=', 'user_account.user_id')
            ->join('school', 'school.id', '=', 'order.school_id', 'left')
            ->join('vanclass_student', 'order.student_id', '=', 'vanclass_student.student_id', 'left')
            ->whereNotNull('order.transaction_id')
            ->whereBetween('order.created_at', [$start, $end])
            ->groupBy('order.id')->get();
        foreach ($orders as $order) {
            $s_id = $order->id;
            $num = $order->out_trade_no;
            $time = Carbon::parse(substr($num, 0, 14));
            $type = explode('_', substr($num, 15, 5));
            $region = is_null($s_id) ? null : explode('/', $regions[$s_id]);
            if ($type[2] != 1) {
                $get_type = $app[$type[2]];
            } else {
                $get_type = $type[0] == 5 ? '微信6' : '微信1';
            }
            $set_price = isset($cont_s[$s_id]) ? $set_prices[$order->commodity_id][$cont_s[$s_id]] : $set_prices[$order->commodity_id]['F'];

            $data = [
                'date' => $time->format('Y-m-d'),
                'time' => $time->format('H:i:s'),
                'channel' => $platform[$type[0]],
                'type' => $get_type,
                'num' => $num,
                'id' => $s_id,
                'name' => $order->name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'marketer' => is_null($order->marketer_id) ? null : $marketers[$order->marketer_id],
                'part' => isset($parts[$s_id]) ? $parts[$s_id] : null,
                'title' => isset($cont_s[$s_id]) ? $cont_s[$s_id] : null,
                'set_price' => $set_price,
                'nickname' => $order->nickname,
                'mark_name' => $order->_mark_name,
                'phone' => substr_replace($order->phone, '****', 3, 4),
                'fee' => $order->pay_fee,
                'group' => $order->is_group_order,
                'comm' => $order->commodity_name,
                'count' => 1,
                'refund' => $order->refunded_at
            ];
            $report[] = $data;
        }

        $refunds = \DB::table('order_refund')
            ->selectRaw('order_refund.out_refund_no, order.out_trade_no, trade_type, is_group_order, school.id, school.name, school.marketer_id, nickname, group_concat(DISTINCT vanclass_student.mark_name) as _mark_name, user.phone, refund_fee, commodity_name,commodity_id, order_refund.created_at')
            ->join('order', 'order.out_trade_no', '=', 'order_refund.out_trade_no')
            ->join('user_account', 'user_account.id', '=', 'order.student_id')
            ->join('user', 'user.id', '=', 'user_account.user_id')
            ->join('school', 'school.id', '=', 'order.school_id', 'left')
            ->join('vanclass_student', 'order.student_id', '=', 'vanclass_student.student_id', 'left')
            ->whereBetween('order_refund.created_at', [$start, $end])
            ->groupBy('order.id')->get();
        foreach ($refunds as $order) {
            $num = $order->out_refund_no;
            $s_id = $order->id;
            $time = $num == '' ? Carbon::parse($order->created_at) : Carbon::parse(substr($num, 0, 14));
            $type = explode('_', substr($order->out_trade_no, 15, 5));
            $region = is_null($s_id) ? null : explode('/', $regions[$s_id]);
            if ($type[2] != 1) {
                $get_type = $app[$type[2]];
            } else {
                $get_type = $type[0] == 5 ? '微信6' : '微信1';
            }
            $set_price = isset($cont_s[$s_id]) ? $set_prices[$order->commodity_id][$cont_s[$s_id]] : $set_prices[$order->commodity_id]['F'];
            $data = [
                'date' => $time->format('Y-m-d'),
                'time' => $time->format('H:i:s'),
                'channel' => $platform[$type[0]],
                'type' => $get_type,
                'num' => $num,
                'id' => $s_id,
                'name' => $order->name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'marketer' => is_null($order->marketer_id) ? null : $marketers[$order->marketer_id],
                'part' => isset($parts[$s_id]) ? $parts[$s_id] : null,
                'title' => isset($cont_s[$s_id]) ? $cont_s[$s_id] : null,
                'set_price' => $set_price,
                'nickname' => $order->nickname,
                'mark_name' => $order->_mark_name,
                'phone' => substr_replace($order->phone, '****', 3, 4),
                'fee' => -$order->refund_fee,
                'group' => $order->is_group_order,
                'comm' => $order->commodity_name,
                'count' => -1,
                'refund' => null
            ];
            $report[] = $data;
        }
        $filename = $start->format('YmdHis') . '_' . $end->format('YmdHis') . '_Order';
        $path = 'order/' . $start->year . '/' . $start->month;
        $file = $this->store($path . '/' . $filename, $report);
        if ($send) {
            list($object, $subject) = $this->getSubject($day);
            $this->email('xuyayue@vanthink.org', 'emails.export', ['object' => $object . '线上'], $subject . ' Order Export', realpath($file));
        }
    }

}
