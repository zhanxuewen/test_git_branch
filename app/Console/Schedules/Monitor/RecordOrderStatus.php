<?php

namespace App\Console\Schedules\Monitor;

use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;

class RecordOrderStatus extends BaseSchedule
{
    protected $local;
    protected $online;

    protected $date;
    protected $between = [];

    protected $comm = [];
    protected $_comm = [];

    protected $create;
    protected $update;
    protected $ids;

    /**
     * Execute the console command.
     *
     * @param $day
     * @return void
     */
    public function handle(Carbon $day)
    {
        if (\DB::table('monitor_order_status')->where('finished_date', $day)->count() > 0) {
            echo 'M_O_S ' . $day . ' Already Done!';
            return;
        }
        $this->init($day);
        \DB::setPdo($this->online);
        $this->getCommodity();
        $this->handleInsert();
        $this->handleRefund();
        \DB::setPdo($this->local);
        $this->insert();
        $this->update();
    }

    public function refundOnly(Carbon $day)
    {
        $this->init($day);
        \DB::setPdo($this->online);
        $this->getCommodity();
        $this->handleRefund();
        \DB::setPdo($this->local);
        $this->update();
    }

    protected function init(Carbon $day)
    {
        $this->date = $day->toDateString();
        $this->between = [
            $day->startOfDay()->toDateTimeString(),
            $day->endOfDay()->toDateTimeString()
        ];
        if (empty($this->local)) {
            $this->local = \DB::getPdo();
            $this->online = $this->getConnPdo('core', 'online');
        }

        $this->ids = [];
        $this->create = [];
        $this->update = ['all' => [], 'part' => []];
    }

    protected function getCommodity()
    {
        if (!empty($this->comm)) return;

        $items = \DB::table('payment_commodity')->selectRaw('id, paid_type, days')
            ->whereNull('deleted_at')->get();
        foreach ($items as $item) {
            $this->comm[$item->id] = [$item->days, $item->paid_type];
            $this->_comm[$item->paid_type][$item->days] = $item->id;
        }
    }

    protected function handleInsert()
    {
        $orders = $this->getOrder();
        foreach ($orders as $order) {
            $this->create[] = $this->buildOrderCreate($order);
        }
        $offline_s = $this->getOffline();
        foreach ($offline_s as $offline) {
            $this->create[] = $this->buildOfflineCreate($offline);
        }
    }

    protected function getOrder()
    {
        $raw = 'id, student_id, school_id, is_group_order, commodity_id, pay_fee';
        return \DB::table('order')->whereBetween('finished_at', $this->between)->selectRaw($raw)->get();
    }

    protected function getOffline()
    {
        $raw = 'id, student_id, school_id, commodity_type, days, date_type, pay_fee';
        return \DB::table('order_offline')->whereBetween('finished_at', $this->between)->selectRaw($raw)->get();
    }

    protected function buildOrderCreate($order)
    {
        return [
            'type' => 'order',
            'origin_id' => $order->id,
            'student_id' => $order->student_id,
            'school_id' => $order->school_id,
            'is_group' => $order->is_group_order,
            'commodity_id' => $order->commodity_id,
            'commodity_type' => $this->comm[$order->commodity_id][1],
            'days' => $this->comm[$order->commodity_id][0],
            'pay_fee' => $order->pay_fee,
            'is_refunded' => 0,
            'finished_date' => $this->date
        ];
    }

    protected function buildOfflineCreate($order)
    {
        $type = $order->commodity_type;
        $id = $order->date_type == 'normal' ? $this->_comm[$type][$order->days] : null;
        return [
            'type' => 'offline',
            'origin_id' => $order->id,
            'student_id' => $order->student_id,
            'school_id' => $order->school_id,
            'is_group' => 0,
            'commodity_id' => $id,
            'commodity_type' => $type,
            'days' => $order->days,
            'pay_fee' => $order->pay_fee,
            'is_refunded' => 0,
            'finished_date' => $this->date
        ];
    }

    protected function handleRefund()
    {
        $orders = $this->getOrderRefund();
        foreach ($orders as $order) {
            $this->ids['order'][] = $order->id;
            $this->buildOrderUpdate($order);
        }
        $offline_s = $this->getOfflineRefund();
        foreach ($offline_s as $offline) {
            $this->ids['offline'][] = $offline->id;
            $this->buildOfflineUpdate($offline);
        }
    }

    protected function getOrderRefund()
    {
        $raw = 'order.id, pay_fee, refund_fee';
        return \DB::table('order')->join('order_refund', 'order_refund.out_trade_no', '=', 'order.out_trade_no')
            ->whereBetween('refunded_at', $this->between)->whereNotNull('finished_at')->selectRaw($raw)->get();
    }

    protected function getOfflineRefund()
    {
        $raw = 'order_offline.id, pay_fee, refund_fee';
        return \DB::table('order_offline')->join('order_offline_refund', 'order_offline_refund.offline_id', '=', 'order_offline.id')
            ->whereBetween('refunded_at', $this->between)->whereNotNull('finished_at')->selectRaw($raw)->get();
    }

    protected function buildOrderUpdate($order)
    {
        $fee = $order->pay_fee - $order->refund_fee;
        if ($fee > 0) {
            $this->update['part']['order'][] = [
                'id' => $order->id,
                'refund_fee' => $order->refund_fee,
                'remained_fee' => $order->pay_fee - $order->refund_fee,
            ];
        } else {
            $this->update['all']['order'][] = $order->id;
        }
    }

    protected function buildOfflineUpdate($order)
    {
        $fee = $order->pay_fee - $order->refund_fee;
        if ($fee > 0) {
            $this->update['part']['offline'][] = [
                'id' => $order->id,
                'refund_fee' => $order->refund_fee,
                'remained_fee' => $order->pay_fee - $order->refund_fee,
            ];
        } else {
            $this->update['all']['offline'][] = $order->id;
        }
    }

    protected function insert()
    {
        $chunks = array_chunk($this->create, 100);
        foreach ($chunks as $chunk) {
            \DB::table('monitor_order_status')->insert($chunk);
        }
    }

    protected function update()
    {
        $this->updateIds();
        $this->updateAll();
        $this->updatePart();
    }

    protected function updateIds()
    {
        $update = ['is_refunded' => 1, 'refunded_date' => $this->date];
        foreach ($this->ids as $type => $ids) {
            \DB::table('monitor_order_status')->where('type', $type)->whereIn('origin_id', $ids)->update($update);
        }
    }

    protected function updateAll()
    {
        foreach ($this->update['all'] as $type => $ids) {
            $ids = implode(',', $ids);
            $where = "type = '$type' AND origin_id IN ($ids)";
            \DB::select("UPDATE monitor_order_status SET refund_fee = pay_fee, remained_fee = 0 WHERE $where");
        }
    }

    protected function updatePart()
    {
        foreach ($this->update['part'] as $type => $items) {
            $refund = $remain = $ids = [];
            foreach ($items as $item) {
                $ids[] = $id = $item['id'];
                $refund[] = "WHEN $id THEN " . $item['refund_fee'];
                $remain[] = "WHEN $id THEN " . $item['remained_fee'];
            }
            $ids = implode(',', $ids);
            $refund = implode(' ', $refund);
            $remain = implode(' ', $remain);
            $where = "type = '$type' AND origin_id IN ($ids)";
            $set = "refund_fee = (CASE origin_id $refund END), remained_fee = (CASE origin_id $remain END)";
            \DB::select("UPDATE monitor_order_status SET $set WHERE $where");
        }
    }


}
