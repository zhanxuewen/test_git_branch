<?php

namespace App\Console\Schedules\Monitor;

use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;

class RecordOrderIncrement extends BaseSchedule
{
    protected $start;

    protected $end;

    /**
     * Execute the console command.
     *
     * @param $day
     * @return void
     */
    public function handle($day)
    {
        if (\DB::table('monitor_order_increment')->where('created_date', $day)->count() > 0) {
            echo 'M_O_I ' . $day . ' Already Done!';
            return;
        }
        $date = $day->toDateString();
        $this->start = $day->startOfDay()->toDateTimeString();
        $this->end = $day->endOfDay()->toDateTimeString();
        $local_pdo = \DB::getPdo();
        $pdo = \DB::setPdo($this->getPdo('online'));
        $types = ['Single Success' => "IN ('single_success', 'success')",
            'Group Success' => "= 'group_success'",
            'Order Refund' => "LIKE '%refund%' AND finished_at IS NOT NULL",
            'Order Success' => "LIKE '%success'",
            'Offline Success' => "= 'success'",
            'Offline Refund' => "= 'refund'"];
        $create = [];
        foreach ($types as $type => $where) {
            $table = strstr($type, 'Offline') ? '`order_offline`' : '`order`';
            $sql = $this->buildSql($table, $where);
            $sum = $pdo->select($sql)[0]->sum;
            $create[$type] = [
                'type' => $type,
                'count' => is_null($sum) ? 0 : $sum,
                'created_date' => $date
            ];
        }
        $all = $this->getAllSum($create);
        $create['All'] = ['type' => 'All', 'count' => $all, 'created_date' => $date];
        \DB::setPdo($local_pdo)->table('monitor_order_increment')->insert($create);
    }

    protected function buildSql($table, $where)
    {
        return "SELECT SUM(pay_fee) as sum FROM $table WHERE pay_status $where AND created_at BETWEEN '$this->start' AND '$this->end'";
    }

    protected function getAllSum($array)
    {
        $sum = isset($array['Order Success']) ? $array['Order Success']['count'] : 0;
        $sum += isset($array['Offline Success']) ? $array['Offline Success']['count'] : 0;
        return $sum;
    }
}
