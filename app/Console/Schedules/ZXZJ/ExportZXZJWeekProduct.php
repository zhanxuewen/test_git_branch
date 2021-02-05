<?php

namespace App\Console\Schedules\ZXZJ;

use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;

class ExportZXZJWeekProduct extends BaseSchedule
{

    private $max_id_arr = [];


    public function handle()
    {
        ini_set('memory_limit', '2048M');
        config(['database.default' => 'zxzj_online_search']);




######################时间
        $week = Carbon::now()->subWeek()->weekOfYear;
        $week <10 ? $week = '0'.$week : null;
        $date_type = 'W2021-'.$week;

        $start_time = Carbon::now()->subWeek()->startOfWeek()->toDateTimeString();
        $end_time = Carbon::now()->subWeek()->endOfWeek()->toDateTimeString();

        $start_date = Carbon::now()->subWeek()->startOfWeek()->toDateString();
        $end_date = Carbon::now()->subWeek()->endOfWeek()->toDateString();

        $max_id_date = Carbon::now()->subDays(10)->toDateString();
##########################################################################

###################### max id
        $max_id_record = \DB::table('statistic_schedule_tmp')
            ->where('key', 'table_nearly_max_id')
            ->where('created_date', $max_id_date)
            ->first();

        if (empty( $max_id_record )) dd( ' max id not find');
        $max_ids = $max_id_record->value;
        $this->max_id_arr = json_decode($max_ids, true );
############################################################################


        $export_student = [];
        $student_info = $this->getStudentInfo($start_time, $end_time);

        foreach ( $student_info as $student_info_item){
            $export_student[] = [
                'key' => $student_info_item['key'],
                'count' => $student_info_item['count'],
            ];
        }
        // 获取额外的数据
//        $student_other_info = $this->getStudentOtherInfo($start_time, $end_time);

        $export_teacher = [];
        $teacher_info = $this->getTeacherInfo($start_time, $end_time);
        foreach ( $teacher_info as $teacher_info_item){
            $export_teacher[] = [
                'key1' => $teacher_info_item['key1'],
                'count_teacher' => $teacher_info_item['count_teacher'],
                'key2' => $teacher_info_item['key2'],
                'count' => $teacher_info_item['count'],
            ];
        }


        $export_share_info = [];
        $share_info = $this->getShareInfo($start_time, $end_time);
        foreach ($share_info as $share_info_item) {
            $export_share_info[] = [
                'type' => $share_info_item['type'],
                'table_name' => $share_info_item['table_name'],
                'count' => $share_info_item['count'],
                'student_count' => $share_info_item['student_count'],
            ];
        }

        $export_VIP_info = [];
        $export_VIP_info[] = [
            '类型', '学生人数','付费次数'
        ];
        $VIP_info = $this->getVIPStudent($start_time, $end_time);
        $VIP_total_info = $this->getVIPStudentTotal($start_time, $end_time);
        foreach ($VIP_info as $VIP_info_item) {
            $export_VIP_info[] = [
                'key' => $VIP_info_item['key'],
                'count_student' => $VIP_info_item['count_student'],
                'count' => $VIP_info_item['count'],
            ];
        }
        foreach ($VIP_total_info as $VIP_info_item) {
            $export_VIP_info[] = [
                'key' => '总计',
                'count_student' => $VIP_info_item['count_student'],
                'count' => $VIP_info_item['count'],
            ];
        }

        // 平台数据
        $platform_data = \DB::table('statistic_marketer_record')
            -> where('date_type', $date_type)
            -> where('marketer_type', 'system_all' )
            -> first();

        $start_school_info_ids = \DB::table('statistic_school_record')
            ->selectRaw('school_id')
            ->where('date_type', $start_date)
            ->get()->pluck('school_id')->toArray();


        $end_school_info_ids = \DB::table('statistic_school_record')
            ->selectRaw('school_id')
            ->where('date_type', $end_date)
            ->get()->pluck('school_id')->toArray();

        $export_platform_data = [];

        $export_platform_data[] = [
            '新增老师',
            '活跃老师',
            '老师总数',

            '新增学生',
            '活跃学生',
            '学生总数',
            '提分版学生',
            '仅试用学生',

            '学校总数',
            '新增学校',
            '期间新创学校',
            '期间删除学校',
        ];

        $export_platform_data[] = [
            $platform_data->new_teacher,
            $platform_data->act_teacher,
            $platform_data->teacher_total,

            $platform_data->new_student,
            $platform_data->act_student,
            $platform_data->student_total,
            $platform_data->vip_student,
            $platform_data->try_student,

            count($end_school_info_ids).'',
            (count($end_school_info_ids) - count($start_school_info_ids) ) .'',
            count(array_diff( $end_school_info_ids, $start_school_info_ids)).'',
            count(array_diff( $start_school_info_ids, $end_school_info_ids)).''
        ];



        $export_student_NB_date = [];
        $student_NB_info = $this->getStudentNBInfo();

        foreach ( $student_NB_info as $student_NB_item ){
            $keys = array_keys($student_NB_item);
            $value = array_values($student_NB_item);
            $export_student_NB_date[] = $keys;
            $export_student_NB_date[] = $value;
        }


        $export_school_NB_data = [];
        $school_NB_info = $this->getSchoolNBInfo();
        $export_school_NB_data[] = [
            '用户id',
            '姓名',
            '类型',
            '学校id',
            '学校',
            '次数',
            '最后下载'
        ];

        foreach ($school_NB_info as $school_NB_info_item ){
            $export_school_NB_data[] = [
                $school_NB_info_item['id'],
                $school_NB_info_item['nickname'],
                $school_NB_info_item['type_name'],
                $school_NB_info_item['school_id'],
                $school_NB_info_item['name'],
                $school_NB_info_item['count'],
                $school_NB_info_item['max_time']
            ];
        }



        $homework_chat_for_one = $this->getHomeworkChatForOne($start_time, $end_time);
        $export_homework_chat_for_one_data = [];

        $export_homework_chat_for_one_data[] = [
            'id','创建时间','作业id','老师id','学生已看人数','已看人数'
        ];
        foreach ( $homework_chat_for_one as $homework_chat_for_one_item){
            $export_homework_chat_for_one_data[] = [
                $homework_chat_for_one_item['id'],
                $homework_chat_for_one_item['created_at'],
                $homework_chat_for_one_item['homework_id'],
                $homework_chat_for_one_item['teacher_id'],
                $homework_chat_for_one_item['student_see'].'',
                $homework_chat_for_one_item['see_total'].''
            ];
        }


        $export_homework_chat_for_vanclass_data = [];
        $homework_chat_for_vanclass = $this->getHomeworkChatForVanclass($start_time, $end_time);
        $export_homework_chat_for_vanclass_data[] = [
            'id','创建时间','作业id','班级id','班级名称','班级学生人数','老师id','老师', '学生已看人数','已看人数'
        ];
        foreach ( $homework_chat_for_vanclass as $homework_chat_for_vanclass_item){
            $export_homework_chat_for_vanclass_data[] = [
                $homework_chat_for_vanclass_item['id'],
                $homework_chat_for_vanclass_item['created_at'],
                $homework_chat_for_vanclass_item['homework_id'],
                $homework_chat_for_vanclass_item['vanclass_id'],
                $homework_chat_for_vanclass_item['name'],

                $homework_chat_for_vanclass_item['student_count'].'',
                $homework_chat_for_vanclass_item['teacher_id'],
                $homework_chat_for_vanclass_item['teacher'],
                $homework_chat_for_vanclass_item['student_see'].'',
                $homework_chat_for_vanclass_item['total_see'].'',
            ];
        }







        $file_name = $date_type.'-在线助教功能数据';

        $this->sheetsStore($file_name.rand(0,100), [
            '学生数量'=>$export_student,

            '老师数据'=>$export_teacher,
            '平台数据'=>$export_platform_data,
            '付费学生'=>$export_VIP_info,
            '分享记录'=>$export_share_info,

            '学生年报_累计'=>$export_student_NB_date,
            '学校年报_累计'=>$export_school_NB_data,
            '作业点评_个人'=>$export_homework_chat_for_one_data,
            '作业点评_班级'=>$export_homework_chat_for_vanclass_data,
        ]);
        dd('done....');

    }

    private function getStudentInfo($start_time, $end_time){

        $sql = <<<EOF
SELECT
  '多少学生使用了单词本' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`word_homework_student_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  AND `deleted_at` IS NULL
  and id >= {$this->max_id_arr['word_homework_student_record']}
  
  UNION

  SELECT
  '多少学生使用了错题本' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`user_student_wrong_note_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  and id >= 1351291

UNION

SELECT
  '多少学生使用了千词闯关' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`qian_ci_student_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  AND `deleted_at` IS NULL
and id >= 388011


UNION 
  
  
SELECT
  '多少学生参与了打卡活动' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`activity_student_book_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
and id >= {$this->max_id_arr['activity_student_book_record']}


UNION 
  
  SELECT
  '多少学生使用了每日一听' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`listening_student_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  AND `deleted_at` IS NULL
and id >= {$this->max_id_arr['listening_student_record']}

UNION

  
  SELECT
  '多少学生做了作业' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`homework_student_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
and id >= {$this->max_id_arr['homework_student_record']}


UNION
    SELECT
  '多少学生做了试卷' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`exam_student_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  AND `deleted_at` IS NULL
and id >= {$this->max_id_arr['exam_student_record']}


UNION 
  
      SELECT
  '多少学生使用了图书馆' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`library_student_book_record` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
and id >= {$this->max_id_arr['library_student_book_record']}


UNION 
  
        SELECT
  '登入打卡抽奖' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`puzzle_history` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  and homework_id is NULL
  and id >= 39011235
  
  union 
  
SELECT
  ' 图书馆磨耳朵' as 'key', count(DISTINCT student_id) count
FROM
  `b_vanthink_online`.`record_student_entity_spend_time` 
  WHERE 
  `created_at` > '$start_time'
  AND `created_at` <= '$end_time'
  and id >= {$this->max_id_arr['record_student_entity_spend_time']}
EOF;
        $student_info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($student_info) , true);
    }


    private function getTeacherInfo($start_time, $end_time){
        $sql = <<<EOF
SELECT
	'有多少老师布置了作业' as `key1`,count(DISTINCT teacher_id) count_teacher,
	'共布置了多少份作业' as `key2`,count(*) count 
FROM
	`b_vanthink_online`.`homework` 
WHERE
	`created_at` > '$start_time' 
	AND `created_at` <= '$end_time' 
	AND `deleted_at` IS NULL

	UNION
		
SELECT
	'多少老师布置了试卷' as `key1`,count(DISTINCT account_id) count_teacher,
	'共布置了多少试卷' as `key2`,count(*) count
FROM
	`b_vanthink_online`.`test_quotation` 
WHERE
	`created_at` > '$start_time' 
	AND `created_at` <= '$end_time' 
	AND `deleted_at` IS NULL
		
	UNION	
		
SELECT
	'多少老师布置了单词本' as `key1`,count(DISTINCT account_id) count_teacher,
	'共布置了多少单词本' as `key2`,count(*) count
FROM
	`b_vanthink_online`.`word_homework` 
WHERE
	`created_at` > '$start_time' 
	AND `created_at` <= '$end_time' 
	AND `deleted_at` IS NULL
	
	UNION	
		
SELECT
	'多少老师布置了打卡活动' as `key1`,count(DISTINCT account_id) count_teacher,
	'共布置了多少打卡活动' as `key2`,count(*) count
FROM
	`b_vanthink_online`.`activity` 
WHERE
	`created_at` > '$start_time' 
	AND `created_at` <= '$end_time' 
	AND `deleted_at` IS NULL
		
	UNION

SELECT
	'多少老师布置了千词闯关' as `key1`,count(DISTINCT teacher_id) count_teacher,
	'共布置多少份千词闯关' as `key2`,count(*) count
FROM
	`b_vanthink_online`.`qian_ci_book` 
WHERE
	`created_at` > '$start_time' 
	AND `created_at` <= '$end_time' 
	AND `deleted_at` IS NULL
	
	UNION	
		
SELECT
	'多少老师创建了大题' as `key1`,count(DISTINCT account_id) count_teacher,
	'共多少大题' as `key2`,count(*) count
FROM
	`b_vanthink_online`.`testbank` 
WHERE
	`created_at` > '$start_time' 
	AND `created_at` <= '$end_time' 
	AND `deleted_at` IS NULL
EOF;

        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);
    }

    private function getShareInfo($start_time, $end_time){
        $sql = <<<EOF
SELECT
CASE  table_name
WHEN 'activity_certificate_clock' THEN
    '打卡活动证书分享'
WHEN 'activity_clock' THEN
    '打卡活动分享'
WHEN 'exam_share' THEN
    '试卷分享'
WHEN 'homework_pl_share' THEN
    '作业磨耳朵分享'
WHEN 'homework_share' THEN
    '作业分享'
WHEN 'library_book_clock' THEN
    '图书馆图书打卡'
WHEN 'library_medal_share' THEN
    '图书馆勋章分享'
WHEN 'listening_clock' THEN
    '每日一听打卡'
WHEN 'listening_share' THEN
    '每日一听分享'
WHEN 'listening_upgrade_share' THEN
    '每日一听升级分享'
WHEN 'puzzle_share' THEN
    '拼图分享'
WHEN 'student_wrong_node_share' THEN
    '错题本分享'
WHEN 'word_clock' THEN
    '单词本打卡'
WHEN 'word_share' THEN
    '单词本分享'
WHEN 'word_student_test_share' THEN
    '单词自测分享'
WHEN 'homework_student_record' THEN
    '作业中口语H5分享'
WHEN 'library_student_book_record' THEN
    '图书馆磨耳朵+口语分享'
		
WHEN 'recruit_student' THEN
    '千词超人'
WHEN 'qianci' THEN
    '千词闯关'
END as type,table_name , count(*) count,count(DISTINCT student_id ) student_count
FROM
	`b_vanthink_online`.`user_share_record` 
WHERE
	`id` >= '2400000' 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
GROUP BY 
table_name

EOF;
        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);

    }

    private function getVIPStudent($start_time, $end_time){
        $sql = <<<EOF
select `key`, COUNT( DISTINCT student_id ) count_student , count(*) count, sum(pay_fee) total_fee from (
SELECT
	'E档开卡' as `key`, binding_student_id student_id, created_at, 0 as pay_fee
FROM
	`b_vanthink_online`.`order_class_e_card` 
WHERE 
created_at >= '$start_time'
and created_at <= '$end_time'

UNION

SELECT
	'线上购买' as `key`, student_id , paid_at, pay_fee
FROM
	`b_vanthink_online`.`order` 
WHERE
	`paid_at` >= '$start_time'
and created_at <= '$end_time'
and finished_at is not NULL


UNION

SELECT
	'线下购买' as `key`, student_id , paid_at, pay_fee
FROM
	`b_vanthink_online`.`order_offline` 
WHERE
	`paid_at` >= '$start_time'
and created_at <= '$end_time'

) tmp 
 GROUP BY `key`
EOF;

        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);
    }

    private function getVIPStudentTotal($start_time, $end_time){
        $sql = <<<EOF
select `key`, COUNT( DISTINCT student_id ) count_student , count(*) count, sum(pay_fee) total_fee from (
SELECT
	'E档开卡' as `key`, binding_student_id student_id, created_at, 0 as pay_fee
FROM
	`b_vanthink_online`.`order_class_e_card` 
WHERE 
created_at >= '$start_time'
and created_at <= '$end_time'

UNION

SELECT
	'线上购买' as `key`, student_id , paid_at, pay_fee
FROM
	`b_vanthink_online`.`order` 
WHERE
	`paid_at` >= '$start_time'
and created_at <= '$end_time'
and finished_at is not NULL


UNION

SELECT
	'线下购买' as `key`, student_id , paid_at, pay_fee
FROM
	`b_vanthink_online`.`order_offline` 
WHERE
	`paid_at` >= '$start_time'
and created_at <= '$end_time'

) tmp 
--  GROUP BY `key`
EOF;

        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);
    }

    private function getStudentNBInfo(){
        $sql = <<<EOF
SELECT
	count(student_id) 总人数 , count(DISTINCT student_id) 人数 , sum(checked_count) 查看次数 ,count(IF( clicked_count <> 0 or shared_count <> 0, 1, null )) 分享人数 ,  SUM( IF( clicked_count <> 0 and shared_count = 0 ,1 ,shared_count)) 分享次数, sum(clicked_count) 分享后被点击次数
FROM
	`b_vanthink_online`.`statistic_student_year_report_share` 

EOF;
        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);


    }

    private function getSchoolNBInfo(){
        $sql = <<<EOF
select tmp.*,count(*)  count, MAX(created_at) max_time from (
SELECT
	user_account.id, user_account.nickname, type_name, school.id school_id,school.`name`,`logs`.created_at
FROM
	`b_vanthink_online`.`logs` 
	LEFT JOIN user_account on user_account.id = `logs`.account_id
	left join school on school.id = `logs`.object_id
	left join user_type on user_account.user_type_id = user_type.id
WHERE
	`log_type_id` = '1' 
	AND `project` = 'school' 
) tmp 
GROUP BY id, school_id
EOF;

        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);


    }


    private function getHomeworkChatForOne( $start_time, $end_time ){

        $sql = <<<EOF
SELECT
    homework_chat.id ,
	homework_chat.created_at ,
	homework_chat.homework_id , 
	homework.teacher_id , 
	count(IF(homework.teacher_id != homework_chat_member.account_id  ,1,NULL)) student_see,
	count(homework_chat_member.id) see_total
FROM
	`b_vanthink_online`.`homework_chat` 
	left join homework_chat_member on homework_chat_member.chat_id = homework_chat.id and  homework_chat_member.last_read_id <> 0
	left join homework on homework.id = homework_chat.homework_id
WHERE
	`is_group` = '0'
	and homework_chat.created_at >= '$start_time'
	and homework_chat.created_at <= '$end_time'
	GROUP BY homework_chat.id
	ORDER BY homework_chat.id  asc
EOF;

        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);
    }

    private function getHomeworkChatForVanclass( $start_time, $end_time ){
        $sql = <<<EOF
SELECT
	homework_chat.id ,
	homework_chat.created_at ,
	homework_chat.homework_id , 
	homework_chat.vanclass_id ,  
	vanclass.`name`  ,
	vanclass.student_count ,
	user_account.id teacher_id ,  
	user_account.nickname teacher,
	count(IF(vanclass_teacher.teacher_id != homework_chat_member.account_id,1,NULL)) student_see,
	count(homework_chat_member.id) total_see
FROM
	`b_vanthink_online`.`homework_chat` 
	left join vanclass on vanclass.id = homework_chat.vanclass_id
	left join vanclass_teacher on vanclass_teacher.vanclass_id = homework_chat.vanclass_id
	left join user_account on user_account.id = vanclass_teacher.teacher_id
	left join homework_chat_member on homework_chat_member.chat_id = homework_chat.id AND last_read_id <> 0
WHERE
	`is_group` = '1' 
	and homework_chat.created_at >= '$start_time'
	and homework_chat.created_at <= '$end_time'
GROUP BY homework_chat.id
ORDER BY homework_chat.id  asc
EOF;
        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);
    }

    private function getStudentOtherInfo($start_time, $end_time){
        $sql = <<<EOF

EOF;
        $info  = \DB::select(\DB::raw($sql));
        return json_decode(json_encode($info) , true);
    }
}