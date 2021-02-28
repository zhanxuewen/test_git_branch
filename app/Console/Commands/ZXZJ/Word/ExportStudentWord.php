<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Learning\ExportBXGCardReport;
use App\Console\Schedules\Learning\ExportBXGMonthReport;
use App\Console\Schedules\Learning\ExportSchoolLearningStudent;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use App\Http\Controllers\Export\SchoolController;
use App\Library\Curl;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportStudentWord extends Command
{

    use Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:student:word';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出学生单词学习';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        config(['database.default' => 'online']);

        $return_data = [];

        $return_data[] = [
            'student_id'=>'学生id',
            'nickname'=>  '学生姓名',
            'phone1'  => '学生手机号',
            'phone2'  => '学生手机号',
            'vanclass'=>  '所在班级',
            'mark_name'=>  '备注名',
            'date_count'=>'学习天数',
            'word_count'=>'已背单词总数',
            'word_total'=>'总词量'
        ];

        $sql = <<<EOF
SELECT
	user_account.id,
	INSERT (user.phone, 4, 4, '****')  AS phone1,
	user.phone phone2,
	user_account.nickname ,
	GROUP_CONCAT(vanclass.`name`) vanclass_name ,
	GROUP_CONCAT(vanclass_student.mark_name) mark_name,
	 word_student_fluency_overview.count_level_gt_0
FROM
	`b_vanthink_online`.`school_member` 
	left join user_account on school_member.account_id = user_account.id
	left join user on user.id = user_account.user_id
	left join vanclass_student on vanclass_student.student_id = user_account.id and vanclass_student.is_active=1
	left join vanclass on vanclass_student.vanclass_id = vanclass.id
	left join word_student_fluency_overview on word_student_fluency_overview.student_id = user_account.id 
WHERE
	school_member.`school_id` = '3487' 
	AND school_member.`account_type_id` = '5' 
GROUP BY user_account.id
EOF;


        $student_info = \DB::select(\DB::raw($sql));


        foreach ($student_info as $student_item){

            $student_id = $student_item->id;
            $nickname = $student_item->nickname;
            $vanclass_name = $student_item->vanclass_name;
            $word_total = $student_item->count_level_gt_0;
            $phone1 = $student_item->phone1;
            $phone2 = $student_item->phone2;
            $mark_name = $student_item->mark_name;

            // 查询学生的 练习信息


            $record = \DB::table('word_homework_student_record')
                ->selectRaw('wordbank_ids,created_at')
                ->where('student_id', $student_id)
                ->whereNull('deleted_at')
                ->where('created_at', '>=', '2020-04-25 00:00:00')
                ->get();

            $word_ids = [];
            $date_arr = [];
            foreach ($record as $record_item){
                $wordbank_ids = $record_item->wordbank_ids;
                $word_tmp = explode(',', $wordbank_ids);
                $word_ids = array_merge($word_ids, $word_tmp);
                $date_arr[] = substr($record_item->created_at,0,10);
            }

            $word_count = count(array_unique($word_ids));
            $date_count = count(array_unique($date_arr));

            $return_data[] = [
                'student_id'=>$student_id,
                'nickname'=>  $nickname,
                'phone1'  => $phone1,
                'phone2'  => $phone2,
                'vanclass'=>  $vanclass_name,
                'mark_name'=>  $mark_name,
                'date_count'=> $date_count ? $date_count : '0',
                'word_count'=> $word_count ? $word_count : '0',
                'word_total'=> empty($word_total) ? '0' : $word_total
            ];

            echo '+';
        }



        $this->store('冒芽儿文化学生单词学习（0425~0519）'.rand(0,100), $return_data, '.xlsx');
    }
}
