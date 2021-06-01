<?php

namespace App\Console\Export\Operator;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SettlementData extends Command
{
    use Excel;

    private $month_key = '2105';

    private  $month_count = 17;                     // 截止到 某月

    private  $max_school_id = 0;                    // 最大 学校id

    private  $school_info = [];                     // 现有的学校的名字

    private $report_arr = [
        '学校id',
        '学校',
        '2021-05',// todo
        '2021-04',
        '2021-03',
        '2021-02',
        '2021-01',
        '2020-12',
        '2020-11',
        '2020-10',
        '2020-09',
        '2020-08',
        '2020-07',
        '2020-06',
        '2020-05',
        '2020-04',
        '2020-03',
        '2020-02',
        '2020-01',
    ];





    protected $signature = 'export:settlement:data';


    protected $description = '导出在线助教 财务结算 基础数据';



    public function handle()
    {
        ini_set('memory_limit', '2048M');
        config(['database.default' => 'zxzj_online_search']);

        // 获取 最大学校  id
        $this->max_school_id =  \DB::table('school')->max('id');

        // 获得 现有的学校的名字
        $this->school_info = \DB::table('school')->selectRaw('id, name')->get()->pluck('name', 'id')->toArray();

        // E卡 结算
        $this->getECardInfo();

        // 线上购买
        $this->getStudentOrderInfo();

        // 代买待退
        $this->handleSchoolInfo( 'if(`offline`.offline_fee ,offline.offline_fee , 0)' , '代买待退');

        // 轻课
        $this->handleSchoolInfo( 'if(`qingke`.finance_fee ,`qingke`.finance_fee , 0)' , '轻课');

        // 学校返款
        $this->handleSchoolInfo( 'if(`finance`.finance_fee ,`finance`.finance_fee , 0)' , '学校返款');

        dd('done');
    }

    private function handleSchoolInfo($type , $name)
    {
        $start_month = '2020-01';
        $school_accountant = [];
        for($i=0; $i < $this->month_count; $i++){
            $month = Carbon::parse($start_month)->addMonths($i)->toDateString();
            $month_key = substr($month,0,7);
            $this->info($month_key);
            $school_accountant[$month_key] = $this->getSchoolMonthFee($month_key,$type);
        }


        if ('轻课' == $name){ // 将 不需要 结算的 全部 算到 自由学生里 去
            $course_count = \DB::table('course_order')
                ->where('school_set_status' , 0)
                ->where('pay_status' , '<>', 'success')
                ->whereNotNull('transaction_id')
                ->count();

            if ( $course_count ) dd( '有 退款订单');

            $sql = <<<EOF
SELECT
	LEFT(paid_at, 7) `month`, SUM(pay_fee) fee 
FROM
	`b_vanthink_online`.`course_order` 
WHERE
	`transaction_id` IS NOT NULL 
	AND `school_set_status` = 0
GROUP BY LEFT(paid_at, 7)
EOF;

            $school_accountant_0 = \DB::select(\DB::raw($sql));

            foreach ( $school_accountant_0 as $school_accountant_0_item){

                $school_accountant[$school_accountant_0_item->month][0]
                    = $school_accountant_0_item->fee;
            }
        }


        $school_record = [];
        $school_record[] = $this->report_arr;

        for ($i=0;$i<= $this->max_school_id; $i++ ){
            $school_id = $i;
            $school_record[] = [
                $school_id,
                isset( $this->school_info[$school_id] ) ? $this->school_info[$school_id] : '不存在或已删除',
                isset($school_accountant['2021-05']) && isset($school_accountant['2021-05'][$school_id]) ? $school_accountant['2021-05'][$school_id].''  : '0',
                isset($school_accountant['2021-04']) && isset($school_accountant['2021-04'][$school_id]) ? $school_accountant['2021-04'][$school_id].''  : '0',
                isset($school_accountant['2021-03']) && isset($school_accountant['2021-03'][$school_id]) ? $school_accountant['2021-03'][$school_id].''  : '0',
                isset($school_accountant['2021-02']) && isset($school_accountant['2021-02'][$school_id]) ? $school_accountant['2021-02'][$school_id].''  : '0',
                isset($school_accountant['2021-01']) && isset($school_accountant['2021-01'][$school_id]) ? $school_accountant['2021-01'][$school_id].''  : '0',
                isset($school_accountant['2020-12']) && isset($school_accountant['2020-12'][$school_id]) ? $school_accountant['2020-12'][$school_id].''  : '0',
                isset($school_accountant['2020-11']) && isset($school_accountant['2020-11'][$school_id]) ? $school_accountant['2020-11'][$school_id].''  : '0',
                isset($school_accountant['2020-10']) && isset($school_accountant['2020-10'][$school_id]) ? $school_accountant['2020-10'][$school_id].''  : '0',
                isset($school_accountant['2020-09']) && isset($school_accountant['2020-09'][$school_id]) ? $school_accountant['2020-09'][$school_id].''  : '0',
                isset($school_accountant['2020-08']) && isset($school_accountant['2020-08'][$school_id]) ? $school_accountant['2020-08'][$school_id].''  : '0',
                isset($school_accountant['2020-07']) && isset($school_accountant['2020-07'][$school_id]) ? $school_accountant['2020-07'][$school_id].''  : '0',
                isset($school_accountant['2020-06']) && isset($school_accountant['2020-06'][$school_id]) ? $school_accountant['2020-06'][$school_id].''  : '0',
                isset($school_accountant['2020-05']) && isset($school_accountant['2020-05'][$school_id]) ? $school_accountant['2020-05'][$school_id].''  : '0',
                isset($school_accountant['2020-04']) && isset($school_accountant['2020-04'][$school_id]) ? $school_accountant['2020-04'][$school_id].''  : '0',
                isset($school_accountant['2020-03']) && isset($school_accountant['2020-03'][$school_id]) ? $school_accountant['2020-03'][$school_id].''  : '0',
                isset($school_accountant['2020-02']) && isset($school_accountant['2020-02'][$school_id]) ? $school_accountant['2020-02'][$school_id].''  : '0',
                isset($school_accountant['2020-01']) && isset($school_accountant['2020-01'][$school_id]) ? $school_accountant['2020-01'][$school_id].''  : '0',
            ];
        }

        $this->store($name.'_'.$this->month_key.'_'.rand(0,100), $school_record, '.xlsx');

    }

    // 在线助教  线上购买
    public function getStudentOrderInfo()
    {
        $return_school = [];

        // 查找 以录入的 学校结算额
        $school_luru = \DB::table('accountant_statement')
            ->where('label_id', 8)
            ->where('has_rollback' , '0')
            ->selectRaw('school_id, fee, extra')->get()->groupBy('extra')->map(function ($month){
                return $month->groupBy('school_id')->map(function ($school){
                    return $school->sum('fee') * -1;
                });
            })->toArray();

        $sql = <<<EOF
SELECT
	school_id, LEFT(`order`.paid_at, 7) `month`, SUM(pay_fee)  pay_fee
FROM
	`order` 
WHERE
	`paid_at` >= '2020-01-01 00:00:00' 
GROUP BY school_id, LEFT(`order`.paid_at, 7)
EOF;
        $school_order = \DB::select(\DB::raw($sql));
        $school_order = json_decode(json_encode($school_order),true);
        $school_order = collect( $school_order )->groupBy('school_id')->map(function ($school){
            return $school->pluck('pay_fee' ,'month')->toArray();
        });


        $sql = <<<EOF
SELECT
	school_id,LEFT(order_refund.created_at,7) `month`, SUM(refund_fee)  refund_total
FROM
	`order_refund`
	left JOIN `order` on `order`.out_trade_no = order_refund.out_trade_no 
	WHERE order_refund.deleted_at is NULL
	and order_refund.refund_status NOT LIKE '%refunding'
	GROUP BY school_id,LEFT(order_refund.created_at,7) 
EOF;
        $order_refund = \DB::select(\DB::raw($sql));
        $order_refund = json_decode(json_encode($order_refund),true);
        $order_refund = collect( $order_refund )->groupBy('school_id')->map(function ($school){
            return $school->pluck('refund_total' ,'month')->toArray();
        });

        $start_month = '2020-01';

        for ($i=0;$i<= $this->month_count; $i++ ){

            $record_month = Carbon::parse( $start_month) -> addMonths($i)->format('Y-m');

            $month_key = 'M'.$record_month;

            $record_school = \DB::table('statistic_school_record_monthly')->where('date_type' ,$month_key)
                ->selectRaw("school_id,date_type,contract_class ,extra->'$.online_earn_money'  exec_res")->get();


            // 先计算自由学生
            $school_0 = (isset($school_order[0][$record_month])     ?  ($school_order[0][$record_month] + 0) : 0 )
                - (isset($order_refund[0][$record_month])     ?  ($order_refund[0][$record_month] + 0) : 0 );

            $return_school[$record_month][0] = $school_0.'';

            foreach ($record_school as $record_school_item ){
                $tmp_school_id = $record_school_item->school_id;
                $contract_class = $record_school_item->contract_class;

                if (in_array($contract_class, ['A' , 'B', 'C', 'D'] )){
                    $return_school[$record_month][$tmp_school_id] =
                        isset($school_luru[$record_month] ) &&
                        isset( $school_luru[$record_month][$tmp_school_id] )? $school_luru[$record_month][$tmp_school_id].'' :  $record_school_item->exec_res.'';
                }else{
                    $tt = (isset($school_order[$tmp_school_id][$record_month]) ?  ($school_order[$tmp_school_id][$record_month] + 0) : 0 )
                        - (isset($order_refund[$tmp_school_id][$record_month]) ?  ($order_refund[$tmp_school_id][$record_month] + 0) : 0 );
                    $return_school[$record_month][$tmp_school_id] = $tt.'';
                }
            }
        }

        $res = [];
        $res[] = $this->report_arr;

        for($i= 0 ;$i<= $this->max_school_id; $i++){
            $school_id = $i;
            $res[] = [
                $school_id,
                isset( $this->school_info[$school_id] ) ? $this->school_info[$school_id] : '不存在或已删除',
                isset($return_school['2021-05'][$school_id]) ? $return_school['2021-05'][$school_id] : '0', // todo
                isset($return_school['2021-04'][$school_id]) ? $return_school['2021-04'][$school_id] : '0',
                isset($return_school['2021-03'][$school_id]) ? $return_school['2021-03'][$school_id] : '0',
                isset($return_school['2021-02'][$school_id]) ? $return_school['2021-02'][$school_id] : '0',
                isset($return_school['2021-01'][$school_id]) ? $return_school['2021-01'][$school_id] : '0',
                isset($return_school['2020-12'][$school_id]) ? $return_school['2020-12'][$school_id] : '0',
                isset($return_school['2020-11'][$school_id]) ? $return_school['2020-11'][$school_id] : '0',
                isset($return_school['2020-10'][$school_id]) ? $return_school['2020-10'][$school_id] : '0',
                isset($return_school['2020-09'][$school_id]) ? $return_school['2020-09'][$school_id] : '0',
                isset($return_school['2020-08'][$school_id]) ? $return_school['2020-08'][$school_id] : '0',
                isset($return_school['2020-07'][$school_id]) ? $return_school['2020-07'][$school_id] : '0',
                isset($return_school['2020-06'][$school_id]) ? $return_school['2020-06'][$school_id] : '0',
                isset($return_school['2020-05'][$school_id]) ? $return_school['2020-05'][$school_id] : '0',
                isset($return_school['2020-04'][$school_id]) ? $return_school['2020-04'][$school_id] : '0',
                isset($return_school['2020-03'][$school_id]) ? $return_school['2020-03'][$school_id] : '0',
                isset($return_school['2020-02'][$school_id]) ? $return_school['2020-02'][$school_id] : '0',
                isset($return_school['2020-01'][$school_id]) ? $return_school['2020-01'][$school_id] : '0',
            ];
        }
        $this->store('线上购买_'.$this->month_key.'_'.rand(0,100), $res, '.xlsx');
    }

    /**
     * 在线助教 结算
     * @param $month
     * @param $column       if(`offline`.offline_fee ,offline.offline_fee , 0)      | 线下
     *                      if(`finance`.finance_fee ,`finance`.finance_fee , 0)    | 学校返款
     *                      if(`qingke`.finance_fee ,`qingke`.finance_fee , 0)      | 轻课
     * @return array|false
     */
    private function getSchoolMonthFee($month,$column)
    {
        $start_time = Carbon::parse($month)->startOfMonth()->toDateString();
        $end_time = Carbon::parse($month)->endOfMonth()->toDateString();

        $sql = <<<EOF
select 
  school_info.school_id,$column as total_fee

--   if(`offline`.offline_fee ,offline.offline_fee , 0)         线下
--   if(`finance`.finance_fee ,`finance`.finance_fee , 0)       学校返款
--   if(`qingke`.finance_fee ,`qingke`.finance_fee , 0)         轻课

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


    /**
     * 获得 E卡 的 结算额
     */
    private  function getECardInfo(){
        $record = [];

        $record[] = [
            '合同id',
            '学校id',
            '学校名称',
            '学习卡数量',
            '学习卡单价',
            '合同金额',
            '学习卡有效期',
            '钉钉审批',
            '合同创建时间',
            '激活期',
            '末卡时间',
            '五月激活卡数量', // todo
            '五月激活卡金额', // todo
            '四月激活卡数量',
            '四月激活卡金额',
            '三月激活卡数量',
            '三月激活卡金额',
            '二月激活卡数量',
            '二月激活卡金额',
            '一月激活卡数量',
            '一月激活卡金额',
            '12月激活卡数量',
            '12月激活卡金额',
            '11月激活卡数量',
            '11月激活卡金额',
            '10月激活卡数量',
            '10月激活卡金额',
            '9月激活卡数量',
            '9月激活卡金额',
            '8月激活卡数量',
            '8月激活卡金额',
            '7月激活卡数量',
            '7月激活卡金额',
            '6月激活卡数量',
            '6月激活卡金额',
            '5月激活卡数量',
            '5月激活卡金额',
            '4月激活卡数量',
            '4月激活卡金额',
            '3月激活卡数量',
            '3月激活卡金额',
            '2月激活卡数量',
            '2月激活卡金额',
            '1月激活卡数量',
            '1月激活卡金额',
//            '截止到12月底激活卡数量',
//            '截止到12月底激活卡数量',
        ];
// todo
        $sql = <<<EOF
SELECT

    order_class_e.id 合同id,
    school_id 学校id, 
    school.`name` 学校名称,
    card_amount 学习卡数量, 
    unit_price 学习卡单价, 
    total_fee 合同金额,
    card_days 学习卡有效期, 
    order_class_e.info->'$.code' 钉钉审批,
    LEFT( order_class_e.created_at , 10 ) 合同创建时间 ,durations 激活期, 
    left(date_add( order_class_e.created_at, interval durations MONTH) , 10 ) 末卡时间 ,
    
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-05-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >=  '2021-05-01 00:00:00' , 
    card_amount - count(IF( order_class_e_card.created_at <=  '2021-05-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) <  '2021-05-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >=  '2021-05-01 00:00:00'  and order_class_e_card.created_at <='2021-05-31 23:59:59' ,1,null )))
)  '五月激活卡数量',
    if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-05-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >=  '2021-05-01 00:00:00' , 
    card_amount - count(IF( order_class_e_card.created_at <=  '2021-05-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) <  '2021-05-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >=  '2021-05-01 00:00:00'  and order_class_e_card.created_at <='2021-05-31 23:59:59' ,1,null )))
) * unit_price '五月激活卡金额',
    
    
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-04-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >=  '2021-04-01 00:00:00' , 
    card_amount - count(IF( order_class_e_card.created_at <=  '2021-04-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) <  '2021-04-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >=  '2021-04-01 00:00:00'  and order_class_e_card.created_at <='2021-04-30 23:59:59' ,1,null )))
)  '四月激活卡数量',
    if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-04-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >=  '2021-04-01 00:00:00' , 
    card_amount - count(IF( order_class_e_card.created_at <=  '2021-04-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) <  '2021-04-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >=  '2021-04-01 00:00:00'  and order_class_e_card.created_at <='2021-04-30 23:59:59' ,1,null )))
) * unit_price '四月激活卡金额',
    
    if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-03-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >=  '2021-03-01 00:00:00' , 
    card_amount - count(IF( order_class_e_card.created_at <=  '2021-03-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) <  '2021-03-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >=  '2021-03-01 00:00:00'  and order_class_e_card.created_at <='2021-03-31 23:59:59' ,1,null )))
)  '三月激活卡数量',
    if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-03-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >=  '2021-03-01 00:00:00' , 
    card_amount - count(IF( order_class_e_card.created_at <=  '2021-03-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) <  '2021-03-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >=  '2021-03-01 00:00:00'  and order_class_e_card.created_at <='2021-03-31 23:59:59' ,1,null )))
) * unit_price '三月激活卡金额',

    if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-02-28 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2021-02-01 00:00:00'  , 
    card_amount - count(IF( order_class_e_card.created_at <= '2021-02-01 00:00:00'  ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2021-02-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >= '2021-02-01 00:00:00'  and order_class_e_card.created_at <= '2021-02-01 00:00:00' ,1,null )))
)  '二月激活卡数量',
            if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-02-28 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2021-02-01 00:00:00'  , 
    card_amount - count(IF( order_class_e_card.created_at <= '2021-02-01 00:00:00'  ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2021-02-01 00:00:00'  , 0  , count(IF( order_class_e_card.created_at >= '2021-02-01 00:00:00'  and order_class_e_card.created_at <= '2021-02-01 00:00:00' ,1,null )))
) * unit_price '二月激活卡金额',

    if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-01-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2021-01-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2021-01-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2021-01-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2021-01-01 00:00:00' and order_class_e_card.created_at <= '2021-01-31 23:59:59',1,null )))
)  '一月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2021-01-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2021-01-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2021-01-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2021-01-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2021-01-01 00:00:00' and order_class_e_card.created_at <= '2021-01-31 23:59:59',1,null )))
) * unit_price '一月激活卡金额',


if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-12-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-12-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-12-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-12-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-12-01 00:00:00' and order_class_e_card.created_at <= '2020-12-31 23:59:59',1,null )))
)  '12月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-12-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-12-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-12-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-12-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-12-01 00:00:00' and order_class_e_card.created_at <= '2020-12-31 23:59:59',1,null )))
) * unit_price '12月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-11-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-11-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-11-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-11-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-11-01 00:00:00' and order_class_e_card.created_at <= '2020-11-30 23:59:59',1,null )))
)  '11月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-11-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-11-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-11-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-11-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-11-01 00:00:00' and order_class_e_card.created_at <= '2020-11-30 23:59:59',1,null )))
) * unit_price '11月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-10-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-10-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-10-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-10-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-10-01 00:00:00' and order_class_e_card.created_at <= '2020-10-31 23:59:59',1,null )))
)  '10月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-10-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-10-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-10-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-10-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-10-01 00:00:00' and order_class_e_card.created_at <= '2020-10-31 23:59:59',1,null )))
) * unit_price '10月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-09-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-09-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-09-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-09-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-09-01 00:00:00' and order_class_e_card.created_at <= '2020-09-30 23:59:59',1,null )))
)  '9月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-09-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-09-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-09-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-09-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-09-01 00:00:00' and order_class_e_card.created_at <= '2020-09-30 23:59:59',1,null )))
) * unit_price '9月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-08-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-08-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-08-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-08-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-08-01 00:00:00' and order_class_e_card.created_at <= '2020-08-31 23:59:59',1,null )))
)  '8月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-08-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-08-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-08-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-08-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-08-01 00:00:00' and order_class_e_card.created_at <= '2020-08-31 23:59:59',1,null )))
) * unit_price '8月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-07-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-07-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-07-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-07-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-07-01 00:00:00' and order_class_e_card.created_at <= '2020-07-31 23:59:59',1,null )))
)  '7月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-07-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-07-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-07-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-07-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-07-01 00:00:00' and order_class_e_card.created_at <= '2020-07-31 23:59:59',1,null )))
) * unit_price '7月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-06-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-06-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-06-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-06-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-06-01 00:00:00' and order_class_e_card.created_at <= '2020-06-30 23:59:59',1,null )))
)  '6月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-06-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-06-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-06-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-06-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-06-01 00:00:00' and order_class_e_card.created_at <= '2020-06-30 23:59:59',1,null )))
) * unit_price '6月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-05-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-05-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-05-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-05-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-05-01 00:00:00' and order_class_e_card.created_at <= '2020-05-31 23:59:59',1,null )))
)  '5月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-05-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-05-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-05-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-05-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-05-01 00:00:00' and order_class_e_card.created_at <= '2020-05-31 23:59:59',1,null )))
) * unit_price '5月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-04-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-04-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-04-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-04-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-04-01 00:00:00' and order_class_e_card.created_at <= '2020-04-30 23:59:59',1,null )))
)  '4月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-04-30 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-04-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-04-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-04-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-04-01 00:00:00' and order_class_e_card.created_at <= '2020-04-30 23:59:59',1,null )))
) * unit_price '4月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-03-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-03-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-03-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-03-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-03-01 00:00:00' and order_class_e_card.created_at <= '2020-03-31 23:59:59',1,null )))
)  '3月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-03-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-03-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-03-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-03-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-03-01 00:00:00' and order_class_e_card.created_at <= '2020-03-31 23:59:59',1,null )))
) * unit_price '3月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-02-29 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-02-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-02-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-02-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-02-01 00:00:00' and order_class_e_card.created_at <= '2020-02-29 23:59:59',1,null )))
)  '2月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-02-29 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-02-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-02-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-02-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-02-01 00:00:00' and order_class_e_card.created_at <= '2020-02-29 23:59:59',1,null )))
) * unit_price '2月激活卡金额',
if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-01-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-01-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-01-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-01-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-01-01 00:00:00' and order_class_e_card.created_at <= '2020-01-31 23:59:59',1,null )))
) '1月激活卡数量',
        if( date_add( order_class_e.created_at, interval durations MONTH) <= '2020-01-31 23:59:59' and date_add( order_class_e.created_at, interval durations MONTH) >= '2020-01-01 00:00:00', 
    card_amount - count(IF( order_class_e_card.created_at <= '2020-01-01 00:00:00' ,1,null )) , 
    if (  date_add( order_class_e.created_at, interval durations MONTH) < '2020-01-01 00:00:00' , 0  , count(IF( order_class_e_card.created_at >= '2020-01-01 00:00:00' and order_class_e_card.created_at <= '2020-01-31 23:59:59',1,null )))
) * unit_price '1月激活卡金额'

FROM
     `order_class_e` 
    left join order_class_e_card on order_class_e_card.order_id = order_class_e.id 
    left join school on school.id = order_class_e.school_id
    GROUP BY order_class_e.id
EOF;

        $records = \DB::select(\DB::raw($sql));

        foreach ( $records as $record_item ){
            $record[] = $record_item;
        }
        $this->store('E档校开卡_'.$this->month_key.'_'.rand(0,100), $record, '.xlsx');
    }

}
