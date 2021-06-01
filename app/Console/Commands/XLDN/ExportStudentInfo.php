<?php

namespace App\Console\Commands\XLDN;

use App\Foundation\Excel;
use App\Helper\normdist;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ExportStudentInfo extends Command
{

    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:student:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出学生数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        config(['database.default' => 'kids_online_search']);

        $start_data = '2021-02-08';
        $end_data = '2021-02-14';


        $return = [];
        $return[] = [
            '区间',
            '学生总人数',
            '次数中位数',
            '0000-0060',
            '0060-0120',
            '0120-0180',
            '0180-0240',
            '0240-0300',
            '0300-0360',
            '0360-0420',
            '0420-0480',
            '0480-0540',
            '0540-0600',
            '0600-1200',
            '1200-2400',
            '2400-3600',
            '>3600'
        ];


        for ($i=0; $i<16; $i++ ){

            $start_data_time = Carbon::parse($start_data)->addWeeks($i)->startOfDay()->toDateTimeString();
            $end_data_time = Carbon::parse($end_data)->addWeeks($i)->endOfDay()->toDateTimeString();

            $start_data_tmp = Carbon::parse($start_data)->addWeeks($i)->toDateString();
            $end_data_tmp = Carbon::parse($end_data)->addWeeks($i)->toDateString();


            // 学生登入
            if (0){
                $return = [];
                $return[] = [
                    '区间',
                    '登入1天',
                    '登入2天',
                    '登入3天',
                    '登入4天',
                    '登入5天',
                    '登入6天',
                    '登入7天',
                ];
                $start_id = \DB::table('system_activity')
                    ->where('created_time', '>=',$start_data_time)
                    ->min('id');

                $sql = <<<EOF

select day_count, COUNT(DISTINCT account_id)  count from 
(
SELECT account_id, count(DISTINCT created_date) day_count from 
(
SELECT
	account_id, LEFT( created_time,10) created_date
FROM
	`kids`.`system_activity` 
WHERE
	`created_time` >= '$start_data_time' 
	AND `created_time` <= '$end_data_time' 
	AND `id` >= $start_id
	
	) tmp 
	GROUP BY account_id
	
	) tmp2 
	GROUP BY day_count
EOF;
                $record = \DB::select(\DB::raw($sql));

                $act_student = array_combine(
                    array_column($record , 'day_count'),
                    array_column( $record , 'count')
                );


                $total = $act_student[1]+$act_student[2]+$act_student[3]+$act_student[4]+$act_student[5]+$act_student[6]+$act_student[7];

                $return[] = [
                    $start_data_tmp.'~'.$end_data_tmp,
                    $act_student[1]/$total,
                    $act_student[2]/$total,
                    $act_student[3]/$total,
                    $act_student[4]/$total,
                    $act_student[5]/$total,
                    $act_student[6]/$total,
                    $act_student[7]/$total,
                ];
            }


            // 老师布置作业
            if (0){
                $return = [];
                $return[] = [
                    '区间',
                    '老师人数',
                    '次数中位数',
                    '1次',
                    '2次',
                    '3次',
                    '4次',
                    '5次',
                    '6次',
                    '7次',
                    '8次',
                    '9次',
                    '10次',
                    '11次',
                    '12次',
                    '13次',
                    '14次',
                    '15次',
                    '16次',
                    '17次',
                    '18次',
                    '19次',
                    '20次',
                    '21次',
                    '22次',
                    '23次',
                    '24次',
                    '25次',
                    '26次',
                    '27次',
                    '28次',
                    '29次',
                    '30次',
                    '31次',
                    '32次',
                    '33次',
                    '34次',
                    '35次',
                    '36次',
                    '37次',
                    '38次',
                    '39次',
                    '40次',
                    '41次',
                    '42次',
                    '43次',
                    '44次',
                    '45次',
                    '46次',
                    '47次',
                    '48次',
                    '49次',
                    '50次',
                ];
                $start_id = \DB::table('homework')
                    ->where('created_at', '>=',$start_data_time)
                    ->min('id');

                $sql = <<<EOF
SELECT homework_count, COUNT(*) teacher_count from 
(
SELECT
	teacher_id, COUNT( * ) homework_count 
FROM
	`kids`.`homework` 
WHERE
	`deleted_at` IS NULL 
	AND `created_at` >= '$start_data_time' 
	AND `created_at` <= '$end_data_time' 
	and  `id` >= $start_id
	GROUP BY teacher_id
	) tmp
	GROUP BY homework_count
	ORDER BY homework_count ASC
EOF;
                $record = \DB::select(\DB::raw($sql));

                $homework_teacher = array_combine(
                    array_column($record , 'homework_count'),
                    array_column( $record , 'teacher_count')
                );

                $homework_teacher_sum = array_sum(array_column( $record , 'teacher_count' ));

                $arr_tmp = [];
                foreach ($homework_teacher as $homework_count=>$teacher_count ){
                    for ($k=1;$k<=$teacher_count;$k++){
                        $arr_tmp[] = $homework_count;
                    }
                }

                if ( $homework_teacher_sum % 2 ){
                    $index = ($homework_teacher_sum+1)/2;
                    $mid = $arr_tmp[$index-1];
                }else{
                    $index_1 = ($homework_teacher_sum)/2;
                    $index_2 = ($homework_teacher_sum)/2  + 1;
                    $mid = ($arr_tmp[$index_1-1] + $arr_tmp[$index_2-1])/2;
                }



                $return[] = [
                    $start_data_tmp.'~'.$end_data_tmp,
                    $homework_teacher_sum,
                    $mid,
                    isset($homework_teacher[1]) ? $homework_teacher[1] : '0',
                    isset($homework_teacher[2]) ? $homework_teacher[2] : '0',
                    isset($homework_teacher[3]) ? $homework_teacher[3] : '0',
                    isset($homework_teacher[4]) ? $homework_teacher[4] : '0',
                    isset($homework_teacher[5]) ? $homework_teacher[5] : '0',
                    isset($homework_teacher[6]) ? $homework_teacher[6] : '0',
                    isset($homework_teacher[7]) ? $homework_teacher[7] : '0',
                    isset($homework_teacher[8]) ? $homework_teacher[8] : '0',
                    isset($homework_teacher[9]) ? $homework_teacher[9] : '0',
                    isset($homework_teacher[10]) ? $homework_teacher[10] : '0',
                    isset($homework_teacher[11]) ? $homework_teacher[11] : '0',
                    isset($homework_teacher[12]) ? $homework_teacher[12] : '0',
                    isset($homework_teacher[13]) ? $homework_teacher[13] : '0',
                    isset($homework_teacher[14]) ? $homework_teacher[14] : '0',
                    isset($homework_teacher[15]) ? $homework_teacher[15] : '0',
                    isset($homework_teacher[16]) ? $homework_teacher[16] : '0',
                    isset($homework_teacher[17]) ? $homework_teacher[17] : '0',
                    isset($homework_teacher[18]) ? $homework_teacher[18] : '0',
                    isset($homework_teacher[19]) ? $homework_teacher[19] : '0',
                    isset($homework_teacher[20]) ? $homework_teacher[20] : '0',
                    isset($homework_teacher[21]) ? $homework_teacher[21] : '0',
                    isset($homework_teacher[22]) ? $homework_teacher[22] : '0',
                    isset($homework_teacher[23]) ? $homework_teacher[23] : '0',
                    isset($homework_teacher[24]) ? $homework_teacher[24] : '0',
                    isset($homework_teacher[25]) ? $homework_teacher[25] : '0',
                    isset($homework_teacher[26]) ? $homework_teacher[26] : '0',
                    isset($homework_teacher[27]) ? $homework_teacher[27] : '0',
                    isset($homework_teacher[28]) ? $homework_teacher[28] : '0',
                    isset($homework_teacher[29]) ? $homework_teacher[29] : '0',
                    isset($homework_teacher[30]) ? $homework_teacher[30] : '0',
                    isset($homework_teacher[31]) ? $homework_teacher[31] : '0',
                    isset($homework_teacher[32]) ? $homework_teacher[32] : '0',
                    isset($homework_teacher[33]) ? $homework_teacher[33] : '0',
                    isset($homework_teacher[34]) ? $homework_teacher[34] : '0',
                    isset($homework_teacher[35]) ? $homework_teacher[35] : '0',
                    isset($homework_teacher[36]) ? $homework_teacher[36] : '0',
                    isset($homework_teacher[37]) ? $homework_teacher[37] : '0',
                    isset($homework_teacher[38]) ? $homework_teacher[38] : '0',
                    isset($homework_teacher[39]) ? $homework_teacher[39] : '0',
                    isset($homework_teacher[40]) ? $homework_teacher[40] : '0',
                    isset($homework_teacher[41]) ? $homework_teacher[41] : '0',
                    isset($homework_teacher[42]) ? $homework_teacher[42] : '0',
                    isset($homework_teacher[43]) ? $homework_teacher[43] : '0',
                    isset($homework_teacher[44]) ? $homework_teacher[44] : '0',
                    isset($homework_teacher[45]) ? $homework_teacher[45] : '0',
                    isset($homework_teacher[46]) ? $homework_teacher[46] : '0',
                    isset($homework_teacher[47]) ? $homework_teacher[47] : '0',
                    isset($homework_teacher[48]) ? $homework_teacher[48] : '0',
                    isset($homework_teacher[49]) ? $homework_teacher[49] : '0',
                    isset($homework_teacher[50]) ? $homework_teacher[50] : '0',
                ];
            }

            // 老师布置 打卡活动
            if (0){
                $return = [];
                $return[] = [
                    '区间',
                    '老师人数',
                    '次数中位数',
                    '1次',
                    '2次',
                    '3次',
                    '4次',
                    '5次',
                    '6次',
                    '7次',
                    '8次',
                    '9次',
                    '10次',
                    '11次',
                    '12次',
                    '13次',
                    '14次',
                    '15次',
                    '16次',
                    '17次',
                    '18次',
                    '19次',
                    '20次',
                    '21次',
                    '22次',
                    '23次',
                    '24次',
                    '25次',
                    '26次',
                    '27次',
                    '28次',
                    '29次',
                    '30次',
                    '31次',
                    '32次',
                    '33次',
                    '34次',
                    '35次',
                    '36次',
                    '37次',
                    '38次',
                    '39次',
                    '40次',
                    '41次',
                    '42次',
                    '43次',
                    '44次',
                    '45次',
                    '46次',
                    '47次',
                    '48次',
                    '49次',
                    '50次',
                ];
                $start_id = \DB::table('activity')
                    ->where('created_at', '>=',$start_data_time)
                    ->min('id');

                $sql = <<<EOF
SELECT homework_count, COUNT(*) teacher_count from 
(
SELECT
	account_id as teacher_id, COUNT( * ) homework_count 
FROM
	`kids`.`activity` 
WHERE
	`deleted_at` IS NULL 
	AND `created_at` >= '$start_data_time' 
	AND `created_at` <= '$end_data_time' 
	and  `id` >= $start_id
	GROUP BY account_id
	) tmp
	GROUP BY homework_count
	ORDER BY homework_count ASC
EOF;
                $record = \DB::select(\DB::raw($sql));

                $homework_teacher = array_combine(
                    array_column($record , 'homework_count'),
                    array_column( $record , 'teacher_count')
                );

                $homework_teacher_sum = array_sum(array_column( $record , 'teacher_count' ));

                $arr_tmp = [];
                foreach ($homework_teacher as $homework_count=>$teacher_count ){
                    for ($k=1;$k<=$teacher_count;$k++){
                        $arr_tmp[] = $homework_count;
                    }
                }

                if ( $homework_teacher_sum % 2 ){
                    $index = ($homework_teacher_sum+1)/2;
                    $mid = $arr_tmp[$index-1];
                }else{
                    $index_1 = ($homework_teacher_sum)/2;
                    $index_2 = ($homework_teacher_sum)/2  + 1;
                    $mid = ($arr_tmp[$index_1-1] + $arr_tmp[$index_2-1])/2;
                }



                $return[] = [
                    $start_data_tmp.'~'.$end_data_tmp,
                    $homework_teacher_sum,
                    $mid,
                    isset($homework_teacher[1]) ? $homework_teacher[1] : '0',
                    isset($homework_teacher[2]) ? $homework_teacher[2] : '0',
                    isset($homework_teacher[3]) ? $homework_teacher[3] : '0',
                    isset($homework_teacher[4]) ? $homework_teacher[4] : '0',
                    isset($homework_teacher[5]) ? $homework_teacher[5] : '0',
                    isset($homework_teacher[6]) ? $homework_teacher[6] : '0',
                    isset($homework_teacher[7]) ? $homework_teacher[7] : '0',
                    isset($homework_teacher[8]) ? $homework_teacher[8] : '0',
                    isset($homework_teacher[9]) ? $homework_teacher[9] : '0',
                    isset($homework_teacher[10]) ? $homework_teacher[10] : '0',
                    isset($homework_teacher[11]) ? $homework_teacher[11] : '0',
                    isset($homework_teacher[12]) ? $homework_teacher[12] : '0',
                    isset($homework_teacher[13]) ? $homework_teacher[13] : '0',
                    isset($homework_teacher[14]) ? $homework_teacher[14] : '0',
                    isset($homework_teacher[15]) ? $homework_teacher[15] : '0',
                    isset($homework_teacher[16]) ? $homework_teacher[16] : '0',
                    isset($homework_teacher[17]) ? $homework_teacher[17] : '0',
                    isset($homework_teacher[18]) ? $homework_teacher[18] : '0',
                    isset($homework_teacher[19]) ? $homework_teacher[19] : '0',
                    isset($homework_teacher[20]) ? $homework_teacher[20] : '0',
                    isset($homework_teacher[21]) ? $homework_teacher[21] : '0',
                    isset($homework_teacher[22]) ? $homework_teacher[22] : '0',
                    isset($homework_teacher[23]) ? $homework_teacher[23] : '0',
                    isset($homework_teacher[24]) ? $homework_teacher[24] : '0',
                    isset($homework_teacher[25]) ? $homework_teacher[25] : '0',
                    isset($homework_teacher[26]) ? $homework_teacher[26] : '0',
                    isset($homework_teacher[27]) ? $homework_teacher[27] : '0',
                    isset($homework_teacher[28]) ? $homework_teacher[28] : '0',
                    isset($homework_teacher[29]) ? $homework_teacher[29] : '0',
                    isset($homework_teacher[30]) ? $homework_teacher[30] : '0',
                    isset($homework_teacher[31]) ? $homework_teacher[31] : '0',
                    isset($homework_teacher[32]) ? $homework_teacher[32] : '0',
                    isset($homework_teacher[33]) ? $homework_teacher[33] : '0',
                    isset($homework_teacher[34]) ? $homework_teacher[34] : '0',
                    isset($homework_teacher[35]) ? $homework_teacher[35] : '0',
                    isset($homework_teacher[36]) ? $homework_teacher[36] : '0',
                    isset($homework_teacher[37]) ? $homework_teacher[37] : '0',
                    isset($homework_teacher[38]) ? $homework_teacher[38] : '0',
                    isset($homework_teacher[39]) ? $homework_teacher[39] : '0',
                    isset($homework_teacher[40]) ? $homework_teacher[40] : '0',
                    isset($homework_teacher[41]) ? $homework_teacher[41] : '0',
                    isset($homework_teacher[42]) ? $homework_teacher[42] : '0',
                    isset($homework_teacher[43]) ? $homework_teacher[43] : '0',
                    isset($homework_teacher[44]) ? $homework_teacher[44] : '0',
                    isset($homework_teacher[45]) ? $homework_teacher[45] : '0',
                    isset($homework_teacher[46]) ? $homework_teacher[46] : '0',
                    isset($homework_teacher[47]) ? $homework_teacher[47] : '0',
                    isset($homework_teacher[48]) ? $homework_teacher[48] : '0',
                    isset($homework_teacher[49]) ? $homework_teacher[49] : '0',
                    isset($homework_teacher[50]) ? $homework_teacher[50] : '0',
                ];
            }

            // 学生单词 乐园
            if (0){

                $start_id = \DB::table('self_study_student_record')
                    ->where('created_at', '>=',$start_data_time)
                    ->min('id');

                $sql = <<<EOF
SELECT self_count, COUNT(*) student_count from 
(
SELECT
	student_id, COUNT( DISTINCT  testbank_id) self_count 
FROM
	`kids`.`self_study_student_record` 
WHERE
	`deleted_at` IS NULL 
	AND `created_at` >= '$start_data_time' 
	AND `created_at` <= '$end_data_time' 
	and `id` >= $start_id
	GROUP BY student_id
	) tmp
	GROUP BY self_count
	ORDER BY self_count ASC
EOF;
                $record = \DB::select(\DB::raw($sql));

                $student_info = array_combine(
                    array_column($record , 'self_count'),
                    array_column( $record , 'student_count')
                );

                $student_sum = array_sum(array_column( $record , 'student_count' ));

                $arr_tmp = [];
                foreach ($student_info as $self_count=>$student_count ){
                    for ($k=1;$k<=$student_count;$k++){
                        $arr_tmp[] = $self_count;
                    }
                }

                if ( $student_sum % 2 ){
                    $index = ($student_sum+1)/2;
                    $mid = $arr_tmp[$index-1];
                }else{
                    $index_1 = ($student_sum)/2;
                    $index_2 = ($student_sum)/2  + 1;
                    $mid = ($arr_tmp[$index_1-1] + $arr_tmp[$index_2-1])/2;
                }

                $average = $this->getMu($arr_tmp);
                $sigma = $this->getSigma($arr_tmp);

                $return[] = [
                    $start_data_tmp.'~'.$end_data_tmp,
                    $student_sum,
                    $mid,
                    $average,
                    $sigma,
                    isset($student_info[1]) ? $student_info[1].'' : "0",
                    isset($student_info[2]) ? $student_info[2].'' : "0",
                    isset($student_info[3]) ? $student_info[3].'' : "0",
                    isset($student_info[4]) ? $student_info[4].'' : "0",
                    isset($student_info[5]) ? $student_info[5].'' : "0",
                    isset($student_info[6]) ? $student_info[6].'' : "0",
                    isset($student_info[7]) ? $student_info[7].'' : "0",
                    isset($student_info[8]) ? $student_info[8].'' : "0",
                    isset($student_info[9]) ? $student_info[9].'' : "0",
                    isset($student_info[10]) ? $student_info[10].'' : "0",
                    isset($student_info[11]) ? $student_info[11].'' : "0",
                    isset($student_info[12]) ? $student_info[12].'' : "0",
                    isset($student_info[13]) ? $student_info[13].'' : "0",
                    isset($student_info[14]) ? $student_info[14].'' : "0",
                    isset($student_info[15]) ? $student_info[15].'' : "0",
                    isset($student_info[16]) ? $student_info[16].'' : "0",
                    isset($student_info[17]) ? $student_info[17].'' : "0",
                    isset($student_info[18]) ? $student_info[18].'' : "0",
                    isset($student_info[19]) ? $student_info[19].'' : "0",
                    isset($student_info[20]) ? $student_info[20].'' : "0",
                    isset($student_info[21]) ? $student_info[21].'' : "0",
                    isset($student_info[22]) ? $student_info[22].'' : "0",
                    isset($student_info[23]) ? $student_info[23].'' : "0",
                    isset($student_info[24]) ? $student_info[24].'' : "0",
                    isset($student_info[25]) ? $student_info[25].'' : "0",
                    isset($student_info[26]) ? $student_info[26].'' : "0",
                    isset($student_info[27]) ? $student_info[27].'' : "0",
                    isset($student_info[28]) ? $student_info[28].'' : "0",
                    isset($student_info[29]) ? $student_info[29].'' : "0",
                    isset($student_info[30]) ? $student_info[30].'' : "0",
                    isset($student_info[31]) ? $student_info[31].'' : "0",
                    isset($student_info[32]) ? $student_info[32].'' : "0",
                    isset($student_info[33]) ? $student_info[33].'' : "0",
                    isset($student_info[34]) ? $student_info[34].'' : "0",
                    isset($student_info[35]) ? $student_info[35].'' : "0",
                    isset($student_info[36]) ? $student_info[36].'' : "0",
                    isset($student_info[37]) ? $student_info[37].'' : "0",
                    isset($student_info[38]) ? $student_info[38].'' : "0",
                    isset($student_info[39]) ? $student_info[39].'' : "0",
                    isset($student_info[40]) ? $student_info[40].'' : "0",
                    isset($student_info[41]) ? $student_info[41].'' : "0",
                    isset($student_info[42]) ? $student_info[42].'' : "0",
                    isset($student_info[43]) ? $student_info[43].'' : "0",
                    isset($student_info[44]) ? $student_info[44].'' : "0",
                    isset($student_info[45]) ? $student_info[45].'' : "0",
                    isset($student_info[46]) ? $student_info[46].'' : "0",
                    isset($student_info[47]) ? $student_info[47].'' : "0",
                    isset($student_info[48]) ? $student_info[48].'' : "0",
                    isset($student_info[49]) ? $student_info[49].'' : "0",
                    isset($student_info[50]) ? $student_info[50].'' : "0",
                    isset($student_info[51]) ? $student_info[51].'' : "0",
                    isset($student_info[52]) ? $student_info[52].'' : "0",
                    isset($student_info[53]) ? $student_info[53].'' : "0",
                    isset($student_info[54]) ? $student_info[54].'' : "0",
                    isset($student_info[55]) ? $student_info[55].'' : "0",
                    isset($student_info[56]) ? $student_info[56].'' : "0",
                    isset($student_info[57]) ? $student_info[57].'' : "0",
                    isset($student_info[58]) ? $student_info[58].'' : "0",
                    isset($student_info[59]) ? $student_info[59].'' : "0",
                    isset($student_info[60]) ? $student_info[60].'' : "0",
                    isset($student_info[61]) ? $student_info[61].'' : "0",
                    isset($student_info[62]) ? $student_info[62].'' : "0",
                    isset($student_info[63]) ? $student_info[63].'' : "0",
                    isset($student_info[64]) ? $student_info[64].'' : "0",
                    isset($student_info[65]) ? $student_info[65].'' : "0",
                    isset($student_info[66]) ? $student_info[66].'' : "0",
                    isset($student_info[67]) ? $student_info[67].'' : "0",
                    isset($student_info[68]) ? $student_info[68].'' : "0",
                    isset($student_info[69]) ? $student_info[69].'' : "0",
                    isset($student_info[70]) ? $student_info[70].'' : "0",
                    isset($student_info[71]) ? $student_info[71].'' : "0",
                    isset($student_info[72]) ? $student_info[72].'' : "0",
                    isset($student_info[73]) ? $student_info[73].'' : "0",
                    isset($student_info[74]) ? $student_info[74].'' : "0",
                    isset($student_info[75]) ? $student_info[75].'' : "0",
                    isset($student_info[76]) ? $student_info[76].'' : "0",
                    isset($student_info[77]) ? $student_info[77].'' : "0",
                    isset($student_info[78]) ? $student_info[78].'' : "0",
                    isset($student_info[79]) ? $student_info[79].'' : "0",
                    isset($student_info[80]) ? $student_info[80].'' : "0",
                    isset($student_info[81]) ? $student_info[81].'' : "0",
                    isset($student_info[82]) ? $student_info[82].'' : "0",
                    isset($student_info[83]) ? $student_info[83].'' : "0",
                    isset($student_info[84]) ? $student_info[84].'' : "0",
                    isset($student_info[85]) ? $student_info[85].'' : "0",
                    isset($student_info[86]) ? $student_info[86].'' : "0",
                    isset($student_info[87]) ? $student_info[87].'' : "0",
                    isset($student_info[88]) ? $student_info[88].'' : "0",
                    isset($student_info[89]) ? $student_info[89].'' : "0",
                    isset($student_info[90]) ? $student_info[90].'' : "0",
                    isset($student_info[91]) ? $student_info[91].'' : "0",
                    isset($student_info[92]) ? $student_info[92].'' : "0",
                    isset($student_info[93]) ? $student_info[93].'' : "0",
                    isset($student_info[94]) ? $student_info[94].'' : "0",
                    isset($student_info[95]) ? $student_info[95].'' : "0",
                    isset($student_info[96]) ? $student_info[96].'' : "0",
                    isset($student_info[97]) ? $student_info[97].'' : "0",
                    isset($student_info[98]) ? $student_info[98].'' : "0",
                    isset($student_info[99]) ? $student_info[99].'' : "0",
                    isset($student_info[100]) ? $student_info[100].'' : "0",
                ];
            }


            // 图书馆
            if (0){

                $start_id = \DB::table('library_student_book_record')
                    ->where('created_at', '>=',$start_data_time)
                    ->min('id');

                $sql = <<<EOF
SELECT library_count, COUNT(*) student_count from 
(
SELECT
	student_id, count(DISTINCT item_id) library_count
FROM
	`kids`.`library_student_book_record` 
WHERE
	 `created_at` >= '$start_data_time' 
	AND `created_at` <= '$end_data_time' 
	and  `id` >= $start_id
GROUP BY student_id
) tmp
	GROUP BY library_count
	ORDER BY library_count ASC
EOF;
                $record = \DB::select(\DB::raw($sql));

                $student_info = array_combine(
                    array_column($record , 'library_count'),
                    array_column( $record , 'student_count')
                );

                $student_sum = array_sum(array_column( $record , 'student_count' ));

                $up_100 = 0;
                $arr_tmp = [];
                foreach ($student_info as $library_count=>$student_count ){
                    for ($k=1;$k<=$student_count;$k++){
                        $arr_tmp[] = $library_count;
                    }
                    if ( $library_count > 100){
                        $up_100 += $student_count;
                    }
                }
                $this->info($student_sum);
                if ( $student_sum % 2 ){
                    $index = ($student_sum+1)/2;
                    $mid = $arr_tmp[$index-1];
                }else{
                    $index_1 = ($student_sum)/2;
                    $index_2 = ($student_sum)/2  + 1;
                    $mid = ($arr_tmp[$index_1-1] + $arr_tmp[$index_2-1])/2;
                }

                $average = $this->getMu($arr_tmp);
                $sigma = $this->getSigma($arr_tmp);

                $return[] = [
                    $start_data_tmp.'~'.$end_data_tmp,
                    $student_sum,
                    $mid,
                    $average,
                    $sigma,
                    isset($student_info[1]) ? $student_info[1].'' : "0",
                    isset($student_info[2]) ? $student_info[2].'' : "0",
                    isset($student_info[3]) ? $student_info[3].'' : "0",
                    isset($student_info[4]) ? $student_info[4].'' : "0",
                    isset($student_info[5]) ? $student_info[5].'' : "0",
                    isset($student_info[6]) ? $student_info[6].'' : "0",
                    isset($student_info[7]) ? $student_info[7].'' : "0",
                    isset($student_info[8]) ? $student_info[8].'' : "0",
                    isset($student_info[9]) ? $student_info[9].'' : "0",
                    isset($student_info[10]) ? $student_info[10].'' : "0",
                    isset($student_info[11]) ? $student_info[11].'' : "0",
                    isset($student_info[12]) ? $student_info[12].'' : "0",
                    isset($student_info[13]) ? $student_info[13].'' : "0",
                    isset($student_info[14]) ? $student_info[14].'' : "0",
                    isset($student_info[15]) ? $student_info[15].'' : "0",
                    isset($student_info[16]) ? $student_info[16].'' : "0",
                    isset($student_info[17]) ? $student_info[17].'' : "0",
                    isset($student_info[18]) ? $student_info[18].'' : "0",
                    isset($student_info[19]) ? $student_info[19].'' : "0",
                    isset($student_info[20]) ? $student_info[20].'' : "0",
                    isset($student_info[21]) ? $student_info[21].'' : "0",
                    isset($student_info[22]) ? $student_info[22].'' : "0",
                    isset($student_info[23]) ? $student_info[23].'' : "0",
                    isset($student_info[24]) ? $student_info[24].'' : "0",
                    isset($student_info[25]) ? $student_info[25].'' : "0",
                    isset($student_info[26]) ? $student_info[26].'' : "0",
                    isset($student_info[27]) ? $student_info[27].'' : "0",
                    isset($student_info[28]) ? $student_info[28].'' : "0",
                    isset($student_info[29]) ? $student_info[29].'' : "0",
                    isset($student_info[30]) ? $student_info[30].'' : "0",
                    isset($student_info[31]) ? $student_info[31].'' : "0",
                    isset($student_info[32]) ? $student_info[32].'' : "0",
                    isset($student_info[33]) ? $student_info[33].'' : "0",
                    isset($student_info[34]) ? $student_info[34].'' : "0",
                    isset($student_info[35]) ? $student_info[35].'' : "0",
                    isset($student_info[36]) ? $student_info[36].'' : "0",
                    isset($student_info[37]) ? $student_info[37].'' : "0",
                    isset($student_info[38]) ? $student_info[38].'' : "0",
                    isset($student_info[39]) ? $student_info[39].'' : "0",
                    isset($student_info[40]) ? $student_info[40].'' : "0",
                    isset($student_info[41]) ? $student_info[41].'' : "0",
                    isset($student_info[42]) ? $student_info[42].'' : "0",
                    isset($student_info[43]) ? $student_info[43].'' : "0",
                    isset($student_info[44]) ? $student_info[44].'' : "0",
                    isset($student_info[45]) ? $student_info[45].'' : "0",
                    isset($student_info[46]) ? $student_info[46].'' : "0",
                    isset($student_info[47]) ? $student_info[47].'' : "0",
                    isset($student_info[48]) ? $student_info[48].'' : "0",
                    isset($student_info[49]) ? $student_info[49].'' : "0",
                    isset($student_info[50]) ? $student_info[50].'' : "0",
                    isset($student_info[51]) ? $student_info[51].'' : "0",
                    isset($student_info[52]) ? $student_info[52].'' : "0",
                    isset($student_info[53]) ? $student_info[53].'' : "0",
                    isset($student_info[54]) ? $student_info[54].'' : "0",
                    isset($student_info[55]) ? $student_info[55].'' : "0",
                    isset($student_info[56]) ? $student_info[56].'' : "0",
                    isset($student_info[57]) ? $student_info[57].'' : "0",
                    isset($student_info[58]) ? $student_info[58].'' : "0",
                    isset($student_info[59]) ? $student_info[59].'' : "0",
                    isset($student_info[60]) ? $student_info[60].'' : "0",
                    isset($student_info[61]) ? $student_info[61].'' : "0",
                    isset($student_info[62]) ? $student_info[62].'' : "0",
                    isset($student_info[63]) ? $student_info[63].'' : "0",
                    isset($student_info[64]) ? $student_info[64].'' : "0",
                    isset($student_info[65]) ? $student_info[65].'' : "0",
                    isset($student_info[66]) ? $student_info[66].'' : "0",
                    isset($student_info[67]) ? $student_info[67].'' : "0",
                    isset($student_info[68]) ? $student_info[68].'' : "0",
                    isset($student_info[69]) ? $student_info[69].'' : "0",
                    isset($student_info[70]) ? $student_info[70].'' : "0",
                    isset($student_info[71]) ? $student_info[71].'' : "0",
                    isset($student_info[72]) ? $student_info[72].'' : "0",
                    isset($student_info[73]) ? $student_info[73].'' : "0",
                    isset($student_info[74]) ? $student_info[74].'' : "0",
                    isset($student_info[75]) ? $student_info[75].'' : "0",
                    isset($student_info[76]) ? $student_info[76].'' : "0",
                    isset($student_info[77]) ? $student_info[77].'' : "0",
                    isset($student_info[78]) ? $student_info[78].'' : "0",
                    isset($student_info[79]) ? $student_info[79].'' : "0",
                    isset($student_info[80]) ? $student_info[80].'' : "0",
                    isset($student_info[81]) ? $student_info[81].'' : "0",
                    isset($student_info[82]) ? $student_info[82].'' : "0",
                    isset($student_info[83]) ? $student_info[83].'' : "0",
                    isset($student_info[84]) ? $student_info[84].'' : "0",
                    isset($student_info[85]) ? $student_info[85].'' : "0",
                    isset($student_info[86]) ? $student_info[86].'' : "0",
                    isset($student_info[87]) ? $student_info[87].'' : "0",
                    isset($student_info[88]) ? $student_info[88].'' : "0",
                    isset($student_info[89]) ? $student_info[89].'' : "0",
                    isset($student_info[90]) ? $student_info[90].'' : "0",
                    isset($student_info[91]) ? $student_info[91].'' : "0",
                    isset($student_info[92]) ? $student_info[92].'' : "0",
                    isset($student_info[93]) ? $student_info[93].'' : "0",
                    isset($student_info[94]) ? $student_info[94].'' : "0",
                    isset($student_info[95]) ? $student_info[95].'' : "0",
                    isset($student_info[96]) ? $student_info[96].'' : "0",
                    isset($student_info[97]) ? $student_info[97].'' : "0",
                    isset($student_info[98]) ? $student_info[98].'' : "0",
                    isset($student_info[99]) ? $student_info[99].'' : "0",
                    isset($student_info[100]) ? $student_info[100].'' : "0",
                    $up_100
                ];
            }


            // 磨耳朵
            if (1){

                $start_id = \DB::table('record_student_entity_spend_time')
                    ->where('created_at', '>=',$start_data_time)
                    ->min('id');

                $sql = <<<EOF
SELECT ELT(INTERVAL(spend_time,0,60,120,180,240,300,360,420,480,540,600,1200,2400,3600),'0000-0060','0060-0120','0120-0180','0180-0240','0240-0300','0300-0360','0360-0420','0420-0480','0480-0540','0540-0600','0600-1200','1200-2400','2400-3600','>3600') AS spend_time_range , sum(student_count) as student_sum from 
(
SELECT spend_time, COUNT(student_id) student_count from 
(
SELECT
	student_id, SUM(spend_time) spend_time
FROM
	`kids`.`record_student_entity_spend_time` 
WHERE
	 `created_at` >= '$start_data_time' 
	AND `created_at` <= '$end_data_time' 
	and  `id` >= $start_id
GROUP BY student_id
) tmp GROUP BY spend_time
) ttmp
GROUP BY ELT(INTERVAL(spend_time,0,60,120,180,240,300,360,420,480,540,600,1200,2400,3600),'0000-0060','0060-0120','0120-0180','0180-0240','0240-0300','0300-0360','0360-0420','0420-0480','0480-0540','0540-0600','0600-1200','1200-2400','2400-3600','>3600')
EOF;
                $record = \DB::select(\DB::raw($sql));

                $student_info = array_combine(
                    array_column($record , 'spend_time_range'),
                    array_column( $record , 'student_sum')
                );

                $student_sum = array_sum(array_column( $record , 'student_sum' ));

                $arr_tmp = [];
                foreach ($student_info as $spend_time_range=>$student_count ){
                    for ($k=1;$k<=$student_count;$k++){
                        $arr_tmp[] = $spend_time_range;
                    }
                }
                $this->info($student_sum);
                if ( $student_sum % 2 ){
                    $index = ($student_sum+1)/2;
                    $mid = $arr_tmp[$index-1];
                }else{
                    $index_1 = ($student_sum)/2;
                    $mid = $arr_tmp[$index_1] ;
                }


                $return[] = [
                    $start_data_tmp.'~'.$end_data_tmp,
                    $student_sum,
                    $mid,
                    $student_info['0000-0060'],
                    $student_info['0060-0120'],
                    $student_info['0120-0180'],
                    $student_info['0180-0240'],
                    $student_info['0240-0300'],
                    $student_info['0300-0360'],
                    $student_info['0360-0420'],
                    $student_info['0420-0480'],
                    $student_info['0480-0540'],
                    $student_info['0540-0600'],
                    $student_info['0600-1200'],
                    $student_info['1200-2400'],
                    $student_info['2400-3600'],
                    $student_info['>3600']
                ];
            }


            $this->info(
                $start_data_time.'~~~'.$end_data_time
            );
        }


        $this->store('磨耳朵', $return);
        dd('done');


    }


    // 平均值
    public function getMu($list){
        return array_sum($list)/count($list);
    }

    // 标准偏差
    public function getSigma($list){
        $total_var = 0;
        foreach ($list as $v){
            $total_var += pow( ($v - $this->getMu($list)), 2 );
        }
        return sqrt( $total_var / (count($list) - 1 ) );
    }
}
