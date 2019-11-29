<?php

namespace App\Console\Common\ZXZJ;

use DB;
use App\Console\Pool\SchoolInfo;

class SchoolAccountant
{
    /**
     * @param $start '2019-01-01'
     * @param $end '2019-10-10'
     * @return array
     */
    public static function get($start, $end)
    {
        // 获得所有学校的合同等级
        $contracts = SchoolInfo::getContract();
        $regions = SchoolInfo::getRegions();
        // 获得学校的售后专员
        $afterSales = SchoolInfo::getAfterSales();

        $map = DB::table('accountant_statement_label')->get();
        $type = $map->pluck('name', 'code')->toArray();
        $label = $map->pluck('name', 'id')->toArray();

        $report = [];
        $report[] = ['日期', '业务类型', '费用类型', '摘要', '金额', '学校ID', '学校名称', '省', '市', '区', '市场专员', '售后专员', '合作档'];
        $rows = DB::table('school')
            ->selectRaw('school.id school_id, name, accountant_statement.type, accountant_statement.label_id, date, fee, content, nickname')
            ->join('accountant_statement', 'school.id', '=', 'accountant_statement.school_id')
            ->join('user_account', 'user_account.id', '=', 'school.marketer_id')
            ->where('has_rollback', '<>', 1)->whereBetween('date', [$start, $end])->orderBy('date')->get();

        foreach ($rows as $row) {
            $id = $row->school_id;
            $region = explode('/', isset($regions[$id]) ? $regions[$id] : '');
            $report[] = [
                'date' => $row->date,
                'type' => $type[$row->type],
                'label' => empty($row->label_id) ? '/' : $label[$row->label_id],
                'con' => $row->content,
                'fee' => in_array($row->label_id, [21, 22]) ? '/' : $row->fee,
                'id' => $id,
                'name' => $row->name,
                'she' => isset($region[0]) ? $region[0] : '',
                'shi' => isset($region[1]) ? $region[1] : '',
                'qu' => isset($region[2]) ? $region[2] : '',
                'nick' => $row->nickname,
                'after_sales' => isset($afterSales[$id]) ? $afterSales[$id] : '',
                'cons' => isset($contracts[$id]) ? $contracts[$id] : '',
            ];
        }
        return $report;
    }

}