<?php

namespace App\Console\Commands\ZXZJ;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportOperatorData extends Command
{
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:operator:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出在线助教 运营数据';

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
//        config(['database.default' => 'local']);

        $time = Carbon::parse($month)->endOfMonth()->endOfDay()->toDateTimeString();

        $sql = <<<EOF
select 
  school_info.school_id,
  if(`offline`.offline_fee ,offline.offline_fee , 0) offline_total,
  if(`online`.online_fee ,`online`.online_fee , 0) online_total,
  if(`finance`.finance_fee ,`finance`.finance_fee , 0) finance_total ,
  if(`qingke`.finance_fee ,`qingke`.finance_fee , 0) qingke_total,
  if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0)  
	+ if(`finance`.finance_fee ,`finance`.finance_fee , 0) + if(`qingke`.finance_fee ,`qingke`.finance_fee , 0) total_fee
from 
(
  SELECT DISTINCT
    school_id 
  FROM
    accountant_statement
) school_info 

left join (
## 线下
  SELECT
    school_id,
    sum( fee ) * - 1 offline_fee 
  FROM
    `accountant_statement` 
  WHERE
    created_at <= '$time'
    AND type IN ( 'schoolOfflinePayment', 'schoolOfflineRefund', 'offlinePayment', 'offlineRefund' ) 
  GROUP BY
    school_id
) offline on offline.school_id = school_info.school_id

left join (
## 线上  
  SELECT
    school_id,
    sum( fee ) * - 1 online_fee 
  FROM
    `accountant_statement` 
  WHERE
    `label_id` = '8' 
    AND `extra` <= '$month'
    AND has_rollback = 0 
  GROUP BY
    school_id
  ) `online` on `online`.school_id = school_info.school_id

  left join (
## 财务
  SELECT
    school_id,
    sum( fee ) * - 1 finance_fee 
  FROM
    `accountant_statement` 
  WHERE
    `type` = 'receipt' 
    AND label_id IN ( 11, 12 ) 
   and created_at <= '$time'
    AND has_rollback = 0 
  GROUP BY
    school_id
) finance on `finance`.school_id = school_info.school_id


 left join (
## 轻课 
  SELECT
    school_id,
    sum( fee )  finance_fee 
  FROM
    `accountant_statement` 
  WHERE
        `label_id` = 32
    AND `extra` <= '$month'
    AND has_rollback = 0 
  GROUP BY
    school_id
) qingke on qingke.school_id = school_info.school_id

ORDER BY  school_info.school_id
EOF;

        $record = \DB::select(\DB::raw($sql));


        return array_combine(
            array_column($record, 'school_id'),
            array_column($record, 'total_fee')
        );
    }


    public function handleSchoolInfo()
    {
        config(['database.default' => 'zxzj_online_search']);

        $month_12_info = $this->getTotalInfo('2020-12');
        $month_11_info = $this->getTotalInfo('2020-11');

//        $month_10_info = $this->getTotalInfo('2020-10');


//        $month_9_info = $this->getTotalInfo('2020-09');

//        $month_8_info = $this->getTotalInfo('2020-08');

//        $month_7_info = $this->getTotalInfo('2020-07');

//        $month_6_info = $this->getTotalInfo('2020-06');

//        $month_5_info = $this->getTotalInfo('2020-05');

//        $month_4_info = $this->getTotalInfo('2020-04');

        // 获得 3月底的 数据
//        $month_3_info = $this->getTotalInfo('2020-03');
//        // 获得 2月底的 数据
//        $month_2_info = $this->getTotalInfo('2020-02');
//        // 获得 1月底的 数据
//        $month_1_info = $this->getTotalInfo('2020-01');


//        config(['database.default' => 'online']);

        //todo 修改 日期
        $start = '2020-11-30';
        $end = '2020-12-31';

        $school_record = [];
        $school_record[] = [
            'school_id' => '学校id',
            'school_name' => '学校名称',
            'nickname' => '运营专员',
//            'first_date'=> '月初时间/接手时间',
            'trans_money' => '初始金额(元)',
//            'last_date'=>'月末时间',
            'last_trans_money' => '月末金额(元)',
            'increase' => '增长额(元)',
        ];


        // 查找学校
        $sql = <<<EOF
SELECT 
	school_id, min(date_type) as min_date, max(date_type) as max_date
FROM
	`b_vanthink_online`.`statistic_school_record` 
WHERE
	`date_type` <= '$end' 
	AND `afterSales_id` <> '0' 
	AND `date_type` >= '$start'
	GROUP BY school_id
EOF;

        $school_info = \DB::select(\DB::raw($sql));
        $school_info = json_decode(json_encode($school_info),true);


        foreach ($school_info as $school_item){
            $school_id = $school_item['school_id'];
//            $min_date = $school_item['min_date'];
            $max_date = $school_item['max_date'];
            // 初始化
            $school_init = \DB::table('statistic_school_record')
                ->selectRaw('school.id, school.name, user_account.nickname,statistic_school_record.extra')
                ->leftjoin('user_account','user_account.id', '=', 'statistic_school_record.afterSales_id')
                ->leftjoin('school','school.id', '=', 'statistic_school_record.school_id')
                ->where('statistic_school_record.school_id',$school_id)
                ->where('date_type',$max_date)
                ->first();

//            $school_last = \DB::table('statistic_school_record')
//                ->selectRaw('school.id, school.name, user_account.nickname,statistic_school_record.extra')
//                ->leftjoin('user_account','user_account.id', '=', 'statistic_school_record.afterSales_id')
//                ->leftjoin('school','school.id', '=', 'statistic_school_record.school_id')
//                ->where('statistic_school_record.school_id',$school_id)
//                ->where('date_type',$end)
//                ->first();

//           $first_fee = json_decode($school_init->extra, true)['trans_money'];
//           if ($min_date == $start){
//
//           }
            // todo 开始时间
            $first_fee = isset($month_11_info[$school_id]) ? $month_11_info[$school_id] : 0;

           // todo  结束信息
            $last_fee = isset($month_12_info[$school_id]) ? $month_12_info[$school_id] : 0;

            $school_record[] = [
                'school_id' => $school_id,
                'school_name' => $school_init->name,
                'nickname' => $school_init->nickname,
//                'first_date'=>$min_date,
                'trans_money' => $first_fee ? $first_fee : '0',
//                'last_date'=>$end,
                'last_trans_money' => $last_fee ? $last_fee : '0',
                'increase' => ($last_fee-$first_fee) ? ($last_fee-$first_fee) : '0',
            ];
            echo '+';
        }

        $this->store('12月份数据_'.rand(0,100), $school_record, '.xlsx');

        dd('done');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        ini_set ('memory_limit', '1024M');
        $this->handleSchoolInfo();
        dd(
            'done'
        );
//        $this->handleSchoolInfo_v2();
    }


//        config(['database.default' => 'local']);
//
//        $re = \DB::table('accountant_statement')->where('type', 'monthPayment')->where('label_id', 8)->get();
//
//        foreach ($re as $item){
//            $month = str_replace('年','-',$item->content);
//            $month = str_replace('月 线上结算额','',$month);
//            \DB::table('accountant_statement')->where('id', $item->id)->update([
//                'code'=>$month
//            ]);
//            echo '+';
//        }


//        dd('done');
//
//        ini_set ('memory_limit', '1024M');
//
//        $this->handleSchoolInfo();
//
//        dd('done');


//        $this->handleSchoolInfo();
//
//        dd('done');
//
//        $sql = <<<EOF
//SELECT
//	activity.id,activity.`name`,  user_account.nickname  ,activity.days,activity.start_at, activity.end_at
//FROM
//	`b_vanthink_online`.`activity`
//	left join user_account on user_accoun t.id = activity.account_id
//WHERE
//	activity.`school_id` = '1348'
//	AND activity.`deleted_at` IS NULL
//EOF;
//
//
//
//        $activity_info = \DB::select(\DB::raw($sql));

//        $activity_info = json_decode(json_encode($activity_info),true);
//
//
//        $rep = [];
//        $rep[] = [
//            "id" => '活动id',
//            "name" => "活动名称",
//            "nickname" => "老师",
//            "days" => '活动天数',
//            "start_at" => "开始时间",
//            "end_at" => "结束时间",
//            "num" => '记录时间',
//            'total' =>'总人数',
//            'join_count' =>'参与人数',
//            'join_rate' => '参与率'
//        ];
//
//        foreach ($activity_info as $activity_item){
//
//            // 一个 活动
//            $activity_id = $activity_item['id'];
//
//            $days_count  = $activity_item['days'];
//
//            $start_at  = $activity_item['start_at'];
//
//            $end_at = $activity_item['end_at'];
//
//            // 每一天的 数据
//            for($i = 0;$i<$days_count; $i++){
//                //计算时间
//                $start_time = Carbon::parse($start_at)->addDays($i)->startOfDay()->toDateTimeString();
//                $end_time = Carbon::parse($start_at)->addDays($i)->endOfDay()->toDateTimeString();
//
//                if (Carbon::parse($start_at)->addDays($i)->gt(Carbon::parse($end_at))){
//                    dd('数据错误'. $activity_id);
//                }
//
//                if (Carbon::parse($start_at)->addDays($i)->gt(Carbon::now())){
//                    continue;
//                }
//                // 总人数
//                $total = \DB::table('activity_student_overview')
//                    ->where('activity_id',$activity_id)
//                    ->where('created_at','<=',$end_time)
//                    ->count();
//
//                // 获得参与人数
//                \DB::enableQueryLog();
//
//                $join_count = \DB::table('activity_student_book_record')
//                    ->selectRaw('count(DISTINCT student_id)  join_num')
//                    ->where('activity_id',$activity_id)
//                    ->where('created_at','>=',$start_time)
//                    ->where('created_at','<=',$end_time)
//                    ->first();
//                $join_count = $join_count->join_num;
//
//
//                $tmp = $i+1;
//                $rep[] = [
//                    "id" => $activity_id,
//                    "name" => $activity_item['name'],
//                    "nickname" => $activity_item['nickname'],
//                    "days" => $days_count,
//                    "start_at" => $start_at,
//                    "end_at" => $end_at,
//                    "num" => "第".$tmp."天",
//                    'total' =>$total ? $total : '0',
//                    'join_count' =>$join_count ? $join_count : '0',
//                    'join_rate' => empty($total) ?  '0' : round(($join_count / $total) * 100 ,1)
//                ];
//
//            }
//
//            echo '+';
//
//
//        }
//
//        $this->store('1348_建昌剑桥_打卡活动'.rand(0,100), $rep, '.xlsx');
//        dd('done');
//    }


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

    public function getTotalInfo_v3($month)
    {
        $start_time = Carbon::parse($month)->startOfMonth()->toDateString();
        $end_time = Carbon::parse($month)->endOfMonth()->toDateString();

        $sql = <<<EOF
select 
  school_info.school_id,
  if(`offline`.offline_fee ,offline.offline_fee , 0) offline_total,
  if(`online`.online_fee ,`online`.online_fee , 0) online_total,
  if(`finance`.finance_fee ,`finance`.finance_fee , 0) finance_total ,
  if(`qingke`.finance_fee ,`qingke`.finance_fee , 0) qingke_total,
  if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0)  
	+ if(`finance`.finance_fee ,`finance`.finance_fee , 0) + if(`qingke`.finance_fee ,`qingke`.finance_fee , 0) total_fee
from 
(
  SELECT DISTINCT
    school_id 
  FROM
    accountant_statement
) school_info 

left join (
## 线下
  SELECT
    school_id,
    sum( fee ) * - 1 offline_fee 
  FROM
    `accountant_statement` 
  WHERE

date <= '$end_time'
and date >= '$start_time'
    AND type IN ( 'schoolOfflinePayment', 'schoolOfflineRefund', 'offlinePayment', 'offlineRefund' ) 
  GROUP BY
    school_id
) offline on offline.school_id = school_info.school_id

left join (
## 线上  
  SELECT
    school_id,
    sum( fee ) * - 1 online_fee 
  FROM
    `accountant_statement` 
  WHERE
    `label_id` = '8' 
    AND `extra` = '$month'
    AND has_rollback = 0 
  GROUP BY
    school_id
  ) `online` on `online`.school_id = school_info.school_id

  left join (
## 财务
  SELECT
    school_id,
    sum( fee ) * - 1 finance_fee 
  FROM
    `accountant_statement` 
  WHERE
    `type` = 'receipt' 
    AND label_id IN ( 11, 12 ) 
 and   date <= '$end_time'
and date >= '$start_time'
    AND has_rollback = 0 
  GROUP BY
    school_id
) finance on `finance`.school_id = school_info.school_id


 left join (
## 轻课 
  SELECT
    school_id,
    sum( fee )  finance_fee 
  FROM
    `accountant_statement` 
  WHERE
        `label_id` = 32
    AND `extra` = '$month'
    AND has_rollback = 0 
  GROUP BY
    school_id
) qingke on qingke.school_id = school_info.school_id

ORDER BY  school_info.school_id
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
        config(['database.default' => 'online']);
        $start_month = '2020-01';
        $school_accountant = [];
        for($i=0; $i<12; $i++){
            $month = Carbon::parse($start_month)->addMonths($i)->toDateString();
            $month_key = substr($month,0,7);
//            $str = <<<EOF
//'month_key_key3'=> isset(school_accountant['month_key']) && isset(school_accountant['month_key'][school_id]) ? (school_accountant['month_key'][school_id] ? school_accountant['month_key'][school_id] : '0') : '0',
//'month_key_key2'=> isset(school_marketer['month_key']) && isset(school_marketer['month_key'][school_id]) ? school_marketer['month_key'][school_id] : '',
//'month_key_key1'=> isset(school_after_man['month_key']) && isset(school_after_man['month_key'][school_id]) ? school_after_man['month_key'][school_id] : '',
//EOF;
//            $rr = str_replace('month_key', $month_key , $str);
//            \Log::info($rr);
        echo '+';

//        echo $month_key;

            $school_accountant[$month_key] = $this->getTotalInfo_v3($month_key);
//            $school_accountant[$month_key] = $month_key;
        }




//        config(['database.default' => 'local']);
//        $sql = <<<EOF
//select
//school_id, LEFT(date,7) AS `month`, sum(fee) * -1 AS total_fee
//from `jxc`.`进销存`
//GROUP BY school_id, LEFT(date,7)
// HAVING  total_fee <> 0
//EOF;
//
//        $record = \DB::select(\DB::raw($sql));
//
//        foreach ( $record as $item ){
//
//            $school_accountant[ $item->month ][intval($item->school_id ) ] = $item->total_fee;
//
//        }
//
//        $school_info = json_decode(json_encode($record ) , true);
//
//        dd( $record, $school_info );





//        $school_accountant = collect($school_info) ->groupBy( 'month')
//            ->map(function ($school){
//
////                if( $school -> first() ['month'] = '2020-11'){
////
//////                    dd($school );
////                    dd(  $school->pluck( 'total_fee', 'school_id')->toArray() );
////                }
//                return  $school->pluck( 'total_fee', 'school_id')->toArray();
//            })->toArray();
//        dd( $school_accountant['2020-11'] );
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
            'class' => '学校档位（八月末）',
            'sheng' => '省',
            'shi' => '市',
            'qu' => '区',
            'marketer' => '市场（八月末）',
            'after_man' => '售后（八月末）',
            '2020' =>     '2020年数据（截止到七月）',
            '2019' =>     '2019年结算数据',
            '2018' =>     '2018年结算数据',


            '2020-12_key3'=>'2020-12',
            '2020-11_key3'=>'2020-11',
            '2020-10_key3'=>'2020-10',
            '2020-09_key3'=>'2020-09',

            '2020-08_key3'=>'2020-08',
            '2020-07_key3'=>'2020-07',
            '2020-06_key3'=>'2020-06',
            '2020-05_key3'=>'2020-05',

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
	`date_type` = '2020-12-31' 
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
            date <= '2020-07-31'
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
            AND `code` <= '2020-07'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id
EOF;

//        $record = \DB::select(\DB::raw($sql));


        $month2020 =  [];

//            array_combine(
//            array_column($record, 'school_id'),
//            array_column($record, 'total_fee')
//        );

//        $sql = <<<EOF
//SELECT
//    distinct accountant_statement.school_id,
//    if(offline.offline_fee ,offline.offline_fee , 0),
//    if(`online`.online_fee ,`online`.online_fee , 0),
//		 if(offline.offline_fee ,offline.offline_fee , 0) + if(`online`.online_fee ,`online`.online_fee , 0) total_fee
//FROM
//    `vanthink`.`accountant_statement`
//left join (
//        SELECT
//            school_id,sum(fee) * -1 offline_fee
//        FROM
//            `vanthink`.`accountant_statement`
//        WHERE
//            date <= '2019-12-31'
//            and label_id <> 8
//            GROUP BY school_id
//    )  offline on offline.school_id = accountant_statement.school_id
//left join (
//            SELECT
//            school_id, sum(fee) * -1 online_fee
//        FROM
//            `accountant_statement`
//        WHERE
//            `label_id` = '8'
//            AND `code` <= '2019-12'
//        GROUP BY school_id
//) `online` on `online`.school_id = accountant_statement.school_id
//EOF;
//
//        $record = \DB::select(\DB::raw($sql));
//
//
//        $month1819 =  array_combine(
//            array_column($record, 'school_id'),
//            array_column($record, 'total_fee')
//        );

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
            and date >= '2019-01-01'
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
            AND `code` >= '2019-01'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id
EOF;

//        $record = \DB::select(\DB::raw($sql));


        $month2019 =  [];

//            array_combine(
//            array_column($record, 'school_id'),
//            array_column($record, 'total_fee')
//        );

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
            date <= '2018-12-31'
            and date >= '2018-01-01'
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
            AND `code` <= '2018-12'
            AND `code` >= '2018-01'
        GROUP BY school_id    
) `online` on `online`.school_id = accountant_statement.school_id
EOF;

//        $record = \DB::select(\DB::raw($sql));


        $month2018 =  [];
//            array_combine(
//            array_column($record, 'school_id'),
//            array_column($record, 'total_fee')
//        );


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
                '2019' =>     isset($month2019[$school_id]) ? ($month2019[$school_id] ? $month2019[$school_id] : '0') : '0',
                '2018' =>     isset($month2018[$school_id]) ? ($month2018[$school_id] ? $month2018[$school_id] : '0') : '0',


                '2020-12_key3'=>isset($school_accountant['2020-12']) && isset($school_accountant['2020-12'][$school_id]) ? ($school_accountant['2020-12'][$school_id] ? $school_accountant['2020-12'][$school_id] : '0') : '0',
                '2020-11_key3'=>isset($school_accountant['2020-11']) && isset($school_accountant['2020-11'][$school_id]) ? ($school_accountant['2020-11'][$school_id] ? $school_accountant['2020-11'][$school_id] : '0') : '0',
                '2020-10_key3'=>isset($school_accountant['2020-10']) && isset($school_accountant['2020-10'][$school_id]) ? ($school_accountant['2020-10'][$school_id] ? $school_accountant['2020-10'][$school_id] : '0') : '0',
                '2020-09_key3'=>isset($school_accountant['2020-09']) && isset($school_accountant['2020-09'][$school_id]) ? ($school_accountant['2020-09'][$school_id] ? $school_accountant['2020-09'][$school_id] : '0') : '0',


                '2020-08_key3'=>isset($school_accountant['2020-08']) && isset($school_accountant['2020-08'][$school_id]) ? ($school_accountant['2020-08'][$school_id] ? $school_accountant['2020-08'][$school_id] : '0') : '0',
                '2020-07_key3'=>isset($school_accountant['2020-07']) && isset($school_accountant['2020-07'][$school_id]) ? ($school_accountant['2020-07'][$school_id] ? $school_accountant['2020-07'][$school_id] : '0') : '0',
                '2020-06_key3'=>isset($school_accountant['2020-06']) && isset($school_accountant['2020-06'][$school_id]) ? ($school_accountant['2020-06'][$school_id] ? $school_accountant['2020-06'][$school_id] : '0') : '0',
                '2020-05_key3'=>isset($school_accountant['2020-05']) && isset($school_accountant['2020-05'][$school_id]) ? ($school_accountant['2020-05'][$school_id] ? $school_accountant['2020-05'][$school_id] : '0') : '0',
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
}
