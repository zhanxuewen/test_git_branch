<?php


namespace App\Console\Schedules\Learning;


use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class ExportSchoolLearningStudent extends BaseSchedule
{
    public function handle()
    {
        \DB::setPdo($this->getConnPdo('learning', 'online'));
        $date = Carbon::yesterday()->toDateString();

        // 查询昨天的 数据
        $sql = <<<EOF
SELECT
    school.id,school.name, 
    statistic_school_record.school_region, 
    marketer.`name` marketer_name ,
    operator.`name` operator_name,
    statistic_school_record.learn_student
FROM
    `learning`.`statistic_school_record`
    left  join school on school.id = statistic_school_record.school_id
    left  join user marketer on marketer.id = statistic_school_record.marketer_id
    left  join user operator on operator.id = statistic_school_record.operator_id
WHERE
    statistic_school_record.`date_type` = '$date'
EOF;

        $school_learning_student = \DB::select(\DB::raw($sql));

       // 查询过去13天的数据
        $start_date = Carbon::yesterday()->subDays(13)->toDateString();
        $end_date  = Carbon::yesterday()->subDay()->toDateString();
        $history_record = DB::table('statistic_school_record')
            ->selectRaw('school_id, learn_student, date_type')
            ->where('date_type','>=', $start_date)
            ->where('date_type','<=', $end_date)
            ->get()->groupBy('school_id')->map(function ($school){
            return $school->pluck('learn_student', 'date_type')->toArray();
        });
        $report = [];
        $report[] = ['学校id', '学校名称', '省', '市', '市场专员', '运营专员',
            Carbon::yesterday()->toDateString(),
            Carbon::yesterday()->subDays(1)->toDateString(),
            Carbon::yesterday()->subDays(2)->toDateString(),
            Carbon::yesterday()->subDays(3)->toDateString(),
            Carbon::yesterday()->subDays(4)->toDateString(),
            Carbon::yesterday()->subDays(5)->toDateString(),
            Carbon::yesterday()->subDays(6)->toDateString(),
            Carbon::yesterday()->subDays(7)->toDateString(),
            Carbon::yesterday()->subDays(8)->toDateString(),
            Carbon::yesterday()->subDays(9)->toDateString(),
            Carbon::yesterday()->subDays(10)->toDateString(),
            Carbon::yesterday()->subDays(11)->toDateString(),
            Carbon::yesterday()->subDays(12)->toDateString(),
            Carbon::yesterday()->subDays(13)->toDateString(),
            ];


        foreach ($school_learning_student as $item){
            $school_id = $item->id;
            $school_name = $item->name;
            $school_region = $item->school_region;
            $marketer_name = $item->marketer_name;
            $operator_name = $item->operator_name;
            $learn_student = $item->learn_student;

            $school_region_arr = explode('/', $school_region);
            $sheng = $school_region_arr[0];
            $shi = $school_region_arr[1];

            $report[] = [
                $school_id,
                $school_name,
                $sheng,
                $shi,
                $marketer_name,
                $operator_name,
                $learn_student,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(1)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(1)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(2)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(2)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(3)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(3)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(4)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(4)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(5)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(5)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(6)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(6)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(7)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(7)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(8)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(8)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(9)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(9)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(10)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(10)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(11)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(11)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(12)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(12)->toDateString()] : null,
                isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(13)->toDateString()]) ?
                    $history_record[$school_id][Carbon::yesterday()->subDays(13)->toDateString()] : null,
            ];
        }

        $filename = 'BXG_learn_student_' . date('md');
        $path = 'learning';
        $file = $this->store($path . '/' . $filename, $report);


        $this->email(['xiemin68@163.com','zhanxuewen2018@126.com'],
            'emails.export2', ['object' => '每日' . '学校学习人数'],
            '百项过学习人数统计_'.date('md'), realpath($file));
    }

}