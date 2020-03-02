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

        $second_record = [];
        $second_school_ids = [];

        $all_record = [];
        $all_school_ids = [];

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

            $last_day = isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(1)->toDateString()]) ?
                $history_record[$school_id][Carbon::yesterday()->subDays(1)->toDateString()] : 0;
            $last_2_day = isset($history_record[$school_id])&&isset($history_record[$school_id][Carbon::yesterday()->subDays(2)->toDateString()]) ?
                $history_record[$school_id][Carbon::yesterday()->subDays(2)->toDateString()] : 0;

            if ($learn_student >= 5 && ($last_day >= 5 || $last_2_day >= 5)){
                $second_school_ids[] = $school_id;
                $second_record[$school_id] = [
                    'school_id' => $school_id,
                    'school_name' => $school_name,
                    'marketer_name' => $marketer_name,
                    'operator_name' => $operator_name,
                    Carbon::yesterday()->toDateString() => $learn_student,
                    Carbon::yesterday()->subDays(1)->toDateString() => $last_day,
                    Carbon::yesterday()->subDays(2)->toDateString() => $last_2_day
                ];
            }
            if ($learn_student||$last_day||$last_2_day){
                $all_school_ids[] = $school_id;
                $all_record[$school_id] = [
                    'school_id' => $school_id,
                    'school_name' => $school_name,
                    'marketer_name' => $marketer_name,
                    'operator_name' => $operator_name,
                    Carbon::yesterday()->toDateString() => $learn_student,
                    Carbon::yesterday()->subDays(1)->toDateString() => $last_day,
                    Carbon::yesterday()->subDays(2)->toDateString() => $last_2_day
                ];
            }

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


        // 2nd
        $start_date = Carbon::yesterday()->subDays(13)->toDateString();
        $end_date  = Carbon::yesterday()->toDateString();

        $report_2nd = [];
        $report_2nd[] = ['学校id', '学校名称', '市场专员','运营专员',
            Carbon::yesterday()->toDateString(),
            Carbon::yesterday()->subDays(1)->toDateString(),
            Carbon::yesterday()->subDays(2)->toDateString(),
            '第一名',
            '第二名',
            '第三名',
            '第四名',
            '第五名',
            '第六名',
            '第七名',
            '第八名',
            '第九名',
            '第十名'];


        $school_list = $second_school_ids;
        foreach ($school_list as $school){
            $sql = <<<EOF
SELECT
	student_id ,count(*)  cc,user.name
FROM
	`learning`.`statistic_student_record` 
	left join user on user.id = student_id
WHERE
	`created_date` >= '$start_date' 
	AND `created_date` <= '$end_date' 
	AND `school_id` = $school 
	GROUP BY
	student_id
	ORDER BY cc desc 
	limit 10
EOF;

            $school_learning_student = \DB::select(\DB::raw($sql));

            $sort = [];
            $school_learning_student = json_decode(json_encode($school_learning_student),true);
            $school_learning_student = collect($school_learning_student)->sortByDesc('cc')->toArray();
            foreach ($school_learning_student as $item){
                $name = $item['name'];
                $substr = substr($name, strlen($name)-3,3);

                $is_cn = preg_match("/^[\x7f-\xff]+$/", $substr);  //判断是否是中文
                if($is_cn){
                    $name = str_replace($substr, '*', $name);
                }else{
                    $name = substr($name, 0,strlen($name)-1).'*';
                }

                if ($name == '*') $name = $item['name'];
                $count = $item['cc'];
                $sort[] = $name.'('.$count.')';
            }

            $report_2nd[] = [
                $second_record[$school]['school_id'],
                $second_record[$school]['school_name'],
                $second_record[$school]['marketer_name'],
                $second_record[$school]['operator_name'],
                $second_record[$school][Carbon::yesterday()->toDateString()],
                $second_record[$school][Carbon::yesterday()->subDays(1)->toDateString()],
                $second_record[$school][Carbon::yesterday()->subDays(2)->toDateString()],

                isset($sort[0]) ? $sort[0] : null,
                isset($sort[1]) ? $sort[1] : null,
                isset($sort[2]) ? $sort[2] : null,
                isset($sort[3]) ? $sort[3] : null,
                isset($sort[4]) ? $sort[4] : null,
                isset($sort[5]) ? $sort[5] : null,
                isset($sort[6]) ? $sort[6] : null,
                isset($sort[7]) ? $sort[7] : null,
                isset($sort[8]) ? $sort[8] : null,
                isset($sort[9]) ? $sort[9] : null,

            ];
        }

        // 3rd
        $start_date = Carbon::yesterday()->subDays(13)->toDateString();
        $end_date  = Carbon::yesterday()->toDateString();

        $report_3rd = [];
        $report_3rd[] = ['学校id', '学校名称', '市场专员','运营专员',
            Carbon::yesterday()->toDateString(),
            Carbon::yesterday()->subDays(1)->toDateString(),
            Carbon::yesterday()->subDays(2)->toDateString(),
            '第一名',
            '第二名',
            '第三名',
            '第四名',
            '第五名',
            '第六名',
            '第七名',
            '第八名',
            '第九名',
            '第十名'];


        $school_list = $all_school_ids;
        foreach ($school_list as $school){
            $sql = <<<EOF
SELECT
	student_id ,count(*)  cc,user.name
FROM
	`learning`.`statistic_student_record` 
	left join user on user.id = student_id
WHERE
	`created_date` >= '$start_date' 
	AND `created_date` <= '$end_date' 
	AND `school_id` = $school 
	GROUP BY
	student_id
	ORDER BY cc desc 
	limit 10
EOF;

            $school_learning_student = \DB::select(\DB::raw($sql));


            $sort = [];
            $school_learning_student = json_decode(json_encode($school_learning_student),true);
            $school_learning_student = collect($school_learning_student)->sortByDesc('cc')->toArray();
            foreach ($school_learning_student as $item){
                $name = $item['name'];
                $substr = substr($name, strlen($name)-3,3);

                $is_cn = preg_match("/^[\x7f-\xff]+$/", $substr);  //判断是否是中文
                if($is_cn){
                    $name = str_replace($substr, '*', $name);
                }else{
                    $name = substr($name, 0,strlen($name)-1).'*';
                }

                if ($name == '*') $name = $item['name'];
                $count = $item['cc'];
                $sort[] = $name.'('.$count.')';
            }

            $report_3rd[] = [
                $all_record[$school]['school_id'],
                $all_record[$school]['school_name'],
                $all_record[$school]['marketer_name'],
                $all_record[$school]['operator_name'],
                $all_record[$school][Carbon::yesterday()->toDateString()],
                $all_record[$school][Carbon::yesterday()->subDays(1)->toDateString()],
                $all_record[$school][Carbon::yesterday()->subDays(2)->toDateString()],

                isset($sort[0]) ? $sort[0] : null,
                isset($sort[1]) ? $sort[1] : null,
                isset($sort[2]) ? $sort[2] : null,
                isset($sort[3]) ? $sort[3] : null,
                isset($sort[4]) ? $sort[4] : null,
                isset($sort[5]) ? $sort[5] : null,
                isset($sort[6]) ? $sort[6] : null,
                isset($sort[7]) ? $sort[7] : null,
                isset($sort[8]) ? $sort[8] : null,
                isset($sort[9]) ? $sort[9] : null,
            ];
        }




        $filename = 'BXG_learn_student_' . date('md');
        $path = 'learning';
        $file = $this->sheetsStore($path . '/' . $filename, ['学校学习学生'=>$report, '前十名学生(筛选)'=>$report_2nd, '前十名学生(全部)'=>$report_3rd]);

        $this->email(['xiemin68@163.com','shirui2811@126.com','zhanxuewen2018@126.com'],
            'emails.export2', ['object' => '每日' . '学校学习人数'],
            '百项过学习人数统计_'.date('md'), realpath($file));
    }

}