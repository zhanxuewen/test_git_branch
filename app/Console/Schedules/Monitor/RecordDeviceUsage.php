<?php

namespace App\Console\Schedules\Monitor;

use App\Console\Schedules\BaseSchedule;

class RecordDeviceUsage extends BaseSchedule
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $day = date('Y-m-d');
        if (\DB::table('monitor_device_usage_amount')->where('created_date', $day)->count() > 0) {
            echo 'M_D_U_A '.$day.' Already Done!';
            return;
        }
        $local_pdo = \DB::getPdo();
        $sql       = "SELECT count(DISTINCT user_id) as coo, device FROM user_device_record GROUP BY device ORDER BY coo DESC LIMIT 50";
        $amounts   = \DB::setPdo($this->getPdo('online'))->select($sql);
        $create    = [];
        foreach ($amounts as $amount) {
            $create[] = [
                'device' => $amount->device,
                'user_amount' => $amount->coo,
                'created_date' => $day
            ];
        }
        \DB::setPdo($local_pdo)->table('monitor_device_usage_amount')->insert($create);
    }
}
