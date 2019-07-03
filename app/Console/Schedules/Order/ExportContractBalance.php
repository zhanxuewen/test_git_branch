<?php

namespace App\Console\Schedules\Order;

use Carbon\Carbon;
use App\Helper\Helper;
use App\Console\Schedules\BaseSchedule;

class ExportContractBalance extends BaseSchedule
{
    /**
     * Execute the console command.
     *
     * @param $send
     * @return void
     */
    public function handle($send = true)
    {
        Helper::modifyDatabaseConfig('online');
        $sql = "SELECT school.id, school.`name`, attr.value as region, nickname, pop.`value` as contract, school_popularize_data.`value` as balance FROM school_popularize_data INNER JOIN school ON school.id = school_popularize_data.school_id INNER JOIN user_account ON school.marketer_id = user_account.id LEFT JOIN school_popularize_data AS pop ON pop.school_id = school.id AND pop.`key` = 'contract_class' LEFT JOIN school_attribute as attr ON attr.school_id = school.id AND attr.`key` = 'region' WHERE school_popularize_data.`key` = 'balance_fee' ORDER BY school.id";
        $report = [];
        $report[] = ['学校ID', '学校名称', '省', '市', '区县', '市场专员', '合同档', '余额'];
        $schools = \DB::select($sql);
        foreach ($schools as $school) {
            $region = explode('/', $school->region);
            $data = [
                'id' => $school->id,
                'name' => $school->name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'marketer' => $school->nickname,
                'part' => $school->contract,
                'balance' => $school->balance
            ];
            $report[] = $data;
        }
        $today = Carbon::now();
        $filename = $today->format('Ymd') . '_Balance';
        $path = 'balance/' . $today->year . '/' . $today->month;
        $file = $this->store($path . '/' . $filename, $report);
        if ($send) {
            $this->email('xuyayue@vanthink.org', 'emails.export', ['object' => '合作校余额'], $today->toDateString() . ' Balance Export', realpath($file));
        }
    }

}
