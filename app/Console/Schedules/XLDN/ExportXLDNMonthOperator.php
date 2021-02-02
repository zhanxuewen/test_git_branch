<?php

namespace App\Console\Schedules\XLDN;


use App\Console\Schedules\BaseSchedule;


class ExportXLDNMonthOperator extends BaseSchedule
{

    public function handle()
    {
        ini_set('memory_limit', '2048M');
        config(['database.default' => 'kids_online']);

        $date_type = 'M2021-01';

        $rep = [];
        $rep[] = [
            '学校ID',
            '学校名称',
            '在线助教ID',
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
            '月活跃学生',

            '提分版学生',
            '仅试用学生',

            'E卡个数',
            '开卡个数',

            '图书馆学习人数（月）',
            '做作业人数（月）',
            '磨耳朵学习人数（月）',
            '单词乐园学习人数（月）',
            '打卡活动学习人数（月）',
            '听力练习学习人数（月）',


            '礼品中心是否上架',

        ];

        $school_info = $this->getSchoolInfo($date_type);
        echo 'school_info----';
        $school_goods = $this->getGoodsInfo();
        echo 'school_goods----';


        // 拼接数据
        foreach ($school_info as $school_id => $school_info_item) {
            echo $school_id;
            $region = $school_info[$school_id]['school_region'];
            $region_arr = explode('/', $region);

            // 获得 学生 活跃信息
            $student_info = $this->getSchoolStudentInfo($date_type, $school_id  );

            $rep[] = [
                $school_id,
                $school_info[$school_id]['school_name'],
                $school_info[$school_id]['core_school_id'],
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


                $school_info[$school_id]['teacher_total'] . '',
                $school_info[$school_id]['act_teacher'] . '',

                $school_info[$school_id]['student_total'] . '',
                $school_info[$school_id]['act_student'] . '',

                $school_info[$school_id]['vip_student'] . '',
                $school_info[$school_id]['try_student'] . '',


                $school_info[$school_id]['card_amount'] . '',
                $school_info[$school_id]['card_student_count'] . '',



                isset($student_info['library']) ?$student_info['library']. '' : '0',
                isset($student_info['homework']) ?$student_info['homework']. '' : '0',
                isset($student_info['practice']) ?$student_info['practice']. '' : '0',
                isset($student_info['self_study']) ?$student_info['self_study']. '' : '0',
                isset($student_info['activity']) ?$student_info['activity'] . '': '0',
                isset($student_info['listening']) ?$student_info['listening'] . '': '0',

                isset($school_goods[$school_id]) ? $school_goods[$school_id]. '' : '-',

            ];

            echo '======';
        }

        $this->store('小龙迪诺月数据_' . rand(0, 100), $rep, '.xlsx');

        dd('done....');

    }


    private function getSchoolInfo($date_type)
    {

        $sql = <<<EOF
SELECT
       statistic_school_record_monthly.school_id,
       school.`name` school_name,
       school.`core_school_id` core_school_id,
       school.`created_at` school_created_at,
       statistic_school_record_monthly.school_region,
       principal.nickname principal_name,
       statistic_school_record_monthly.principal_phone,
       marketer.nickname marketer_name,
       operator.nickname operator_name,
       statistic_school_record_monthly.contract_class,
       statistic_school_record_monthly.sign_contract_date,
       statistic_school_record_monthly.teacher_total,
       statistic_school_record_monthly.act_teacher,
       statistic_school_record_monthly.student_total,
       statistic_school_record_monthly.act_student,
       statistic_school_record_monthly.vip_student,
       statistic_school_record_monthly.try_student,
       statistic_school_record_monthly.extra->'$.card_amount' as card_amount,
	   statistic_school_record_monthly.extra->'$.card_student_count' as card_student_count
FROM
      `statistic_school_record_monthly` 
left  join  school  on school.id = statistic_school_record_monthly.school_id  
left  join user_account  principal on principal.id = statistic_school_record_monthly.principal_id
left  join user_account marketer on marketer.id = statistic_school_record_monthly.marketer_id
left  join user_account operator on operator.id = statistic_school_record_monthly.afterSales_id
      
WHERE
      `contract_class` <> 'N' 
      AND `date_type` = '$date_type' 
			and  school.deleted_at is NULL
EOF;
        $school_info = \DB::select(\DB::raw($sql));
        $school_info = json_decode(json_encode($school_info), true);
        echo '+';
        return collect($school_info)->keyBy('school_id')->toArray();

    }

    private function getGoodsInfo()
    {

        $sql = <<<EOF
SELECT
	school_id, max(is_available) is_available
FROM
	`goods` 
where deleted_at is null 	
GROUP BY school_id
EOF;

        $school_goods = \DB::select(\DB::raw($sql));

        return array_combine(
            array_column($school_goods, 'school_id'),
            array_column($school_goods, 'is_available')
        );
    }

    private function getSchoolStudentInfo($date_type, $school_id ){
        $sql = <<<EOF
SELECT
      count(IF( activity->'$.spend_time' > 0 ,1,null) ) activity, 
      count(IF( homework->'$.spend_time' > 0 ,1,null) ) homework, 
      count(IF( library->'$.spend_time' > 0 ,1,null) ) library, 
      count(IF( practice->'$.spend_time' > 0 ,1,null) ) practice, 
      count(IF( self_study->'$.spend_time' > 0 ,1,null) ) self_study, 
      count(IF( listening->'$.total_time' > 0 ,1,null) ) listening
FROM
      `kids`.`statistic_student_data_monthly` 
WHERE
      `date_type` = '$date_type' 
      AND `school_id` = $school_id    
EOF;
        $student_info = \DB::select(\DB::raw($sql));
        $student_info = json_decode(json_encode($student_info), true);

        return array_pop($student_info);
    }


}