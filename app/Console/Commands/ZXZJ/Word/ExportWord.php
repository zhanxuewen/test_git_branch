<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
SELECT
school.id 学校id, school.name 学校名称, school_region , mark.nickname 市场专员, after.nickname 运营专员, sign_contract_date 签约日期,contract_class 档位
FROM
`b_vanthink_online`.`statistic_school_record`
left join school on school.id = statistic_school_record.school_id
left join user_account mark on mark.id = statistic_school_record.marketer_id
left join user_account after on after.id = statistic_school_record.afterSales_id
WHERE
`date_type` = '2020-07-23'
AND `contract_class` <> 'N'
**/

class ExportWord extends Command
{

    use PdoBuilder;
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:word {pdo=local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import word and translation by upload excel';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function getTotalInfo($month)
    {
        config(['database.default' => 'local']);

        $time = Carbon::parse($month)->endOfMonth()->endOfDay()->toDateTimeString();

        $sql = <<<EOF
SELECT
    distinct accountant_statement.school_id,
    if(offline.offline_fee ,offline.offline_fee , 0),
    if(`online`.online_fee ,`online`.online_fee , 0),
		 if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0) total_fee
FROM
    `vanthink`.`accountant_statement`
left join (
        SELECT
            school_id,sum(fee) * -1 offline_fee
        FROM
            `vanthink`.`accountant_statement`
        WHERE
            created_at <= '$time'
            and label_id <> 8
            GROUP BY school_id
    )  offline on offline.school_id = accountant_statement.school_id
left join (    
            SELECT
            school_id, sum(fee) * -1 online_fee
        FROM
            `accountant_statement`
        WHERE
            `label_id` = '8'
            AND `code` <= '$month'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id


WHERE
    accountant_statement.created_at <= '$time'
EOF;

        $record = \DB::select(\DB::raw($sql));


        return array_combine(
            array_column($record, 'school_id'),
            array_column($record, 'total_fee')
        );
    }


    public function handleSchoolInfo()
    {
        $month_5_info = $this->getTotalInfo('2020-05');

        $month_4_info = $this->getTotalInfo('2020-04');

        // 获得 3月底的 数据
        $month_3_info = $this->getTotalInfo('2020-03');
//        // 获得 2月底的 数据
//        $month_2_info = $this->getTotalInfo('2020-02');
//        // 获得 1月底的 数据
//        $month_1_info = $this->getTotalInfo('2020-01');


        config(['database.default' => 'online']);
        $start = '2020-03-31';
        $end = '2020-04-30';

        $school_record = [];
        $school_record[] = [
            'school_id' => '学校id',
            'school_name' => '学校名称',
            'nickname' => '运营专员',
            'first_date'=> '月初时间/接手时间',
            'trans_money' => '初始金额(元)',
            'last_date'=>'月末时间',
            'last_trans_money' => '月末金额(元)',
            'increase' => '增长额(元)',
        ];


        // 查找学校
        $sql = <<<EOF
SELECT 
	school_id, min(date_type) as min_date
FROM
	`b_vanthink_online`.`statistic_school_record` 
WHERE
	`date_type` <= '$end' AND `afterSales_id` <> '0' AND `date_type` >= '$start'
	GROUP BY school_id
EOF;

        $school_info = \DB::select(\DB::raw($sql));
        $school_info = json_decode(json_encode($school_info),true);


        foreach ($school_info as $school_item){
            $school_id = $school_item['school_id'];
            $min_date = $school_item['min_date'];
            // 初始化
            $school_init = \DB::table('statistic_school_record')
                ->selectRaw('school.id, school.name, user_account.nickname,statistic_school_record.extra')
                ->leftjoin('user_account','user_account.id', '=', 'statistic_school_record.afterSales_id')
                ->leftjoin('school','school.id', '=', 'statistic_school_record.school_id')
                ->where('statistic_school_record.school_id',$school_id)
                ->where('date_type',$min_date)
                ->first();

            $school_last = \DB::table('statistic_school_record')
                ->selectRaw('school.id, school.name, user_account.nickname,statistic_school_record.extra')
                ->leftjoin('user_account','user_account.id', '=', 'statistic_school_record.afterSales_id')
                ->leftjoin('school','school.id', '=', 'statistic_school_record.school_id')
                ->where('statistic_school_record.school_id',$school_id)
                ->where('date_type',$end)
                ->first();

           $first_fee = json_decode($school_init->extra, true)['trans_money'];
           if ($min_date == $start){
               $first_fee = isset($month_3_info[$school_init->id]) ? $month_3_info[$school_init->id] : 0;
           }


            if (empty($school_last)){
//                $school_record[] = [
//                    'school_id' => $school_init->id,
//                    'school_name' => $school_init->name,
//                    'nickname' => $school_init->nickname,
//                    'first_date'=>$min_date,
//                    'trans_money' => $first_fee ? $first_fee : '0',
//                    'last_date'=>$end,
//                    'increase' => '学校被删除'
//                ];
//                echo '+';
                continue;
            }

            $last_fee = isset($month_4_info[$school_init->id]) ? $month_4_info[$school_init->id] : 0;

            $school_record[] = [
                'school_id' => $school_init->id,
                'school_name' => $school_init->name,
                'nickname' => $school_init->nickname,
                'first_date'=>$min_date,
                'trans_money' => $first_fee ? $first_fee : '0',
                'last_date'=>$end,
                'last_trans_money' => $last_fee ? $last_fee : '0',
                'increase' => ($last_fee-$first_fee) ? ($last_fee-$first_fee) : '0',
            ];
            echo '+';
        }

        $this->store('4月份数据_'.rand(0,100), $school_record, '.xlsx');

        dd('done');
    }

    public function getTotalInfo_v2($month)
    {
        config(['database.default' => 'local']);

//        $time = Carbon::parse($month)->endOfMonth()->endOfDay()->toDateString();
        $start_time = Carbon::parse($month)->startOfMonth()->toDateString();
        $end_time = Carbon::parse($month)->endOfMonth()->toDateString();

        $sql = <<<EOF
SELECT
    distinct accountant_statement.school_id,
    if(offline.offline_fee ,offline.offline_fee , 0),
    if(`online`.online_fee ,`online`.online_fee , 0),
		 if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0) total_fee
FROM
    `vanthink`.`accountant_statement`
left join (
        SELECT
            school_id,sum(fee) * -1 offline_fee
        FROM
            `vanthink`.`accountant_statement`
        WHERE
            date <= '$end_time'
            and date >= '$start_time'
            and label_id <> 8
            GROUP BY school_id
    )  offline on offline.school_id = accountant_statement.school_id
left join (    
            SELECT
            school_id, sum(fee) * -1 online_fee
        FROM
            `accountant_statement`
        WHERE
            `label_id` = '8'
            AND `code` = '$month'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id
EOF;

        $record = \DB::select(\DB::raw($sql));


        return array_combine(
            array_column($record, 'school_id'),
            array_column($record, 'total_fee')
        );
    }

    public function getSchoolMarketer($date_key)
    {
        $sql = <<<EOF
SELECT
	statistic_school_record.school_id,user_account.nickname  marketer, after_man.nickname after_man
FROM
	`b_vanthink_online`.`statistic_school_record` 
	left join user_account on user_account.id = statistic_school_record.marketer_id
	left join user_account after_man on after_man.id = statistic_school_record.afterSales_id
WHERE
	`date_type` = '$date_key' 
EOF;

        $school_manage = \DB::select(\DB::raw($sql));
        $school_manage = json_decode(json_encode($school_manage),true);

        $marketer = array_combine(
            array_column($school_manage, 'school_id'),
            array_column($school_manage, 'marketer')
        );

        $after_man = array_combine(
            array_column($school_manage, 'school_id'),
            array_column($school_manage, 'after_man')
        );

        return [
            'marketer' => $marketer,
            'after_man' => $after_man
        ];

    }

    public function handleSchoolInfo_v2()
    {
        $start_month = '2018-05';
        $school_accountant = [];
        for($i=0; $i<24; $i++){
            $month = Carbon::parse($start_month)->addMonths($i)->toDateString();
            $month_key = substr($month,0,7);
//            $str = <<<EOF
//'month_key_key3'=> isset(school_accountant['month_key']) && isset(school_accountant['month_key'][school_id]) ? (school_accountant['month_key'][school_id] ? school_accountant['month_key'][school_id] : '0') : '0',
//'month_key_key2'=> isset(school_marketer['month_key']) && isset(school_marketer['month_key'][school_id]) ? school_marketer['month_key'][school_id] : '',
//'month_key_key1'=> isset(school_after_man['month_key']) && isset(school_after_man['month_key'][school_id]) ? school_after_man['month_key'][school_id] : '',
//EOF;
//            $rr = str_replace('month_key', $month_key , $str);
//            \Log::info($rr);


            $school_accountant[$month_key] = $this->getTotalInfo_v2($month_key);
        }


        config(['database.default' => 'online']);
        $start_month = '2019-03';
        $school_marketer = [];
        $school_after_man = [];
//        for($i=0; $i<20; $i++){
//            $date_key = Carbon::parse($start_month)->addMonths($i)->endOfMonth()->toDateString();
//            $month_key = substr($date_key,0,7);
//            $res = $this->getSchoolMarketer($date_key);
//            $school_marketer[$month_key] = $res['marketer'];
//            $school_after_man[$month_key] = $res['after_man'];
//        }

        config(['database.default' => 'online']);
        $school_record = [];
//        $school_record[] = [
//            'school_id' => '学校id',
//            'school_name' => '学校名称',
//            'create_date' => '创建时间',
//            '2019-12_key3'=>'2019-12',
//            '2019-12_key2'=>'市场',
//            '2019-12_key1'=>'售后',
//            '2019-11_key3'=>'2019-11',
//            '2019-11_key2'=>'市场',
//            '2019-11_key1'=>'售后',
//            '2019-10_key3'=>'2019-10',
//            '2019-10_key2'=>'市场',
//            '2019-10_key1'=>'售后',
//            '2019-09_key3'=>'2019-09',
//            '2019-09_key2'=>'市场',
//            '2019-09_key1'=>'售后',
//            '2019-08_key3'=>'2019-08',
//            '2019-08_key2'=>'市场',
//            '2019-08_key1'=>'售后',
//            '2019-07_key3'=>'2019-07',
//            '2019-07_key2'=>'市场',
//            '2019-07_key1'=>'售后',
//            '2019-06_key3'=>'2019-06',
//            '2019-06_key2'=>'市场',
//            '2019-06_key1'=>'售后',
//            '2019-05_key3'=>'2019-05',
//            '2019-05_key2'=>'市场',
//            '2019-05_key1'=>'售后',
//            '2019-04_key3'=>'2019-04',
//            '2019-04_key2'=>'市场',
//            '2019-04_key1'=>'售后',
//            '2019-03_key3'=>'2019-03',
//            '2019-03_key2'=>'市场',
//            '2019-03_key1'=>'售后',
//            '2019-02_key3'=>'2019-02',
//            '2019-02_key2'=>'市场',
//            '2019-02_key1'=>'售后',
//            '2019-01_key3'=>'2019-01',
//            '2019-01_key2'=>'市场',
//            '2019-01_key1'=>'售后',
//            '2018-12_key3'=>'2018-12',
//            '2018-12_key2'=>'市场',
//            '2018-12_key1'=>'售后',
//            '2018-11_key3'=>'2018-11',
//            '2018-11_key2'=>'市场',
//            '2018-11_key1'=>'售后',
//            '2018-10_key3'=>'2018-10',
//            '2018-10_key2'=>'市场',
//            '2018-10_key1'=>'售后',
//            '2018-09_key3'=>'2018-09',
//            '2018-09_key2'=>'市场',
//            '2018-09_key1'=>'售后',
//            '2018-08_key3'=>'2018-08',
//            '2018-08_key2'=>'市场',
//            '2018-08_key1'=>'售后',
//            '2018-07_key3'=>'2018-07',
//            '2018-07_key2'=>'市场',
//            '2018-07_key1'=>'售后',
//            '2018-06_key3'=>'2018-06',
//            '2018-06_key2'=>'市场',
//            '2018-06_key1'=>'售后',
//            '2018-05_key3'=>'2018-05',
//            '2018-05_key2'=>'市场',
//            '2018-05_key1'=>'售后',
//        ];
        $school_record[] = [
            'school_id' => '学校id',
            'school_name' => '学校名称',
            'create_date' => '创建时间',
            'class' => '学校档位（四月末）',
            'sheng' => '省',
            'shi' => '市',
            'qu' => '区',
            'marketer' => '市场（四月末）',
            'after_man' => '售后（四月末）',
            '2020' =>     '2020年数据（截止到四月）',
            '1819' =>     '1819年结算数据',

            '2020-04_key3'=>'2020-04',
            '2020-03_key3'=>'2020-03',
            '2020-02_key3'=>'2020-02',
            '2020-01_key3'=>'2020-01',
            '2019-12_key3'=>'2019-12',
            //'2019-12_key2'=>'市场',
            //'2019-12_key1'=>'售后',
            '2019-11_key3'=>'2019-11',
            //'2019-11_key2'=>'市场',
            //'2019-11_key1'=>'售后',
            '2019-10_key3'=>'2019-10',
            //'2019-10_key2'=>'市场',
            //'2019-10_key1'=>'售后',
            '2019-09_key3'=>'2019-09',
            //'2019-09_key2'=>'市场',
            //'2019-09_key1'=>'售后',
            '2019-08_key3'=>'2019-08',
            //'2019-08_key2'=>'市场',
            //'2019-08_key1'=>'售后',
            '2019-07_key3'=>'2019-07',
            //'2019-07_key2'=>'市场',
            //'2019-07_key1'=>'售后',
            '2019-06_key3'=>'2019-06',
            //'2019-06_key2'=>'市场',
            //'2019-06_key1'=>'售后',
            '2019-05_key3'=>'2019-05',
            //'2019-05_key2'=>'市场',
            //'2019-05_key1'=>'售后',
            '2019-04_key3'=>'2019-04',
            //'2019-04_key2'=>'市场',
            //'2019-04_key1'=>'售后',
            '2019-03_key3'=>'2019-03',
            //'2019-03_key2'=>'市场',
            //'2019-03_key1'=>'售后',
            '2019-02_key3'=>'2019-02',
            //'2019-02_key2'=>'市场',
            //'2019-02_key1'=>'售后',
            '2019-01_key3'=>'2019-01',
            //'2019-01_key2'=>'市场',
            //'2019-01_key1'=>'售后',
            '2018-12_key3'=>'2018-12',
            //'2018-12_key2'=>'市场',
            //'2018-12_key1'=>'售后',
            '2018-11_key3'=>'2018-11',
            //'2018-11_key2'=>'市场',
            //'2018-11_key1'=>'售后',
            '2018-10_key3'=>'2018-10',
            //'2018-10_key2'=>'市场',
            //'2018-10_key1'=>'售后',
            '2018-09_key3'=>'2018-09',
            //'2018-09_key2'=>'市场',
            //'2018-09_key1'=>'售后',
            '2018-08_key3'=>'2018-08',
            //'2018-08_key2'=>'市场',
            //'2018-08_key1'=>'售后',
            '2018-07_key3'=>'2018-07',
            //'2018-07_key2'=>'市场',
            //'2018-07_key1'=>'售后',
            '2018-06_key3'=>'2018-06',
            //'2018-06_key2'=>'市场',
            //'2018-06_key1'=>'售后',
            '2018-05_key3'=>'2018-05',
            //'2018-05_key2'=>'市场',
            //'2018-05_key1'=>'售后',
        ];
        // 查找学校
        $sql = <<<EOF
SELECT
	statistic_school_record.school_id,school.name, school.created_at, user_account.nickname  marketer, after_man.nickname after_man, school_attribute.`value`  region , class.value class
FROM
	`b_vanthink_online`.`statistic_school_record` 
	left join school on school.id = statistic_school_record.school_id
	left join user_account on user_account.id = statistic_school_record.marketer_id
	left join user_account after_man on after_man.id = statistic_school_record.afterSales_id
	left join school_attribute on school_attribute.school_id = statistic_school_record.school_id and school_attribute.key = 'region'
	left join school_attribute class on class.school_id = statistic_school_record.school_id and class.key = 'contract_class'
WHERE
	`date_type` = '2020-04-30' 
EOF;

        $school_info = \DB::select(\DB::raw($sql));
        $school_info = json_decode(json_encode($school_info),true);
        config(['database.default' => 'local']);
        $sql = <<<EOF
SELECT
    distinct accountant_statement.school_id,
    if(offline.offline_fee ,offline.offline_fee , 0),
    if(`online`.online_fee ,`online`.online_fee , 0),
		 if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0) total_fee
FROM
    `vanthink`.`accountant_statement`
left join (
        SELECT
            school_id,sum(fee) * -1 offline_fee
        FROM
            `vanthink`.`accountant_statement`
        WHERE
            date <= '2020-04-30'
            and date >= '2020-01-01'
            and label_id <> 8
            GROUP BY school_id
    )  offline on offline.school_id = accountant_statement.school_id
left join (    
            SELECT
            school_id, sum(fee) * -1 online_fee
        FROM
            `accountant_statement`
        WHERE
            `label_id` = '8'
            AND `code` >= '2020-01'
            AND `code` <= '2020-04'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id
EOF;

        $record = \DB::select(\DB::raw($sql));


        $month2020 =  array_combine(
            array_column($record, 'school_id'),
            array_column($record, 'total_fee')
        );

        $sql = <<<EOF
SELECT
    distinct accountant_statement.school_id,
    if(offline.offline_fee ,offline.offline_fee , 0),
    if(`online`.online_fee ,`online`.online_fee , 0),
		 if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0) total_fee
FROM
    `vanthink`.`accountant_statement`
left join (
        SELECT
            school_id,sum(fee) * -1 offline_fee
        FROM
            `vanthink`.`accountant_statement`
        WHERE
            date <= '2019-12-31'
            and label_id <> 8
            GROUP BY school_id
    )  offline on offline.school_id = accountant_statement.school_id
left join (    
            SELECT
            school_id, sum(fee) * -1 online_fee
        FROM
            `accountant_statement`
        WHERE
            `label_id` = '8'
            AND `code` <= '2019-12'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id
EOF;

        $record = \DB::select(\DB::raw($sql));


        $month1819 =  array_combine(
            array_column($record, 'school_id'),
            array_column($record, 'total_fee')
        );


        foreach ($school_info as $school_item){
            $school_id =  $school_item['school_id'];

            $region = $school_item['region'];

            $region_arr = explode('/', $region);


//            $school_record[] = [
//                'school_id' => $school_id,
//                'school_name' => $school_item['name'],
//                'create_date' => $school_item['created_at'],
//                '2019-12_key3'=> isset($school_accountant['2019-12']) && isset($school_accountant['2019-12'][$school_id]) ? ($school_accountant['2019-12'][$school_id] ? $school_accountant['2019-12'][$school_id] : '0') : '0',
//                '2019-12_key2'=> isset($school_marketer['2019-12']) && isset($school_marketer['2019-12'][$school_id]) ? $school_marketer['2019-12'][$school_id] : '',
//                '2019-12_key1'=> isset($school_after_man['2019-12']) && isset($school_after_man['2019-12'][$school_id]) ? $school_after_man['2019-12'][$school_id] : '',
//                '2019-11_key3'=> isset($school_accountant['2019-11']) && isset($school_accountant['2019-11'][$school_id]) ? ($school_accountant['2019-11'][$school_id] ? $school_accountant['2019-11'][$school_id] : '0') : '0',
//                '2019-11_key2'=> isset($school_marketer['2019-11']) && isset($school_marketer['2019-11'][$school_id]) ? $school_marketer['2019-11'][$school_id] : '',
//                '2019-11_key1'=> isset($school_after_man['2019-11']) && isset($school_after_man['2019-11'][$school_id]) ? $school_after_man['2019-11'][$school_id] : '',
//                '2019-10_key3'=> isset($school_accountant['2019-10']) && isset($school_accountant['2019-10'][$school_id]) ? ($school_accountant['2019-10'][$school_id] ? $school_accountant['2019-10'][$school_id] : '0') : '0',
//                '2019-10_key2'=> isset($school_marketer['2019-10']) && isset($school_marketer['2019-10'][$school_id]) ? $school_marketer['2019-10'][$school_id] : '',
//                '2019-10_key1'=> isset($school_after_man['2019-10']) && isset($school_after_man['2019-10'][$school_id]) ? $school_after_man['2019-10'][$school_id] : '',
//                '2019-09_key3'=> isset($school_accountant['2019-09']) && isset($school_accountant['2019-09'][$school_id]) ? ($school_accountant['2019-09'][$school_id] ? $school_accountant['2019-09'][$school_id] : '0') : '0',
//                '2019-09_key2'=> isset($school_marketer['2019-09']) && isset($school_marketer['2019-09'][$school_id]) ? $school_marketer['2019-09'][$school_id] : '',
//                '2019-09_key1'=> isset($school_after_man['2019-09']) && isset($school_after_man['2019-09'][$school_id]) ? $school_after_man['2019-09'][$school_id] : '',
//                '2019-08_key3'=> isset($school_accountant['2019-08']) && isset($school_accountant['2019-08'][$school_id]) ? ($school_accountant['2019-08'][$school_id] ? $school_accountant['2019-08'][$school_id] : '0') : '0',
//                '2019-08_key2'=> isset($school_marketer['2019-08']) && isset($school_marketer['2019-08'][$school_id]) ? $school_marketer['2019-08'][$school_id] : '',
//                '2019-08_key1'=> isset($school_after_man['2019-08']) && isset($school_after_man['2019-08'][$school_id]) ? $school_after_man['2019-08'][$school_id] : '',
//                '2019-07_key3'=> isset($school_accountant['2019-07']) && isset($school_accountant['2019-07'][$school_id]) ? ($school_accountant['2019-07'][$school_id] ? $school_accountant['2019-07'][$school_id] : '0') : '0',
//                '2019-07_key2'=> isset($school_marketer['2019-07']) && isset($school_marketer['2019-07'][$school_id]) ? $school_marketer['2019-07'][$school_id] : '',
//                '2019-07_key1'=> isset($school_after_man['2019-07']) && isset($school_after_man['2019-07'][$school_id]) ? $school_after_man['2019-07'][$school_id] : '',
//                '2019-06_key3'=> isset($school_accountant['2019-06']) && isset($school_accountant['2019-06'][$school_id]) ? ($school_accountant['2019-06'][$school_id] ? $school_accountant['2019-06'][$school_id] : '0') : '0',
//                '2019-06_key2'=> isset($school_marketer['2019-06']) && isset($school_marketer['2019-06'][$school_id]) ? $school_marketer['2019-06'][$school_id] : '',
//                '2019-06_key1'=> isset($school_after_man['2019-06']) && isset($school_after_man['2019-06'][$school_id]) ? $school_after_man['2019-06'][$school_id] : '',
//                '2019-05_key3'=> isset($school_accountant['2019-05']) && isset($school_accountant['2019-05'][$school_id]) ? ($school_accountant['2019-05'][$school_id] ? $school_accountant['2019-05'][$school_id] : '0') : '0',
//                '2019-05_key2'=> isset($school_marketer['2019-05']) && isset($school_marketer['2019-05'][$school_id]) ? $school_marketer['2019-05'][$school_id] : '',
//                '2019-05_key1'=> isset($school_after_man['2019-05']) && isset($school_after_man['2019-05'][$school_id]) ? $school_after_man['2019-05'][$school_id] : '',
//                '2019-04_key3'=> isset($school_accountant['2019-04']) && isset($school_accountant['2019-04'][$school_id]) ? ($school_accountant['2019-04'][$school_id] ? $school_accountant['2019-04'][$school_id] : '0') : '0',
//                '2019-04_key2'=> isset($school_marketer['2019-04']) && isset($school_marketer['2019-04'][$school_id]) ? $school_marketer['2019-04'][$school_id] : '',
//                '2019-04_key1'=> isset($school_after_man['2019-04']) && isset($school_after_man['2019-04'][$school_id]) ? $school_after_man['2019-04'][$school_id] : '',
//                '2019-03_key3'=> isset($school_accountant['2019-03']) && isset($school_accountant['2019-03'][$school_id]) ? ($school_accountant['2019-03'][$school_id] ? $school_accountant['2019-03'][$school_id] : '0') : '0',
//                '2019-03_key2'=> isset($school_marketer['2019-03']) && isset($school_marketer['2019-03'][$school_id]) ? $school_marketer['2019-03'][$school_id] : '',
//                '2019-03_key1'=> isset($school_after_man['2019-03']) && isset($school_after_man['2019-03'][$school_id]) ? $school_after_man['2019-03'][$school_id] : '',
//                '2019-02_key3'=> isset($school_accountant['2019-02']) && isset($school_accountant['2019-02'][$school_id]) ? ($school_accountant['2019-02'][$school_id] ? $school_accountant['2019-02'][$school_id] : '0') : '0',
//                '2019-02_key2'=> isset($school_marketer['2019-02']) && isset($school_marketer['2019-02'][$school_id]) ? $school_marketer['2019-02'][$school_id] : '',
//                '2019-02_key1'=> isset($school_after_man['2019-02']) && isset($school_after_man['2019-02'][$school_id]) ? $school_after_man['2019-02'][$school_id] : '',
//                '2019-01_key3'=> isset($school_accountant['2019-01']) && isset($school_accountant['2019-01'][$school_id]) ? ($school_accountant['2019-01'][$school_id] ? $school_accountant['2019-01'][$school_id] : '0') : '0',
//                '2019-01_key2'=> isset($school_marketer['2019-01']) && isset($school_marketer['2019-01'][$school_id]) ? $school_marketer['2019-01'][$school_id] : '',
//                '2019-01_key1'=> isset($school_after_man['2019-01']) && isset($school_after_man['2019-01'][$school_id]) ? $school_after_man['2019-01'][$school_id] : '',
//                '2018-12_key3'=> isset($school_accountant['2018-12']) && isset($school_accountant['2018-12'][$school_id]) ? ($school_accountant['2018-12'][$school_id] ? $school_accountant['2018-12'][$school_id] : '0') : '0',
//                '2018-12_key2'=> isset($school_marketer['2018-12']) && isset($school_marketer['2018-12'][$school_id]) ? $school_marketer['2018-12'][$school_id] : '',
//                '2018-12_key1'=> isset($school_after_man['2018-12']) && isset($school_after_man['2018-12'][$school_id]) ? $school_after_man['2018-12'][$school_id] : '',
//                '2018-11_key3'=> isset($school_accountant['2018-11']) && isset($school_accountant['2018-11'][$school_id]) ? ($school_accountant['2018-11'][$school_id] ? $school_accountant['2018-11'][$school_id] : '0') : '0',
//                '2018-11_key2'=> isset($school_marketer['2018-11']) && isset($school_marketer['2018-11'][$school_id]) ? $school_marketer['2018-11'][$school_id] : '',
//                '2018-11_key1'=> isset($school_after_man['2018-11']) && isset($school_after_man['2018-11'][$school_id]) ? $school_after_man['2018-11'][$school_id] : '',
//                '2018-10_key3'=> isset($school_accountant['2018-10']) && isset($school_accountant['2018-10'][$school_id]) ? ($school_accountant['2018-10'][$school_id] ? $school_accountant['2018-10'][$school_id] : '0') : '0',
//                '2018-10_key2'=> isset($school_marketer['2018-10']) && isset($school_marketer['2018-10'][$school_id]) ? $school_marketer['2018-10'][$school_id] : '',
//                '2018-10_key1'=> isset($school_after_man['2018-10']) && isset($school_after_man['2018-10'][$school_id]) ? $school_after_man['2018-10'][$school_id] : '',
//                '2018-09_key3'=> isset($school_accountant['2018-09']) && isset($school_accountant['2018-09'][$school_id]) ? ($school_accountant['2018-09'][$school_id] ? $school_accountant['2018-09'][$school_id] : '0') : '0',
//                '2018-09_key2'=> isset($school_marketer['2018-09']) && isset($school_marketer['2018-09'][$school_id]) ? $school_marketer['2018-09'][$school_id] : '',
//                '2018-09_key1'=> isset($school_after_man['2018-09']) && isset($school_after_man['2018-09'][$school_id]) ? $school_after_man['2018-09'][$school_id] : '',
//                '2018-08_key3'=> isset($school_accountant['2018-08']) && isset($school_accountant['2018-08'][$school_id]) ? ($school_accountant['2018-08'][$school_id] ? $school_accountant['2018-08'][$school_id] : '0') : '0',
//                '2018-08_key2'=> isset($school_marketer['2018-08']) && isset($school_marketer['2018-08'][$school_id]) ? $school_marketer['2018-08'][$school_id] : '',
//                '2018-08_key1'=> isset($school_after_man['2018-08']) && isset($school_after_man['2018-08'][$school_id]) ? $school_after_man['2018-08'][$school_id] : '',
//                '2018-07_key3'=> isset($school_accountant['2018-07']) && isset($school_accountant['2018-07'][$school_id]) ? ($school_accountant['2018-07'][$school_id] ? $school_accountant['2018-07'][$school_id] : '0') : '0',
//                '2018-07_key2'=> isset($school_marketer['2018-07']) && isset($school_marketer['2018-07'][$school_id]) ? $school_marketer['2018-07'][$school_id] : '',
//                '2018-07_key1'=> isset($school_after_man['2018-07']) && isset($school_after_man['2018-07'][$school_id]) ? $school_after_man['2018-07'][$school_id] : '',
//                '2018-06_key3'=> isset($school_accountant['2018-06']) && isset($school_accountant['2018-06'][$school_id]) ? ($school_accountant['2018-06'][$school_id] ? $school_accountant['2018-06'][$school_id] : '0') : '0',
//                '2018-06_key2'=> isset($school_marketer['2018-06']) && isset($school_marketer['2018-06'][$school_id]) ? $school_marketer['2018-06'][$school_id] : '',
//                '2018-06_key1'=> isset($school_after_man['2018-06']) && isset($school_after_man['2018-06'][$school_id]) ? $school_after_man['2018-06'][$school_id] : '',
//                '2018-05_key3'=> isset($school_accountant['2018-05']) && isset($school_accountant['2018-05'][$school_id]) ? ($school_accountant['2018-05'][$school_id] ? $school_accountant['2018-05'][$school_id] : '0') : '0',
//                '2018-05_key2'=> isset($school_marketer['2018-05']) && isset($school_marketer['2018-05'][$school_id]) ? $school_marketer['2018-05'][$school_id] : '',
//                '2018-05_key1'=> isset($school_after_man['2018-05']) && isset($school_after_man['2018-05'][$school_id]) ? $school_after_man['2018-05'][$school_id] : '',
//            ];

            $school_record[] = [
                'school_id' => $school_id,
                'school_name' => $school_item['name'],
                'create_date' => substr($school_item['created_at'], 0,10 ),

                'class' => $school_item['class'],
                'sheng' => isset($region_arr[0]) ? $region_arr[0] : '',
                'shi' => isset($region_arr[1]) ? $region_arr[1] : '',
                'qu' => isset($region_arr[2]) ? $region_arr[2] : '',
                'marketer' => $school_item['marketer'],
                'after_man' => $school_item['after_man'],

                '2020' =>     isset($month2020[$school_id]) ? ($month2020[$school_id] ? $month2020[$school_id] : '0') : '0',
                '1819' =>     isset($month1819[$school_id]) ? ($month1819[$school_id] ? $month1819[$school_id] : '0') : '0',

                '2020-04_key3'=> isset($school_accountant['2020-04']) && isset($school_accountant['2020-04'][$school_id]) ? ($school_accountant['2020-04'][$school_id] ? $school_accountant['2020-04'][$school_id] : '0') : '0',
                '2020-03_key3'=> isset($school_accountant['2020-03']) && isset($school_accountant['2020-03'][$school_id]) ? ($school_accountant['2020-03'][$school_id] ? $school_accountant['2020-03'][$school_id] : '0') : '0',
                '2020-02_key3'=> isset($school_accountant['2020-02']) && isset($school_accountant['2020-02'][$school_id]) ? ($school_accountant['2020-02'][$school_id] ? $school_accountant['2020-02'][$school_id] : '0') : '0',
                '2020-01_key3'=> isset($school_accountant['2020-01']) && isset($school_accountant['2020-01'][$school_id]) ? ($school_accountant['2020-01'][$school_id] ? $school_accountant['2020-01'][$school_id] : '0') : '0',
                '2019-12_key3'=> isset($school_accountant['2019-12']) && isset($school_accountant['2019-12'][$school_id]) ? ($school_accountant['2019-12'][$school_id] ? $school_accountant['2019-12'][$school_id] : '0') : '0',
                //'2019-12_key2'=> isset($school_marketer['2019-12']) && isset($school_marketer['2019-12'][$school_id]) ? $school_marketer['2019-12'][$school_id] : '',
                //'2019-12_key1'=> isset($school_after_man['2019-12']) && isset($school_after_man['2019-12'][$school_id]) ? $school_after_man['2019-12'][$school_id] : '',
                '2019-11_key3'=> isset($school_accountant['2019-11']) && isset($school_accountant['2019-11'][$school_id]) ? ($school_accountant['2019-11'][$school_id] ? $school_accountant['2019-11'][$school_id] : '0') : '0',
                //'2019-11_key2'=> isset($school_marketer['2019-11']) && isset($school_marketer['2019-11'][$school_id]) ? $school_marketer['2019-11'][$school_id] : '',
                //'2019-11_key1'=> isset($school_after_man['2019-11']) && isset($school_after_man['2019-11'][$school_id]) ? $school_after_man['2019-11'][$school_id] : '',
                '2019-10_key3'=> isset($school_accountant['2019-10']) && isset($school_accountant['2019-10'][$school_id]) ? ($school_accountant['2019-10'][$school_id] ? $school_accountant['2019-10'][$school_id] : '0') : '0',
                //'2019-10_key2'=> isset($school_marketer['2019-10']) && isset($school_marketer['2019-10'][$school_id]) ? $school_marketer['2019-10'][$school_id] : '',
                //'2019-10_key1'=> isset($school_after_man['2019-10']) && isset($school_after_man['2019-10'][$school_id]) ? $school_after_man['2019-10'][$school_id] : '',
                '2019-09_key3'=> isset($school_accountant['2019-09']) && isset($school_accountant['2019-09'][$school_id]) ? ($school_accountant['2019-09'][$school_id] ? $school_accountant['2019-09'][$school_id] : '0') : '0',
                //'2019-09_key2'=> isset($school_marketer['2019-09']) && isset($school_marketer['2019-09'][$school_id]) ? $school_marketer['2019-09'][$school_id] : '',
                //'2019-09_key1'=> isset($school_after_man['2019-09']) && isset($school_after_man['2019-09'][$school_id]) ? $school_after_man['2019-09'][$school_id] : '',
                '2019-08_key3'=> isset($school_accountant['2019-08']) && isset($school_accountant['2019-08'][$school_id]) ? ($school_accountant['2019-08'][$school_id] ? $school_accountant['2019-08'][$school_id] : '0') : '0',
                //'2019-08_key2'=> isset($school_marketer['2019-08']) && isset($school_marketer['2019-08'][$school_id]) ? $school_marketer['2019-08'][$school_id] : '',
                //'2019-08_key1'=> isset($school_after_man['2019-08']) && isset($school_after_man['2019-08'][$school_id]) ? $school_after_man['2019-08'][$school_id] : '',
                '2019-07_key3'=> isset($school_accountant['2019-07']) && isset($school_accountant['2019-07'][$school_id]) ? ($school_accountant['2019-07'][$school_id] ? $school_accountant['2019-07'][$school_id] : '0') : '0',
                //'2019-07_key2'=> isset($school_marketer['2019-07']) && isset($school_marketer['2019-07'][$school_id]) ? $school_marketer['2019-07'][$school_id] : '',
                //'2019-07_key1'=> isset($school_after_man['2019-07']) && isset($school_after_man['2019-07'][$school_id]) ? $school_after_man['2019-07'][$school_id] : '',
                '2019-06_key3'=> isset($school_accountant['2019-06']) && isset($school_accountant['2019-06'][$school_id]) ? ($school_accountant['2019-06'][$school_id] ? $school_accountant['2019-06'][$school_id] : '0') : '0',
                //'2019-06_key2'=> isset($school_marketer['2019-06']) && isset($school_marketer['2019-06'][$school_id]) ? $school_marketer['2019-06'][$school_id] : '',
                //'2019-06_key1'=> isset($school_after_man['2019-06']) && isset($school_after_man['2019-06'][$school_id]) ? $school_after_man['2019-06'][$school_id] : '',
                '2019-05_key3'=> isset($school_accountant['2019-05']) && isset($school_accountant['2019-05'][$school_id]) ? ($school_accountant['2019-05'][$school_id] ? $school_accountant['2019-05'][$school_id] : '0') : '0',
                //'2019-05_key2'=> isset($school_marketer['2019-05']) && isset($school_marketer['2019-05'][$school_id]) ? $school_marketer['2019-05'][$school_id] : '',
                //'2019-05_key1'=> isset($school_after_man['2019-05']) && isset($school_after_man['2019-05'][$school_id]) ? $school_after_man['2019-05'][$school_id] : '',
                '2019-04_key3'=> isset($school_accountant['2019-04']) && isset($school_accountant['2019-04'][$school_id]) ? ($school_accountant['2019-04'][$school_id] ? $school_accountant['2019-04'][$school_id] : '0') : '0',
                //'2019-04_key2'=> isset($school_marketer['2019-04']) && isset($school_marketer['2019-04'][$school_id]) ? $school_marketer['2019-04'][$school_id] : '',
                //'2019-04_key1'=> isset($school_after_man['2019-04']) && isset($school_after_man['2019-04'][$school_id]) ? $school_after_man['2019-04'][$school_id] : '',
                '2019-03_key3'=> isset($school_accountant['2019-03']) && isset($school_accountant['2019-03'][$school_id]) ? ($school_accountant['2019-03'][$school_id] ? $school_accountant['2019-03'][$school_id] : '0') : '0',
                //'2019-03_key2'=> isset($school_marketer['2019-03']) && isset($school_marketer['2019-03'][$school_id]) ? $school_marketer['2019-03'][$school_id] : '',
                //'2019-03_key1'=> isset($school_after_man['2019-03']) && isset($school_after_man['2019-03'][$school_id]) ? $school_after_man['2019-03'][$school_id] : '',
                '2019-02_key3'=> isset($school_accountant['2019-02']) && isset($school_accountant['2019-02'][$school_id]) ? ($school_accountant['2019-02'][$school_id] ? $school_accountant['2019-02'][$school_id] : '0') : '0',
                //'2019-02_key2'=> isset($school_marketer['2019-02']) && isset($school_marketer['2019-02'][$school_id]) ? $school_marketer['2019-02'][$school_id] : '',
                //'2019-02_key1'=> isset($school_after_man['2019-02']) && isset($school_after_man['2019-02'][$school_id]) ? $school_after_man['2019-02'][$school_id] : '',
                '2019-01_key3'=> isset($school_accountant['2019-01']) && isset($school_accountant['2019-01'][$school_id]) ? ($school_accountant['2019-01'][$school_id] ? $school_accountant['2019-01'][$school_id] : '0') : '0',
                //'2019-01_key2'=> isset($school_marketer['2019-01']) && isset($school_marketer['2019-01'][$school_id]) ? $school_marketer['2019-01'][$school_id] : '',
                //'2019-01_key1'=> isset($school_after_man['2019-01']) && isset($school_after_man['2019-01'][$school_id]) ? $school_after_man['2019-01'][$school_id] : '',
                '2018-12_key3'=> isset($school_accountant['2018-12']) && isset($school_accountant['2018-12'][$school_id]) ? ($school_accountant['2018-12'][$school_id] ? $school_accountant['2018-12'][$school_id] : '0') : '0',
                //'2018-12_key2'=> isset($school_marketer['2018-12']) && isset($school_marketer['2018-12'][$school_id]) ? $school_marketer['2018-12'][$school_id] : '',
                //'2018-12_key1'=> isset($school_after_man['2018-12']) && isset($school_after_man['2018-12'][$school_id]) ? $school_after_man['2018-12'][$school_id] : '',
                '2018-11_key3'=> isset($school_accountant['2018-11']) && isset($school_accountant['2018-11'][$school_id]) ? ($school_accountant['2018-11'][$school_id] ? $school_accountant['2018-11'][$school_id] : '0') : '0',
                //'2018-11_key2'=> isset($school_marketer['2018-11']) && isset($school_marketer['2018-11'][$school_id]) ? $school_marketer['2018-11'][$school_id] : '',
                //'2018-11_key1'=> isset($school_after_man['2018-11']) && isset($school_after_man['2018-11'][$school_id]) ? $school_after_man['2018-11'][$school_id] : '',
                '2018-10_key3'=> isset($school_accountant['2018-10']) && isset($school_accountant['2018-10'][$school_id]) ? ($school_accountant['2018-10'][$school_id] ? $school_accountant['2018-10'][$school_id] : '0') : '0',
                //'2018-10_key2'=> isset($school_marketer['2018-10']) && isset($school_marketer['2018-10'][$school_id]) ? $school_marketer['2018-10'][$school_id] : '',
                //'2018-10_key1'=> isset($school_after_man['2018-10']) && isset($school_after_man['2018-10'][$school_id]) ? $school_after_man['2018-10'][$school_id] : '',
                '2018-09_key3'=> isset($school_accountant['2018-09']) && isset($school_accountant['2018-09'][$school_id]) ? ($school_accountant['2018-09'][$school_id] ? $school_accountant['2018-09'][$school_id] : '0') : '0',
                //'2018-09_key2'=> isset($school_marketer['2018-09']) && isset($school_marketer['2018-09'][$school_id]) ? $school_marketer['2018-09'][$school_id] : '',
                //'2018-09_key1'=> isset($school_after_man['2018-09']) && isset($school_after_man['2018-09'][$school_id]) ? $school_after_man['2018-09'][$school_id] : '',
                '2018-08_key3'=> isset($school_accountant['2018-08']) && isset($school_accountant['2018-08'][$school_id]) ? ($school_accountant['2018-08'][$school_id] ? $school_accountant['2018-08'][$school_id] : '0') : '0',
                //'2018-08_key2'=> isset($school_marketer['2018-08']) && isset($school_marketer['2018-08'][$school_id]) ? $school_marketer['2018-08'][$school_id] : '',
                //'2018-08_key1'=> isset($school_after_man['2018-08']) && isset($school_after_man['2018-08'][$school_id]) ? $school_after_man['2018-08'][$school_id] : '',
                '2018-07_key3'=> isset($school_accountant['2018-07']) && isset($school_accountant['2018-07'][$school_id]) ? ($school_accountant['2018-07'][$school_id] ? $school_accountant['2018-07'][$school_id] : '0') : '0',
                //'2018-07_key2'=> isset($school_marketer['2018-07']) && isset($school_marketer['2018-07'][$school_id]) ? $school_marketer['2018-07'][$school_id] : '',
                //'2018-07_key1'=> isset($school_after_man['2018-07']) && isset($school_after_man['2018-07'][$school_id]) ? $school_after_man['2018-07'][$school_id] : '',
                '2018-06_key3'=> isset($school_accountant['2018-06']) && isset($school_accountant['2018-06'][$school_id]) ? ($school_accountant['2018-06'][$school_id] ? $school_accountant['2018-06'][$school_id] : '0') : '0',
                //'2018-06_key2'=> isset($school_marketer['2018-06']) && isset($school_marketer['2018-06'][$school_id]) ? $school_marketer['2018-06'][$school_id] : '',
                //'2018-06_key1'=> isset($school_after_man['2018-06']) && isset($school_after_man['2018-06'][$school_id]) ? $school_after_man['2018-06'][$school_id] : '',
                '2018-05_key3'=> isset($school_accountant['2018-05']) && isset($school_accountant['2018-05'][$school_id]) ? ($school_accountant['2018-05'][$school_id] ? $school_accountant['2018-05'][$school_id] : '0') : '0',
                //'2018-05_key2'=> isset($school_marketer['2018-05']) && isset($school_marketer['2018-05'][$school_id]) ? $school_marketer['2018-05'][$school_id] : '',
                //'2018-05_key1'=> isset($school_after_man['2018-05']) && isset($school_after_man['2018-05'][$school_id]) ? $school_after_man['2018-05'][$school_id] : '',
            ];
        }

        $this->store('在线助教数据_'.rand(0,100), $school_record, '.xlsx');

        dd('done');
    }


    public function getLevel($num)
    {
        if ($num <= 0) return 'A';
        if ($num <= 1000) return 'B';
        if ($num <= 3000) return 'C';
        if ($num <= 5000) return 'D';
        if ($num <= 10000) return 'E';
        if ($num <= 20000) return 'F';
        if ($num <= 50000) return 'G';
        return 'H';
    }


    private $filePath; //文件路径数组
    protected function traverse($path = '.')
    {
        $current_dir = opendir($path);    //opendir()返回一个目录句柄,失败返回false
        while (($file = readdir($current_dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
            if ($file == '.' || $file == '..') {
                continue;
            } else if (is_dir($sub_dir)) {    //如果是目录,进行递归
                $this->traverse($sub_dir); //嵌套遍历子文件夹
            } else {    //如果是文件,直接输出路径和文件名
                $path_tmp = str_replace('/home/vagrant/code/sql_analyze/storage/imports', '',$path);
                $this->filePath[] = $path_tmp . '/' . $file;//把文件路径赋值给数组
            }
        }
        return $this->filePath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {


        ini_set ('memory_limit', '1024M');

        config(['database.default' => 'online']); // 3.1-4.9
        $res = SchoolAccountant::getSchoolAccountant('2020-10-01','2021-02-26');
        $this->store('对账单_2020-10-01_2021-02-26'.rand(0,100), $res, '.xlsx');
        dd('done' );
//
//
//
//
//        ini_set ('memory_limit', '1024M');
//
//        $this->handleSchoolInfo();
//
//        dd('done');

//        config(['database.default' => 'online']); // 3.1-4.9
//        $res = SchoolAccountant::getSchoolAccountant('2020-05-01','2020-06-19');
//        $this->store('对账单_2020-05-01_2020-06-19'.rand(0,100), $res, '.xlsx');
//        dd('done' );

//        $tmp = new ExportBookLearningProcess();
//
//        $tmp->handle();
//
//        dd();
//        ini_set ('memory_limit', '1024M');
//        $this->handleSchoolInfo_v2();
//
//        dd('done');
//
//
        config(['database.default' => 'local']);
//
        $re = \DB::table('accountant_statement')->where('type', 'monthPayment')->where('label_id', 8)->get();
//
        foreach ($re as $item){
            $month = str_replace('年','-',$item->content);
            $month = str_replace('月 线上结算额','',$month);
            \DB::table('accountant_statement')->where('id', $item->id)->update([
                'code'=>$month
            ]);
            echo '+';
        }


        dd('1212121222');




        $this->handleSchoolInfo();

        dd('done');

        $sql = <<<EOF
SELECT
	activity.id,activity.`name`,  user_account.nickname  ,activity.days,activity.start_at, activity.end_at 
FROM
	`b_vanthink_online`.`activity` 
	left join user_account on user_account.id = activity.account_id
WHERE
	activity.`school_id` = '1348' 
	AND activity.`deleted_at` IS NULL
EOF;



        $activity_info = \DB::select(\DB::raw($sql));

        $activity_info = json_decode(json_encode($activity_info),true);


        $rep = [];
        $rep[] = [
            "id" => '活动id',
            "name" => "活动名称",
            "nickname" => "老师",
            "days" => '活动天数',
            "start_at" => "开始时间",
            "end_at" => "结束时间",
            "num" => '记录时间',
            'total' =>'总人数',
            'join_count' =>'参与人数',
            'join_rate' => '参与率'
        ];

        foreach ($activity_info as $activity_item){
            
            // 一个 活动
            $activity_id = $activity_item['id'];

            $days_count  = $activity_item['days'];

            $start_at  = $activity_item['start_at'];

            $end_at = $activity_item['end_at'];

            // 每一天的 数据
            for($i = 0;$i<$days_count; $i++){
                //计算时间
                $start_time = Carbon::parse($start_at)->addDays($i)->startOfDay()->toDateTimeString();
                $end_time = Carbon::parse($start_at)->addDays($i)->endOfDay()->toDateTimeString();

                if (Carbon::parse($start_at)->addDays($i)->gt(Carbon::parse($end_at))){
                    dd('数据错误'. $activity_id);
                }

                if (Carbon::parse($start_at)->addDays($i)->gt(Carbon::now())){
                    continue;
                }
                // 总人数
                $total = \DB::table('activity_student_overview')
                    ->where('activity_id',$activity_id)
                    ->where('created_at','<=',$end_time)
                    ->count();

                // 获得参与人数
                \DB::enableQueryLog();

                $join_count = \DB::table('activity_student_book_record')
                    ->selectRaw('count(DISTINCT student_id)  join_num')
                    ->where('activity_id',$activity_id)
                    ->where('created_at','>=',$start_time)
                    ->where('created_at','<=',$end_time)
                    ->first();
                $join_count = $join_count->join_num;


                $tmp = $i+1;
                $rep[] = [
                    "id" => $activity_id,
                    "name" => $activity_item['name'],
                    "nickname" => $activity_item['nickname'],
                    "days" => $days_count,
                    "start_at" => $start_at,
                    "end_at" => $end_at,
                    "num" => "第".$tmp."天",
                    'total' =>$total ? $total : '0',
                    'join_count' =>$join_count ? $join_count : '0',
                    'join_rate' => empty($total) ?  '0' : round(($join_count / $total) * 100 ,1)
                ];

            }

            echo '+';


        }

        $this->store('1348_建昌剑桥_打卡活动'.rand(0,100), $rep, '.xlsx');
        dd('done');

        $path = '/home/vagrant/code/sql_analyze/storage/imports';
        $this->traverse($path);

        $this->output->progressStart(count($this->filePath));

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getConnPdo('core', $pdo_type);

        $save_data = [];
        foreach ($this->filePath as  $file) {
            if (strpos($file, '.gitignore') || strpos($file, 'abc')) {
                $this->output->progressAdvance();
                continue;
            }
            \Log::info($file);
            $contents = $this->import($file);
            $header = array_shift($contents);
            $header = array_filter($header);
            $key_tran = array_flip($header);
            ###检查文件####
            #$save_count = 0;
            #$empty_count = 0;
            ##############
            foreach ($contents as $content) {
                ###检查文件####
//                if(empty($content[$key_tran['单词']])&& empty($content[$key_tran['词性1']])
//                    && empty($content[$key_tran['解释1']]) && empty($content[$key_tran['最小标签的ID']])){
//                    $empty_count++;
//                    if ($save_count<3&&$empty_count>5){
//                        \Log::info($file);
//                        break;
//                    }
//                    continue;
//                }
//                $save_count++;
                ##############

                if(empty($content[$key_tran['单词']])&& empty($content[$key_tran['词性1']])
                    && empty($content[$key_tran['解释1']]) && empty($content[$key_tran['最小标签的ID']])){
                    continue;
                }
                $word = trim($content[$key_tran['单词']]);
                $speed_1 = $this->handleSpeed($content[$key_tran['词性1']]);
                $trans_1 = $this->handleTranslation($content[$key_tran['解释1']]);
                $label_id = trim($content[$key_tran['最小标签的ID']]);


                if (!isset($save_data[$word])){
                    $save_data[$word] = [];
                }
                if (!isset($save_data[$word][$speed_1])){
                    $save_data[$word][$speed_1] = [];
                }
                if (!isset($save_data[$word][$speed_1][$trans_1])){
                    $this->handleTrans($save_data[$word][$speed_1], $trans_1);
//                    $save_data[$word][$speed_1][$trans_1] = [];
                }
                $save_data[$word][$speed_1][$trans_1][] = $label_id;


                if (isset($key_tran['词性2'])&&isset($key_tran['解释2'])&&!empty($content[$key_tran['词性2']]) && empty($content[$key_tran['解释2']])){
                    $speed_2 = $this->handleSpeed($content[$key_tran['词性2']]);
                    $trans_2 = $this->handleTranslation($content[$key_tran['解释2']]);

                    if (!isset($save_data[$word][$speed_2])){
                        $save_data[$word][$speed_2] = [];
                    }
                    if (!isset($save_data[$word][$speed_2][$trans_2])){
                        $this->handleTrans($save_data[$word][$speed_2], $trans_2);
//                        $save_data[$word][$speed_2][$trans_2] = [];
                    }
                    $save_data[$word][$speed_2][$trans_2][] = $label_id;

                }
            }
            $this->output->progressAdvance();
        }


        // 获取 库里的 数据
        $vocabulary = array_keys($save_data);
        $words_str = str_repeat("?,", count($vocabulary)-1) . "?";;
        // 获得所有单词的例句解释
        $sql = 'select  `vocabulary`,`wordbank_id`,`sentence` , `explain` from `wordbank_sentence` 
                INNER  JOIN  `wordbank` ON `wordbank`.`id` = `wordbank_sentence`.`wordbank_id`
                where `vocabulary` in ('.$words_str.') and `wordbank_sentence`.`deleted_at` is null 
                and `wordbank`.`deleted_at` is null';
        $res  = $pdo->prepare($sql);
        $res->execute($vocabulary);
        $word_sentence_list = $res->fetchAll(\PDO::FETCH_ASSOC  );
        $word_sentence_list = collect($word_sentence_list)->keyBy('vocabulary')->toArray();

        // 获取所有的单词
        $sql = 'select  `vocabulary`,`id` from `wordbank` where `vocabulary` in ('.$words_str.') and `wordbank`.`deleted_at` is null';
        $res  = $pdo->prepare($sql);
        $res->execute($vocabulary);
        $word_list = $res->fetchAll(\PDO::FETCH_ASSOC  );
        $word_list = collect($word_list)->pluck('id','vocabulary')->toArray();

        // 拼接数据
        $export_data = [];
        $export_data[] = ['vocabulary', 'part_of_speech', 'translation',  'sentence', 'explain', 'word_id','label_ids'];
        foreach ($save_data as $word=>$speeds){
            $is_first = true;
            $had_word =     isset($word_list[$word])? 1 : 0;
            $had_sentence = isset($word_sentence_list[$word])? 1 : 0;
            foreach ($speeds as $speed=>$trans){
                foreach ($trans as $tran=>$labels){
                    if (!$had_word){
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            '',
                            '',
                            '单词未录入',
                            implode(',', $labels)
                        ];
                        continue;
                    }
                    if (!$had_sentence){
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            '单词未上传例句',
                            '单词未上传例句',
                            $word_list[$word],
                            implode(',', $labels)
                        ];
                        continue;
                    }
                    if ($is_first){
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            $word_sentence_list[$word]['sentence'],
                            $word_sentence_list[$word]['explain'],
                            $word_list[$word],
                            implode(',', $labels)
                        ];
                        $is_first = false;
                    }else{
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            '',
                            '',
                            $word_list[$word],
                            implode(',', $labels)
                        ];
                    }
                }
            }
        }

        // 保存到文件
        $this->store('单词例句解释'.rand(0,100), $export_data, '.xlsx');
        $this->output->progressFinish();
    }

    // 处理词性
    public function handleSpeed($speed)
    {
        $speed = str_replace(chr(194).chr(160), ' ',$speed);
        $speed = trim($speed);
        if (strpos($speed, '.')===false){
            $speed = $speed.'.';
        }
        return $speed;
    }

    // 处理解释
    public function handleTranslation($translation)
    {
        $translation = trim($translation);

        $trans1 = [
            ";"     => "；",
            "，"    => "；",
            ","     => "；",
            "："     => "；",

            "..."   => "......",
            "……"    => "......",
            "⋯⋯"    => "......",


            "("    => "（",
            ")"    => "）",



            "0"     => '零',
            "—"     => "一",
            "1"     => "一",
            "2"     => "二",
            "3"     => "三",
            "4"     => "四",
            "5"     => "五",
            "6"     => "六",
            "7"     => "七",
            "8"     => "八",
            "9"     => "九",
            "10"     => "十",
            "11"     => "十一",
            "12"     => "十二",
            "13"     => "十三",
            "14"     => "十四",
            "15"     => "十五",
            "16"     => "十六",
            "17"     => "十七",
            "18"     => "十八",
            "19"     => "十九",
            "20"     => "二十",
            "21"     => "二十一",
            "30"     => "三十",
            "40"     => "四十",
            "50"     => "五十",
            "60"     => "六十",
            "70"     => "七十",
            "80"     => "八十",
            "90"     => "九十",
            "100"    => "一百",

            '周一'		=> '星期一',
            '周二'		=> '星期二',
            '周三'		=> '星期三',
            '周四'		=> '星期四',
            '周五'		=> '星期五',
            '周六'		=> '星期六',
            '星期天'		=> '星期日',
            '周日'		=> '星期日',

        ];


        $translation = strtr($translation, $trans1);

        return $translation;
    }

    // 处理解释
    public function handleTrans(&$list, &$item)
    {
        if (count($list)){
            $is_did = false;
            // 已存在
            $trans_list = array_keys($list);
            foreach ($trans_list as $old_tran){
                $old_tran_list = explode('；', $old_tran);
                $item_list = explode('；', $item);
                if (count($old_tran_list) > 1 && count($item_list) > 1){
                    // 两个相等
                    if (count($old_tran_list) == count(array_intersect($old_tran_list, $item_list))){
                        $item = $old_tran;
                        $is_did = true;
                        break;
                    }
                    // old 大
                    if (count(array_diff($old_tran_list,$item_list)) && !count(array_diff($item_list,$old_tran_list))){
                        $item = $old_tran;
                        $is_did = true;
                        break;
                    }

                    // item 大
                    if (!count(array_diff($old_tran_list,$item_list)) && count(array_diff($item_list,$old_tran_list))){
                        $tmp = $list[$old_tran];
                        $list[$item] = $tmp;
                        unset($list[$old_tran]);

                        $is_did = true;
                        break;
                    }

                    // 互不覆盖
                }


                if (count($old_tran_list) == 1  && count($item_list) > 1){
                    if (in_array($old_tran, $item_list)){
                        if (!isset($list[$item])){
                            $list[$item] = [];
                        }
                        $list[$item] = array_merge($list[$item], $list[$old_tran]);
                        unset($list[$old_tran]);
                    }
                    $is_did = true;
                    continue;
                }

                if (count($old_tran_list) > 1 && count($item_list) == 1){
                    if (in_array($item, $old_tran_list)){
                        $item = $old_tran;
                        $is_did = true;
                        break;
                    }
                }

                if (count($old_tran_list) == 1 && count($item_list) == 1){
                    if($old_tran == $item){
                        $is_did = true;
                        break;
                    }
                }
            }

            if (!$is_did){
                $list[$item] = [];
            }
        }else{
            $list[$item] = [];
        }
    }
}
