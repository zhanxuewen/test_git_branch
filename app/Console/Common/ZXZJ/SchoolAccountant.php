<?php
namespace App\Console\Common\ZXZJ;

use App\Helper\Helper;

class SchoolAccountant
{
    /**
     * @param $start    '2019-01-01'
     * @param $end      '2019-10-10'
     */
    public static function getSchoolAccountant($start,$end,$conn='dev')
    {
        Helper::modifyDatabaseConfig($conn);
        // 获得所有学校的合同等级
        $school_contract = \DB::table('school_popularize_data')->selectRaw('school_id, value')->where('key', 'contract_class')
            ->get()->pluck('value', 'school_id')->toArray();
        $school_region = \DB::table('school_attribute')->selectRaw('school_id, value')->where('key', 'region')
            ->get()->pluck('value', 'school_id')->toArray();
        // 获得学校的售后专员
        $school_aftersales = \DB::table('school_attribute')
            ->selectRaw('school_attribute.school_id,nickname')
            ->leftJoin('user_account', 'user_account.id','=','school_attribute.value')
            ->where('key', 'after_sales')
            ->get()->pluck('nickname', 'school_id')->toArray();


        $statement_map = \DB::table('accountant_statement_label')->get();
        $statement_type = $statement_map->pluck('name', 'code')->toArray();
        $statement_label = $statement_map->pluck('name', 'id')->toArray();

        $between = [$start, $end];
        $report = [];
        $report[] = ['日期', '业务类型','费用类型', '摘要', '金额', '学校ID', '学校名称', '省', '市', '区', '市场专员','售后专员', '合作档'];
        $orders = \DB::table('school')
            ->selectRaw('school.id school_id, name, accountant_statement.type, accountant_statement.label_id,  date, fee, content, nickname')
            ->join('accountant_statement', 'school.id', '=', 'accountant_statement.school_id')
            ->join('user_account', 'user_account.id', '=', 'school.marketer_id')
            ->where('has_rollback', '<>', 1)
            ->whereBetween('date', $between)
            ->orderBy('date')->get();

        foreach ($orders as $order) {
            $region = explode('/', isset($school_region[$order->school_id]) ? $school_region[$order->school_id] : '');
            $report[] = [
                'date' => $order->date,
                'type' => $statement_type[$order->type],
                'label' => empty($order->label_id) ? '/':$statement_label[$order->label_id],
                'con' => $order->content,
                'fee' => in_array($order->label_id,[21,22]) ? '/' : $order->fee,
                'id' => $order->school_id,
                'name' => $order->name,
                'she' => isset($region[0]) ? $region[0] : '',
                'shi' => isset($region[1]) ? $region[1] : '',
                'qu' => isset($region[2]) ? $region[2] : '',
                'nick' => $order->nickname,
                'after_sales' => isset($school_aftersales[$order->school_id]) ? $school_aftersales[$order->school_id] : '',
                'cons' => isset($school_contract[$order->school_id]) ? $school_contract[$order->school_id] : '',
            ];
        }
        return $report;
    }
}