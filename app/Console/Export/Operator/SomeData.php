<?php

namespace App\Console\Export\Operator;

use App\Foundation\Excel;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SomeData extends Command
{
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:some_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出在线助教相关数据';


    private static function FillFormat($key)
    {
        return  $key < 10 ? '0'.$key : $key;
    }

    public  function getLastMonthFormat()
    {
        $last_month = Carbon::today()->startOfMonth()->subMonth();
        $year_last_month   = $last_month->year;
        $month_last_month  = $last_month->month;
        $month_last_month = self::FillFormat($month_last_month);
        return 'M'.$year_last_month.'-'.$month_last_month;
    }


    public function getSchoolStudentCountWeekly($key)
    {
        return \DB::table('statistic_school_record_weekly')
            ->where('date_type' ,$key )
            ->selectRaw('act_student,school_id')
            ->get()->pluck( 'act_student', 'school_id' )->toArray();

    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '2048M');
        config(['database.default' => 'zxzj_online_search']);

        // 周活跃学生
        $week_of_year = Carbon::today()->weekOfYear;
        $year = Carbon::today()->year;
        $last_week = 'W'.$year.'-'.self::FillFormat($week_of_year - 1 );
        $last_2_week = 'W'.$year.'-'.self::FillFormat($week_of_year - 2 );
        $last_3_week = 'W'.$year.'-'.self::FillFormat($week_of_year - 3 );
        $last_4_week = 'W'.$year.'-'.self::FillFormat($week_of_year - 4 );

        $last_WAS = $this->getSchoolStudentCountWeekly($last_week);
        $last_2_WAS = $this->getSchoolStudentCountWeekly($last_2_week);
        $last_3_WAS = $this->getSchoolStudentCountWeekly($last_3_week);
        $last_4_WAS = $this->getSchoolStudentCountWeekly($last_4_week);


        $last_month_start_time = Carbon::now()->startOfMonth()->subMonth()->startOfMonth()->startOfDay()->toDateTimeString();
        $last_month_end_time = Carbon::now()->startOfMonth()->subMonth()->endOfMonth()->endOfDay()->toDateTimeString();


        $last_month_key = $this->getLastMonthFormat();

        $last_school_info = \DB::table('statistic_school_record_monthly')
            ->selectRaw('school_id,teacher_total,new_teacher, act_teacher,star, student_total , act_student , vip_student, try_student')
            ->where('date_type' ,$last_month_key)
            ->get()->keyBy('school_id')->toArray();

        // 上月新增注册学生
        $school_student_MNS = \DB::table('school_member')
            ->selectRaw('school_id, count(distinct account_id) new_student_count')
            ->where('account_type_id', 5)
            ->where('joined_time', '>=', $last_month_start_time)
            ->where('joined_time', '<=', $last_month_end_time)
            ->groupBy('school_id')
            ->get()->pluck('new_student_count', 'school_id')->toArray();

        // 当前快照
        $school_student_now = \DB::table('school_member')
            ->selectRaw('school_id, count(distinct account_id) student_count')
            ->where('account_type_id', 5)
            ->where('joined_time', '<', $last_month_start_time)
            ->groupBy('school_id')
            ->get()->pluck('student_count', 'school_id')->toArray();

        // 上月初 快照
        $start_month_data = Carbon::today()->startOfMonth()->subMonth()->endOfMonth()->toDateString();

        $school_student_last_month = \DB::table('statistic_school_record')
            ->selectRaw('school_id, student_total')
            ->where('date_type' , $start_month_data)
            ->get()->pluck('student_total', 'school_id')->toArray();


        $school_info = \DB::table('school')
            ->selectRaw('school.*,nickname')
            ->leftJoin('user_account', 'user_account.id', '=', 'school.marketer_id')
            ->get()->keyBy('id')->toArray();

        $school_region = \DB::table('school_attribute')->where('key','region')->get()->pluck('value', 'school_id')->toArray();


        $school_operator = \DB::table('school_attribute')
            ->selectRaw('school_attribute.school_id, nickname')
            ->where('key','after_sales')
            ->leftJoin('user_account', 'user_account.id', '=', 'school_attribute.value')
            ->get()->pluck('nickname', 'school_id')->toArray();

        $zxzj_class = $this->getClassInfo('contract_class');
        $zxzj_class_info = $zxzj_class['info'];
        $zxzj_class_date = $zxzj_class['date'];


        $jxc_class = $this->getClassInfo('text_contract_class');
        $jxc_class_info = $jxc_class['info'];
        $jxc_class_date = $jxc_class['date'];

        $dn_class = $this->getClassInfo('DN_contract_class');
        $dn_class_info = $dn_class['info'];
        $dn_class_date = $dn_class['date'];


        $school_expired_at = \DB::table('school_popularize_data')->where('key','school_expired_at')->get()->pluck('value','school_id')->toArray();

        $zxzj_fee = $this->getFeeInfo('app_balance_fee');
        $jxc_fee = $this->getFeeInfo('textbook_balance_fee');

        // 截止到 上月 的 软件结算额
        $school_app_fee = $this->getSchoolAppSettlement();

        // 去年 的 软件结算额
        $school_app_fee_last_year = $this->getSchoolAppSettlementLastYear();


        // 截止到 昨天 的 资料结算额
        $start_date = Carbon::today()->startOfYear()->toDateString();
        $end_date = Carbon::yesterday()->toDateString();
        $school_text_fee = $this->getSchoolTextSettlement($start_date, $end_date);

        // 去年 的 资料结算额
        $start_date = Carbon::now()->subYear()->startOfYear()->toDateString();
        $end_date = Carbon::now()->subYear()->endOfYear()->toDateString();
        $school_text_fee_last_year = $this->getSchoolTextSettlement($start_date, $end_date);



        $report = [];
        $report[] = [
            '学校ID',
            '学校名称',
            '创建时间',
            '校区数',
            '省',
            '地市',
            '区县',
            '销售',
            '运营',
            '在线助教档位',
            '在线助教签约日期',
            '在线助教试用期到（实时）',
            '软件余额（实时）',
            '上年累计在线助教结算额',
            '本年在线助教累计结算额(截止上月)',
            '上月末注册学生数',
            '上月新增注册学生数',
//            '上月新增注册学生数(不含退校的)',
//            '上月退校的学生数',
//            '当前快照',
//            '上月快照',
            '上月末提分版学生数',
            '上月末试用版学生数',
            '在线助教上月MAS',
            '在线助教前4周平均WAS',
            '1WAS',
            '2WAS',
            '3WAS',
            '4WAS',
            '上月学生星星数',
            '上月末注册老师数',
            '上月新增注册老师数',
            '在线助教上月MAT',
            '资料档位',
            '资料签约日期',
            '资料余额（实时）',
            '上年累计资料结算额',
            '本年累计资料结算额(截止昨天)',
            '优英档位',
            '优英签约日期',
            '优英余额（实时）',
            '上年累计优英结算额',
            '本年累计优英结算额(截止上月)',
        ];


        foreach ($school_info as $school_id=>$school_item ){

            $region = isset($school_region[$school_id]) ?  $school_region[$school_id] : '';
            $region_arr = explode('/', $region);

            $report[] = [
                $school_id,
                $school_item->name,
                substr( $school_item->created_at, 0, 10),
                '/',

                isset( $region_arr[0] ) ? $region_arr[0] : '/',
                isset( $region_arr[1] ) ? $region_arr[1] : '/',
                isset( $region_arr[2] ) ? $region_arr[2] : '/',

                $school_item->nickname,
                isset( $school_operator[$school_id] ) ? $school_operator[$school_id] : '/',

                isset( $zxzj_class_info[$school_id] ) ? $zxzj_class_info[$school_id] : 'N',
                isset( $zxzj_class_date[$school_id] ) ? $zxzj_class_date[$school_id] : '/',

                isset( $school_expired_at[$school_id] ) ? $school_expired_at[$school_id] : '/',

                isset( $zxzj_fee[$school_id] ) ? $zxzj_fee[$school_id].'' : '0',
                isset( $school_app_fee_last_year[$school_id] ) ? $school_app_fee_last_year[$school_id].'' : '0',
                isset( $school_app_fee[$school_id] ) ? $school_app_fee[$school_id].'' : '0',


                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->student_total.'' : '0',
                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->act_student.'' : '0',
//                isset( $school_student_MNS[$school_id] ) ? $school_student_MNS[$school_id].'' : '0',
//
//                (  (isset( $school_student_now[$school_id] ) ?   $school_student_now[$school_id] : 0) //当前
//                    -
//                    (isset( $school_student_last_month[$school_id]) ? $school_student_last_month[$school_id] : 0) // 上月
//                ).'',
//                isset( $school_student_now[$school_id] ) ? $school_student_now[$school_id].'' : '0',
//                isset( $school_student_last_month[$school_id] ) ? $school_student_last_month[$school_id].'' : '0',

                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->vip_student.'' : '0',
                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->try_student.'' : '0',
                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->act_student.'' : '0',

                round(
                (
                    (isset( $last_WAS[$school_id] ) ? $last_WAS[$school_id] : 0)  +
                    (isset( $last_2_WAS[$school_id] ) ? $last_2_WAS[$school_id] : 0 ) +
                        (isset( $last_3_WAS[$school_id] ) ? $last_3_WAS[$school_id] : 0 )  +
                            (isset( $last_4_WAS[$school_id] ) ? $last_4_WAS[$school_id] : 0 )
                ) / 4  , 2).'',

                isset( $last_WAS[$school_id] ) ? $last_WAS[$school_id].'' : '0',
                isset( $last_2_WAS[$school_id] ) ? $last_2_WAS[$school_id].'' : '0'  ,
                isset( $last_3_WAS[$school_id] ) ? $last_3_WAS[$school_id].'' : '0'  ,
                isset( $last_4_WAS[$school_id] ) ? $last_4_WAS[$school_id].'' : '0',

                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->star.'' : '0',
                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->teacher_total.'' : '0',
                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->new_teacher.'' : '0',
                isset( $last_school_info[$school_id] ) ? $last_school_info[$school_id]->act_teacher.'' : '0',

                isset( $jxc_class_info[$school_id] ) ? $jxc_class_info[$school_id] : 'N',
                isset( $jxc_class_date[$school_id] ) ? $jxc_class_date[$school_id] : '/',
                isset( $jxc_fee[$school_id] ) ? $jxc_fee[$school_id].'' : '0',
                isset( $school_text_fee_last_year[$school_id] ) ? $school_text_fee_last_year[$school_id].'' : '0',
                isset( $school_text_fee[$school_id] ) ? $school_text_fee[$school_id].'' : '0',


                isset( $dn_class_info[$school_id] ) ? $dn_class_info[$school_id] : 'N',
                isset( $dn_class_date[$school_id] ) ? $dn_class_date[$school_id] : '/',
                '/',
                '/',
                '/',
            ];

        }


        $this->store('在线助教导表_'.rand(0,100), $report, '.xlsx');


        dd('done');
    }


    public function getClassInfo($key)
    {
        $class = \DB::table('school_popularize_data')->selectRaw('school_id, value, left(created_at,10) date')
            ->where('key',$key)->get();
        return [
          'info' => $class->pluck('value', 'school_id')->toArray(),
          'date' => $class->pluck('date', 'school_id')->toArray(),
        ];

    }

    public function getFeeInfo($key)
    {
        return  \DB::table('school_popularize_data')
            ->where('key',$key)->get()->pluck('value', 'school_id')->toArray();
    }

    // 获得 教材结算额
    private function getSchoolTextSettlement($start_date, $end_date)
    {
        return \DB::table('accountant_statement')
            ->selectRaw("school_id, sum(fee) * (-1) as total_fee")
            ->where('date', '>=', $start_date)
            ->where('date', '<=', $end_date)
            ->whereIn('label_id', [36, 42 ])
            ->groupBy('school_id')
            ->get()->pluck('total_fee' , 'school_id')->toArray();
    }






    // 获得 当年的 软件 结算额 (截止到  上月)
    private function getSchoolAppSettlement()
    {
        $start_time = Carbon::today()->startOfYear()->startOfDay()->toDateTimeString();
        $end_time = Carbon::today()->startOfMonth()->startOfDay()->toDateTimeString();

        $start_date = substr($start_time, 0,10);
        $end_date = substr($end_time, 0,10);


        $start_month = Carbon::today()->format('Y'.'-01' );
        $end_month = Carbon::today()->startOfMonth()->subMonth()->format('Y'.'-'.'m' );


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
    created_at >= '$start_time'
    and created_at < '$end_time'
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
    AND `extra` >= '$start_month'
    AND `extra` <= '$end_month'
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
    and `date` >= '$start_date'
    and `date` < '$end_date'
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
    AND `extra` >= '$start_month'
    AND `extra` <= '$end_month'
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


    // 获得 上年的 软件 结算额
    private function getSchoolAppSettlementLastYear()
    {
        $start_time = '2020-01-01 00:00:00';
        $end_time = '2021-01-01 00:00:00';
        $start_date = '2020-01-01';
        $end_date = '2021-01-01';
        $start_month = '2020-01';
        $end_month = '2020-12';

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
    created_at >= '$start_time'
    and created_at < '$end_time'
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
    AND `extra` >= '$start_month'
    AND `extra` <= '$end_month'
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
    and `date` >= '$start_date'
    and `date` < '$end_date'
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
    AND `extra` >= '$start_month'
    AND `extra` <= '$end_month'
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

}
