<?php

namespace App\Console\Schedules\Statistic;

use DB;
use Carbon\Carbon;
use App\Console\Schedules\BaseSchedule;
use Illuminate\Database\Query\JoinClause;

class ExportCourseSchool extends BaseSchedule
{
    /**
     * @param bool $send
     * @return void
     */
    public function handle($send = true)
    {
        \DB::setPdo($this->getConnPdo('core', 'online4'));
        $rows = DB::table('course_school_map')->selectRaw('school_id, is_active, created_at')
            ->orderBy('school_id')->get();
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $row->school_id;
        }
        $schools = DB::table('school')->join('user_account as mkt', 'mkt.id', '=', 'school.marketer_id')
            ->join('school_attribute as region', function (JoinClause $join) {
                $join->on('region.school_id', '=', 'school.id')
                    ->where('region.key', 'region');
            })->join('school_member', function (JoinClause $join) {
                $join->on('school_member.school_id', '=', 'school.id')
                    ->where('school_member.account_type_id', 6);
            }, null, null, 'left')
            ->join('user_account as pri', 'pri.id', '=', 'school_member.account_id', 'left')
            ->selectRaw('school.id, name, pri.nickname as p_name, mkt.nickname as m_name, region.value')
            ->whereIn('school.id', $ids)->get()->keyBy('id');
        $vco = ', count(DISTINCT cov.student_id) as vco';
        $oco = ', count(DISTINCT cod.student_id) as oco';
        $sta = DB::table('school')
            ->join('school_member', function (JoinClause $join) {
                $join->on('school_member.school_id', '=', 'school.id')
                    ->where('school_member.account_type_id', 5);
            }, null, null, 'left')
            ->join('course_student_overview as cov', 'cov.student_id', '=', 'school_member.account_id', 'left')
            ->join('course_order as cod', function (JoinClause $join) {
                $join->on('cod.student_id', '=', 'school_member.account_id')
                    ->where('cod.pay_status', 'success')->where('cod.pay_fee', '>', 1);
            }, null, null, 'left')
            ->whereIn('school.id', $ids)
            ->groupBy(['school.id'])
            ->selectRaw('school.id' . $vco . $oco)
            ->get()->keyBy('id');

        $report = [['学校ID', '学校名称', '校长', '轻课状态', '销售', '省', '市', '区',
            '绑定日期', '时间', '学习人数', '付费人数']];
        foreach ($rows as $row) {
            $id = $row->school_id;
            $region = explode('/', $schools[$id]->value);
            $dt = explode(' ', $row->created_at);
            $report[] = [
                'id' => $id,
                'name' => $schools[$id]->name,
                'pri' => $schools[$id]->p_name,
                'status' => $row->is_active,
                'mark' => $schools[$id]->m_name,
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'date' => isset($dt[0]) ? $dt[0] : null,
                'time' => isset($dt[1]) ? $dt[1] : null,
                'vco' => $sta[$id]->vco,
                'oco' => $sta[$id]->oco
            ];
        }

        $now = Carbon::now();
        $filename = 'qing_ke_statistic_' . $now->format('Ymd');
        $path = 'school/' . $now->year . '/' . $now->month;
        $file = $this->store($path . '/' . $filename, $report);
        if ($send) {
            $this->email('xiemin68@163.com', 'emails.export', ['object' => '轻课学校统计'], $now->toDateString() . ' Qing Ke Export', realpath($file));
        }

    }

}