<?php

namespace App\Console\Schedules\Learning;


use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;

class ExportZXZJWeekOperator extends BaseSchedule
{
    public function handle()
    {
        ini_set('memory_limit', '2048M');
        config(['database.default' => 'zxzj_online_search']);


        $day_count = 7;
        $date_type = 'W2021-03';
        $start_time = '2021-01-18 00:00:00';
        $end_time = '2021-01-24 23:59:59';
        $start_date = '2021-01-18';
        $end_date = '2021-01-24';

        $rep = [];
        $rep[] = [
            '学校ID',
            '学校名称',
            '省',
            '地市',
            '区县',
            '创建时间',
            '校长',
            '校长手机',

            '市场专员',
            '运营专员',

            '签约日期',
            '合作档位',

            '老师总数',
            '活跃老师',

            '学生总数',
            '周活跃学生',
            '月活跃学生',
            '半年活跃学生',
            '提分版学生',

            '作业布置量',
            '做作业人数',

            '试卷布置量',
            '做试卷人数',

            '单词本布置量',
            '单词本使用人数',

            '千词闯关布置量',
            '千词闯关使用人数',

            '打卡活动布置量',
            '打卡活动使用人数',

            '是否有推荐到本校题库',
            '启动页设置',
            '招生二维码设置',
            '招生班是否设置',

            '图书管理员是否设置',
            '图书馆是否有上架',
            '使用图书馆学生',

            '礼品中心是否上架',
            '轻课是否开启',
            '微官网是否设置',
            '拼团活动是否设置',
            '招生活动是否设置',
        ];

        $school_attr = $this->getSchoolAttribute();
        $school_attr_arr =  [];
        foreach ($school_attr as $key=>$value ){
            $school_attr_arr[$key] = explode( ',',$value );
        }
        echo 'school_attr----';
        $school_popular = $this->getSchoolPop();
        $school_popular_arr = [];
        foreach ($school_popular as $key=>$value ){
            $school_popular_arr[$key] = explode( ',',$value );
        }
        echo 'school_popular----';
        $school_vanclass = $this->getRecruitVanclass();
        echo 'school_vanclass----';
        $school_info = $this->getSchoolInfo( $date_type );
        echo 'school_info----';
        $school_goods = $this->getGoodsInfo();

        echo 'school_goods----';
        $school_course = $this->getSchoolCourse();

        echo 'school_course----';
        $school_activity = $this->getSchoolActivity();
        echo 'school_activity----';

        // 拼接数据
        foreach ($school_info as $school_id=>$school_info_item){
            echo $school_id;
            $region = $school_info[$school_id]['school_region'];
            $region_arr = explode('/', $region);

            $student_ids = $this->getSchoolStudents($school_id);
            $teacher_ids = $this->getSchoolTeachers($school_id);
            echo '+a';
            $act_students = $this->getActStudent($student_ids);
            echo '+b';
            $homework_info = $this->getHomeworkInfo($student_ids,$teacher_ids, $start_time, $end_time);
            echo '+c';
            $exam_info = $this->getExamInfo($student_ids,$teacher_ids, $start_time, $end_time);
            echo '+d';
            $word_info = $this->getWordInfo($student_ids,$teacher_ids, $start_time, $end_time);
            echo '+e';
            $qian_ci_info = $this->getQianCiInfo($student_ids,$teacher_ids, $start_time, $end_time);
            echo '+f';
            $activity_info = $this->getActivityInfo($student_ids,$teacher_ids, $start_time, $end_time);
            echo '+g';
            $school_testbank_info = $this->getSchoolTestbankInfo($school_id,  $start_time, $end_time);
            echo '+h';
            $school_library_status = $this->getSchoolLibraryStatus($school_id);
            echo '+i';
            $school_library_student = $this->getSchoolLibraryStudent($student_ids, $start_time, $end_time );
            echo '+j';
            $rep[] = [
                $school_id,
                $school_info[$school_id]['school_name'],
                $region_arr[0],
                isset($region_arr[1]) ? $region_arr[1] : '',
                isset($region_arr[2]) ? $region_arr[2] : '',
                $school_info[$school_id]['school_created_at'],
                $school_info[$school_id]['principal_name'],
                $school_info[$school_id]['principal_phone'],

                $school_info[$school_id]['marketer_name'],
                $school_info[$school_id]['operator_name'],

                $school_info[$school_id]['sign_contract_date'],
                $school_info[$school_id]['contract_class'],


                $school_info[$school_id]['teacher_total'],
                $school_info[$school_id]['act_teacher'],

                $school_info[$school_id]['student_total'],
                $school_info[$school_id]['act_student'],
                $act_students['month_count'],
                $act_students['left_year_count'],
                $school_info[$school_id]['vip_student'],

                $homework_info['teacher_count'].'/'. $homework_info['teacher_homework'],
                $homework_info['total_student_count'].'/'. $homework_info['total_homework_count'],


                $exam_info['teacher_count'].'/'. $exam_info['teacher_exam'],
                $exam_info['total_student_count'].'/'. $exam_info['total_exam_count'],


                $word_info['teacher_count'].'/'. $word_info['teacher_word'],
                $word_info['total_student_count'],

                $qian_ci_info['teacher_count'].'/'. $qian_ci_info['teacher_book'],
                $qian_ci_info['total_student_count'],

                $activity_info['teacher_count'].'/'. $activity_info['activity_count'],
                $activity_info['total_student_count'],


                $school_testbank_info,
                in_array(  $school_id , $school_attr_arr['home_page_src'] ) ? 1 : '0',
                in_array(  $school_id , $school_popular_arr['QRcode'] ) ? 1 : '0',
                in_array(  $school_id , $school_vanclass ) ? 1 : '0',

                in_array(  $school_id , $school_attr_arr['library_manager'] ) ? 1 : '0',
                $school_library_status,
                $school_library_student,

                isset($school_goods[$school_id]) ? $school_goods[$school_id] : '-',
                isset($school_course[$school_id]) ? $school_course[$school_id] : '-',

                in_array(  $school_id , $school_popular_arr['official_website_status_finish'] ) ? '已发布' :
                    ( in_array(  $school_id , $school_popular_arr['official_website_status_temporary'] ) ? '已编辑' : '-') ,

                isset( $school_activity['pay_group'][$school_id]) ?  $school_activity['pay_group'][$school_id] : '-',
                isset( $school_activity['recruit_activity'][$school_id]) ?  $school_activity['recruit_activity'][$school_id] : '-',


            ];

            echo '======';
        }

        $this->store('在线助教周数据_'.rand(0,100), $rep, '.xlsx');

        dd('done....');

    }


    private function getSchoolInfo( $date_type){

        $sql = <<<EOF
SELECT
	 statistic_school_record_weekly.school_id,
	 school.`name` school_name,
	 school.`created_at` school_created_at,
	 statistic_school_record_weekly.school_region,
	 principal.nickname principal_name,
	 statistic_school_record_weekly.principal_phone,
	 marketer.nickname marketer_name,
	 operator.nickname operator_name,
	 statistic_school_record_weekly.contract_class,
	 statistic_school_record_weekly.sign_contract_date,
	 statistic_school_record_weekly.teacher_total,
	 statistic_school_record_weekly.act_teacher,
	 statistic_school_record_weekly.student_total,
	 statistic_school_record_weekly.act_student,
	 statistic_school_record_weekly.vip_student
FROM
	school
	LEFT  JOIN statistic_school_record_weekly on school.id  = statistic_school_record_weekly.school_id and statistic_school_record_weekly.`date_type` = '$date_type'
	left  join user_account  principal on principal.id = statistic_school_record_weekly.principal_id
	left  join user_account marketer on marketer.id = statistic_school_record_weekly.marketer_id
    left  join user_account operator on operator.id = statistic_school_record_weekly.afterSales_id
EOF;
        $school_info = \DB::select(\DB::raw($sql));
        $school_info = json_decode(json_encode($school_info) , true);
        echo '+';
        return collect($school_info )->keyBy('school_id')->toArray();

    }

    private function  getSchoolTeachers($school_id){
        $sql = <<<EOF
SELECT account_id FROM `school_member` WHERE `school_id` = '$school_id' AND `account_type_id` = '4'
EOF;
        $teacher = \DB::select(\DB::raw($sql));
        $teacher_ids = array_column( $teacher, 'account_id');
        return $teacher_ids;
    }


    private function getSchoolStudents($school_id){

        $sql = <<<EOF
SELECT
	DISTINCT account_id  
FROM
	`user_account_attribute` 
WHERE
	user_account_attribute.`value` = '$school_id' 
	AND user_account_attribute.`key` = 'default_school' 
EOF;
        $student = \DB::select(\DB::raw($sql));
        $student_ids = array_column( $student, 'account_id');

        return $student_ids;

    }


    private function getActStudent($student_ids){
        $month_start = Carbon::now()->subMonth()->toDateString();
        $left_year_start = Carbon::now()->subMonth( 6)->toDateString();

        $month_count = 0;
        $left_year_count = 0;

        foreach (array_chunk($student_ids, 50 ) as $student_ids_chunk){

            $str_ids = implode(',', $student_ids_chunk);

            $sql = <<<EOF
SELECT
	student_id, max(created_date) created_date
FROM
	`statistic_student_activity` 
WHERE
	`student_id` IN ( $str_ids ) 
GROUP BY student_id
EOF;
            $student_info = \DB::select(\DB::raw($sql));

            foreach ( $student_info as $student_item){
                $student_date = $student_item->created_date;
                if ( $student_date >= $month_start){
                    $month_count++;
                }

                if ( $student_date >= $left_year_start){
                    $left_year_count++;
                }
            }
        }

        return [
          'month_count' => $month_count,
          'left_year_count' => $left_year_count,
        ];

    }


    private function getHomeworkInfo(
        $student_ids,$teacher_ids, $start_time, $end_time
    ){
        $teacher_str = implode(',',$teacher_ids );
        // 老师
        if (!empty($teacher_str )){
            $sql = <<<EOF
SELECT
	count(DISTINCT teacher_id) teacher_count, count(*)  homework_count
FROM
	`homework` 
WHERE
	`teacher_id` IN ( $teacher_str ) 
	AND `deleted_at` IS NULL 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
EOF;
            $teacher_info = \DB::select(\DB::raw($sql))[0];
        }else{
            $teacher_info = new \stdClass();
        }


        $total_student_count = 0;
        $total_homework_count = 0;
        // 学生
        foreach ( array_chunk(  $student_ids , 10 ) as $student_ids_chunk){
            $student_str = implode( ',', $student_ids_chunk );
            $sql = <<<EOF
SELECT
	count(DISTINCT student_id) student_count , count(*) homework_count
FROM
	`homework_student_record` 
WHERE
	`student_id` IN ($student_str)
AND `created_at` >= '$start_time' 
AND `created_at` <= '$end_time'
and  id > 243764885
EOF;

            $student_chunk_info = \DB::select(\DB::raw($sql))[0];

            $student_count = empty( $student_chunk_info->student_count ) ?  0 :intval( $student_chunk_info->student_count );
            $homework_count = empty( $student_chunk_info->homework_count ) ?  0 :intval( $student_chunk_info->homework_count );

            $total_student_count += $student_count;
            $total_homework_count += $homework_count;
        }


        return  [
            'total_student_count' => $total_student_count,
            'total_homework_count' => $total_homework_count,
            'teacher_count' => empty( $teacher_info->teacher_count ) ?  0 :intval( $teacher_info->teacher_count ),
            'teacher_homework' =>empty( $teacher_info->homework_count ) ?  0 :intval( $teacher_info->homework_count )
        ];
    }

    private function getExamInfo(
        $student_ids,$teacher_ids, $start_time, $end_time
    ){
        $teacher_str = implode(',',$teacher_ids );

        if (!empty($teacher_str )){

            $sql = <<<EOF
SELECT
	count(DISTINCT account_id) teacher_count, count(*)  exam_count
FROM
	`test_quotation` 
WHERE
	`account_id` IN ( $teacher_str ) 
	AND `deleted_at` IS NULL 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
EOF;
            $teacher_info = \DB::select(\DB::raw($sql))[0];

        }else{
            $teacher_info = new \stdClass();
        }



        // 老师


        $total_student_count = 0;
        $total_exam_count = 0;
        // 学生
        foreach ( array_chunk(  $student_ids , 10 ) as $student_ids_chunk){
            $student_str = implode( ',', $student_ids_chunk );
            $sql = <<<EOF
SELECT
	count(DISTINCT student_id) student_count , count(*) exam_count
FROM
	`exam_student_record` 
WHERE
	`student_id` IN ($student_str)
AND `created_at` >= '$start_time' 
AND `created_at` <= '$end_time'
AND `deleted_at` IS NULL 
and id > 3313767
EOF;

            $student_chunk_info = \DB::select(\DB::raw($sql))[0];

            $student_count = empty( $student_chunk_info->student_count ) ?  0 :intval( $student_chunk_info->student_count );
            $exam_count = empty( $student_chunk_info->exam_count ) ?  0 :intval( $student_chunk_info->exam_count );

            $total_student_count += $student_count;
            $total_exam_count += $exam_count;
        }


        return  [
            'total_student_count' => $total_student_count,
            'total_exam_count' => $total_exam_count,
            'teacher_count' => empty( $teacher_info->teacher_count ) ?  0 :intval( $teacher_info->teacher_count ),
            'teacher_exam' =>empty( $teacher_info->exam_count ) ?  0 :intval( $teacher_info->exam_count )
        ];
    }

    private function getWordInfo(
        $student_ids,$teacher_ids, $start_time, $end_time
    ){
        $teacher_str = implode(',',$teacher_ids );


        if (!empty($teacher_str )){

            $sql = <<<EOF
SELECT
	count(DISTINCT account_id) teacher_count, count(*)  word_count
FROM
	`word_homework` 
WHERE
	`account_id` IN ( $teacher_str ) 
	AND `deleted_at` IS NULL 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
EOF;
            $teacher_info = \DB::select(\DB::raw($sql))[0];

        }else{
            $teacher_info = new \stdClass();
        }
        // 老师


        $total_student_count = 0;
        // 学生
        foreach ( array_chunk(  $student_ids , 10 ) as $student_ids_chunk){
            $student_str = implode( ',', $student_ids_chunk );
            $sql = <<<EOF
SELECT
	count(DISTINCT student_id) student_count 
FROM
	`word_homework_student_record` 
WHERE
	`student_id` IN ($student_str)
AND `created_at` >= '$start_time' 
AND `created_at` <= '$end_time'
AND `deleted_at` IS NULL 
and  id > 11534439
EOF;

            $student_chunk_info = \DB::select(\DB::raw($sql))[0];
            $student_count = empty( $student_chunk_info->student_count ) ?  0 :intval( $student_chunk_info->student_count );
            $total_student_count += $student_count;
        }


        return  [
            'total_student_count' => $total_student_count,
            'teacher_count' => empty( $teacher_info->teacher_count ) ?  0 :intval( $teacher_info->teacher_count ),
            'teacher_word' =>empty( $teacher_info->word_count ) ?  0 :intval( $teacher_info->word_count )
        ];
    }

    private function getQianCiInfo(
        $student_ids,$teacher_ids, $start_time, $end_time
    ){
        $teacher_str = implode(',',$teacher_ids );
        if (!empty($teacher_str )){

            $sql = <<<EOF
SELECT
	count(DISTINCT teacher_id) teacher_count, count(*)  book_count
FROM
	`qian_ci_book` 
WHERE
	`teacher_id` IN ( $teacher_str ) 
	AND `deleted_at` IS NULL 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
EOF;
            $teacher_info = \DB::select(\DB::raw($sql))[0];

        }else{
            $teacher_info = new \stdClass();
        }


        // 老师


        $total_student_count = 0;
        // 学生
        foreach ( array_chunk(  $student_ids , 10 ) as $student_ids_chunk){
            $student_str = implode( ',', $student_ids_chunk );
            $sql = <<<EOF
SELECT
	count(DISTINCT student_id) student_count 
FROM
	`qian_ci_student_record` 
WHERE
	`student_id` IN ($student_str)
AND `created_at` >= '$start_time' 
AND `created_at` <= '$end_time'
AND `deleted_at` IS NULL 
and id > 532473
EOF;

            $student_chunk_info = \DB::select(\DB::raw($sql))[0];
            $student_count = empty( $student_chunk_info->student_count ) ?  0 :intval( $student_chunk_info->student_count );
            $total_student_count += $student_count;
        }


        return  [
            'total_student_count' => $total_student_count,
            'teacher_count' => empty( $teacher_info->teacher_count ) ?  0 :intval( $teacher_info->teacher_count ),
            'teacher_book' =>empty( $teacher_info->book_count ) ?  0 :intval( $teacher_info->book_count )
        ];
    }



    private function getActivityInfo(
        $student_ids,$teacher_ids, $start_time, $end_time
    ){
        $teacher_str = implode(',',$teacher_ids );

        if (!empty($teacher_str )){

            $sql = <<<EOF
SELECT
	count(DISTINCT account_id) teacher_count, count(*)  activity_count
FROM
	`activity` 
WHERE
	`account_id` IN ( $teacher_str ) 
	AND `deleted_at` IS NULL 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
EOF;
            $teacher_info = \DB::select(\DB::raw($sql))[0];

        }else{
            $teacher_info = new \stdClass();
        }


        // 老师


        $total_student_count = 0;
        // 学生
        foreach ( array_chunk(  $student_ids , 10 ) as $student_ids_chunk){
            $student_str = implode( ',', $student_ids_chunk );
            $sql = <<<EOF
SELECT
	count(DISTINCT student_id) student_count 
FROM
	`activity_student_book_record` 
WHERE
	`student_id` IN ($student_str)
AND `created_at` >= '$start_time' 
AND `created_at` <= '$end_time'
and id > 15561163
EOF;

            $student_chunk_info = \DB::select(\DB::raw($sql))[0];
            $student_count = empty( $student_chunk_info->student_count ) ?  0 :intval( $student_chunk_info->student_count );
            $total_student_count += $student_count;
        }


        return  [
            'total_student_count' => $total_student_count,
            'teacher_count' => empty( $teacher_info->teacher_count ) ?  0 :intval( $teacher_info->teacher_count ),
            'activity_count' =>empty( $teacher_info->activity_count ) ?  0 :intval( $teacher_info->activity_count )
        ];
    }


    private function getSchoolLibraryStudent(
        $student_ids, $start_time, $end_time
    ){
        $total_student_count = 0;
        foreach ( array_chunk(  $student_ids , 10 ) as $student_ids_chunk){
            $student_str = implode( ',', $student_ids_chunk );
            $sql = <<<EOF
SELECT
	count(DISTINCT student_id) student_count 
FROM
	`library_student_book_record` 
WHERE
	`student_id` IN ($student_str)
AND `created_at` >= '$start_time' 
AND `created_at` <= '$end_time'
and id > 23802649
EOF;

            $student_chunk_info = \DB::select(\DB::raw($sql))[0];
            $student_count = empty( $student_chunk_info->student_count ) ?  0 :intval( $student_chunk_info->student_count );
            $total_student_count += $student_count;
        }

        return $total_student_count;

    }

    private function getSchoolTestbankInfo($school_id,  $start_time, $end_time){
        $sql = <<<EOF
SELECT
	type, count(*) count
FROM
	`school_testbank` 
WHERE
	`school_id` = '$school_id' 
	AND `created_at` >= '$start_time' 
	AND `created_at` <= '$end_time' 
	GROUP BY type
EOF;
        $school_testbank = \DB::select(\DB::raw($sql));

        if (empty( $school_testbank ))return '/';

        $school_testbank_arr = array_combine(
            array_column( $school_testbank,'type' ),
            array_column( $school_testbank, 'count')
        );

        return (isset($school_testbank_arr['bill'] ) ? $school_testbank_arr['bill'] : 0 ) .'/'.
         (isset($school_testbank_arr['exam'] ) ? $school_testbank_arr['exam'] : 0 ) .'/'.
         (isset($school_testbank_arr['quotedTestbank'] ) ? $school_testbank_arr['quotedTestbank'] : 0 ) ;

    }

    private function getSchoolAttribute(){
        $flag = \DB::select("SHOW VARIABLES LIKE '%group_concat_max_len%'");
        if (empty($flag ) || $flag[0]->Value < 102400 ) dd( 'group_concat_max_len' );


        $sql = <<<EOF
SELECT
 `key`, GROUP_CONCAT( school_id ) school_ids
FROM
	`school_attribute` 
WHERE `key` in (
'home_page_src', 'library_manager'
)
GROUP BY `key`
EOF;

        $school_attribute = \DB::select(\DB::raw($sql));

        return array_combine(
            array_column($school_attribute, 'key'),
            array_column( $school_attribute, 'school_ids')
        );
    }

    private function getSchoolPop(){
        $flag = \DB::select("SHOW VARIABLES LIKE '%group_concat_max_len%'");
        if (empty($flag ) || $flag[0]->Value < 102400 ) dd( 'group_concat_max_len' );


        $sql = <<<EOF
SELECT
 `key`, GROUP_CONCAT( school_id ) school_ids
FROM
	`school_popularize_data` 
WHERE `key` in (
'QRcode', 'official_website_status_temporary','official_website_status_finish'
)
and  `value` != ''
GROUP BY `key`
EOF;

        $school_popularize= \DB::select(\DB::raw($sql));

        return array_combine(
            array_column($school_popularize, 'key'),
            array_column( $school_popularize, 'school_ids')
        );

    }


    private function getRecruitVanclass(){
        $sql = <<<EOF
SELECT
	GROUP_CONCAT( DISTINCT school_id )  school_ids
FROM
	`vanclass` 
WHERE
	`type` = 'recruit' 
EOF;

        $school_recruit = \DB::select(\DB::raw($sql))[0];

        return explode(',', $school_recruit->school_ids);
    }

    private function getSchoolActivity(){
        $sql = <<<EOF
SELECT
	type,school_id, count(IF( deleted_at is null ,1 , NULL)) flag
FROM
	`school_activity` 
GROUP BY type,school_id
EOF;

        $school_activity = \DB::select(\DB::raw($sql));

        $return_data = [];

        foreach ($school_activity as  $item){
            $type = $item->type;
            $school_id = $item->school_id;
            $flag = $item->flag;
            if (!isset($return_data[$type])){
                $return_data[$type] = [];
            }
            $return_data[$type][$school_id] = $flag . '';
        }
        return $return_data;
    }

    private function getSchoolCourse(){
        $sql = <<<EOF
SELECT
	school_id, max(is_active) is_available
FROM
	`course_school_map`
	GROUP BY school_id
EOF;

        $school_course = \DB::select(\DB::raw($sql));

        return array_combine(
            array_column($school_course, 'school_id'  ),
            array_column($school_course, 'is_available'  )
        );
    }

    private function getGoodsInfo(){

        $sql = <<<EOF
SELECT
	school_id, max(is_available) is_available
FROM
	`goods` 
GROUP BY school_id
EOF;

        $school_goods = \DB::select(\DB::raw($sql));

        return array_combine(
            array_column($school_goods, 'school_id'  ),
            array_column($school_goods, 'is_available'  )
        );
    }


    private  function getSchoolLibraryStatus($school_id){
        $sql = <<<EOF
SELECT
	MAX(school_visible) school_visible
 FROM
	`library_books` 
WHERE
	`school_id` = '$school_id' 
	AND `deleted_at` IS NULL 
EOF;

        $school_library_status = \DB::select(\DB::raw($sql))[0];

        return is_null( $school_library_status->school_visible ) ? '-' : $school_library_status->school_visible;
    }

}