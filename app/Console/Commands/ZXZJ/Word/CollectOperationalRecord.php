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

class CollectOperationalRecord extends Command
{

    use Excel;

    private $ignore_ids = 81;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:operation:record';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导出百项过运营记录';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function getSchoolLearnInfo($start_day, $date_total)
    {



        $school_card_use_info = [];
        // 书 与 卡 的关系
        $sql = <<<EOF
SELECT
	student_id,book_id,GROUP_CONCAT(DISTINCT card_id) card_ids, count(DISTINCT card_id) count
FROM
	`learning`.`course_user_book_record` 
GROUP BY student_id,book_id
EOF;

        $student_book_card_relation = \DB::select(\DB::raw($sql));

        $student_book_card_relation = collect($student_book_card_relation)
            ->groupBy('student_id')
            ->map(function ($student){
                $return_data = [];

                foreach ($student as $value){
                    $book_id = $value->book_id;
                    $card_ids = $value->card_ids;
                    $count = $value->count;

                    $return_data[$book_id] = [
                        'card_ids' => $card_ids,
                        'count'    => $count
                    ];
                }

                return $return_data;
            })
            ->toArray();



        for ($i=0;$i<$date_total;$i++){
            $record_date = Carbon::parse($start_day)->addDays($i)->toDateString();
            $student_records = \DB::table('statistic_student_record')
                ->selectRaw('student_id, school_id,value as books')
                ->where('created_date', $record_date)
                ->get();
            foreach ($student_records as $student_record){
                $school_id  = $student_record->school_id;
                $student_id  = $student_record->student_id;
                $books  = $student_record->books;
                $book_ids = array_keys(json_decode($books, true));
                foreach ($book_ids as $book_id){
                    if (!isset($school_card_use_info[$school_id])){
                        $school_card_use_info[$school_id] = [];
                    }

                    // 获得 这本书 的卡信息
                    $card_info = $student_book_card_relation[$student_id][$book_id];
                    $card_num = $card_info['count'];
                    $card_ids = $card_info['card_ids'];

                    if ($card_num == 1){
                        $card_tmp = \DB::table('card')
                            ->selectRaw('prototype_id')
                            ->where('id', $card_ids)
                            ->first();
                        $card = $card_tmp->prototype_id;
                        if (!isset($school_card_use_info[$school_id][$card])){
                            $school_card_use_info[$school_id][$card] = [];
                        }
                        $school_card_use_info[$school_id][$card][] = $student_id;
                    }else{
                        // 绑着多张卡
                        $card_tmp = \DB::table('card')
                            ->whereIn('id', explode(',', $card_ids))
                            ->where('created_at','<=', $record_date.' 23:59:59')
                            ->get();
                        foreach ($card_tmp as $card_item){
                            if (
                                $card_item->activated_at <= $record_date &&
                                $card_item->expired_at >= $record_date
                            ){
                                $card = $card_item->prototype_id;

                                if (!isset($school_card_use_info[$school_id][$card])){
                                    $school_card_use_info[$school_id][$card] = [];
                                }
                                $school_card_use_info[$school_id][$card][] = $student_id;
                                break;
                            }
                        }
                    }
                }
                usleep(200);
                echo '=';
            }
            echo '>'.$record_date;
            sleep(1);

        }
        return $school_card_use_info;

    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $tmp = new ExportBookLearningProcess();

        $tmp->handle();

        dd('done');
/**



        $message = "打包提醒: 学校信息：1111:school_name, 软件名称: XXXX, icon： www.baidu.com, logo: www.example.com";
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=a8190218fd6c32c141ee72bf3ca707fb3c6a514f56ab580318ce359674bf9904';
        $data = json_encode(['msgtype' => 'text', 'text' => ['content' => $message]]);
        Curl::curlPost($url, $data);

        dd('done');



        $tmp = new SchoolController();

        $tmp->postExport1();

        dd('done');

        config(['database.default' => 'BXG_online']);

        $date_type = 'M2020-02';

        $start_time_str = '2020-02-01 00:00:00';
        $end_time_str = '2020-02-29 23:59:59';

        $start_date = '2020-02-01 00:00:00';
        $end_date = '2020-02-29 23:59:59';

        $start_day = '2020-02-01';
        $end_day = '2020-02-29';
        $day_count = 29;


//        $date_type = 'M2020-01';
//
//        $start_time_str = '2020-01-01 00:00:00';
//        $end_time_str = '2020-01-31 23:59:59';
//
//        $start_date = '2020-01-01 00:00:00';
//        $end_date = '2020-01-31 23:59:59';


        $month_11_start_str = $start_time_str;
        $month_11_end_str = $end_time_str;

        $rep = [];
        $rep[] = [
            1=>'学校ID',
            2=>'学校名称',
            3=>'省',
            4=>'地市',
            5=>'区县',
            6=>'计数',
            7=>'合作档位',
            8=>'签约日期',
            9=>'推广代表',
            10=>'运营代表',
            11=>'本月结算总额',
            12=>'本月减免额',
            13=>'有效结算总额',
            14=>'本月学习人数',
            15=>'1A单词卡',
            16=>'',
            17=>'',
            18=>'1B单词1月引流卡',
            19=>'',
            20=>'',
            21=>'1C单词3天引流卡',
            22=>'',
            23=>'',
            24=>'1D单词速记卡',
            25=>'',
            26=>'',
            27=>'2神奇拼读卡',
            28=>'',
            29=>'',
            30=>'3音标速拼卡',
            31=>'',
            32=>'',
            33=>'4A课文卡',
            34=>'',
            35=>'',
            36=>'4B课文引流卡',
            37=>'',
            38=>'',
            39=>'5口语卡',
            40=>'',
            41=>'',
            42=>'6语法卡',
            43=>'',
            44=>'',
            45=>'7A英语同步卡',
            46=>'',
            47=>'',
            48=>'7B英语同步引流卡',
            49=>'',
            50=>'',
            51=>'8A英语中考卡',
            52=>'',
            53=>'',
            54=>'8B英语中考引流卡',
            55=>'',
            56=>'',
            57=>'9A史地生会考卡',
            58=>'',
            59=>'',
            60=>'9B会考引流卡',
            61=>'',
            62=>'',
            63=>'10A史地生同步卡',
            64=>'',
            65=>'',
            66=>'10B同步1月引流卡',
            67=>'',
            68=>'',
            69=>'10C同步3天引流卡',
            70=>'',
            71=>'',
        ];
        $rep[] = [
            1=>'',
            2=>'',
            3=>'',
            4=>'',
            5=>'',
            6=>'',
            7=>'',
            8=>'',
            9=>'',
            10=>'',
            11=>'',
            12=>'',
            13=>'',
            14=>'',
            15=>'本月开卡量',
            16=>'结算价',
            17=>'学习人数',
            18=>'本月开卡量',
            19=>'结算价',
            20=>'学习人数',
            21=>'本月开卡量',
            22=>'结算价',
            23=>'学习人数',
            24=>'本月开卡量(年卡)',
            25=>'本月开卡量(半年卡)',
            26=>'学习人数',
            27=>'本月开卡量',
            28=>'结算价',
            29=>'学习人数',
            30=>'本月开卡量',
            31=>'结算价',
            32=>'学习人数',
            33=>'本月开卡量',
            34=>'结算价',
            35=>'学习人数',
            36=>'本月开卡量',
            37=>'结算价',
            38=>'学习人数',
            39=>'本月开卡量',
            40=>'结算价',
            41=>'学习人数',
            42=>'本月开卡量',
            43=>'结算价',
            44=>'学习人数',
            45=>'本月开卡量',
            46=>'结算价',
            47=>'学习人数',
            48=>'本月开卡量',
            49=>'结算价',
            50=>'学习人数',
            51=>'本月开卡量',
            52=>'结算价',
            53=>'学习人数',
            54=>'本月开卡量',
            55=>'结算价',
            56=>'学习人数',
            57=>'本月开卡量',
            58=>'结算价',
            59=>'学习人数',
            60=>'本月开卡量',
            61=>'结算价',
            62=>'学习人数',
            63=>'本月开卡量',
            64=>'结算价',
            65=>'学习人数',
            66=>'本月开卡量',
            67=>'结算价',
            68=>'学习人数',
            69=>'本月开卡量',
            70=>'结算价',
            71=>'学习人数',
        ];



        // 获得 学生练习 课程
//        $school_card_use_info = $this->getSchoolLearnInfo($start_day, $day_count);
        $school_card_use_info = [];




// 学校基本信息
        $sql = <<<EOF
SELECT
	school.id, school.name, school_attribute.value region,mark.name  marketer_name ,operator_user.name oper_name
FROM
	school
	
	left join school_attribute on school.id = school_attribute.school_id and school_attribute.`key` = 'region'
	
	left join school_attribute  marketer on school.id = marketer.school_id and marketer.`key` = 'marketer_id'
	left join user mark on mark.id = marketer.`value` 
	
	left join school_attribute  operator on school.id = operator.school_id and operator.`key` = 'operator_id'
	left join user operator_user on operator_user.id = operator.`value` 
	
EOF;
        $school_baseinfo = \DB::select(\DB::raw($sql));

        $school_baseinfo = json_decode(json_encode($school_baseinfo),true);

        $school_info = collect($school_baseinfo)->keyBy('id')->toArray();


        // 获得 学习卡 的 价格
        $card_fee = \DB::table('card_prototype')
            ->selectRaw('id, pay_fee')
//            ->where('is_available', 1)
            ->get();

        $card_fee = $card_fee->pluck('pay_fee','id')->toArray();

        $school_fee = \DB::table('card_prototype_school_customization')
            ->selectRaw('school_id, prototype_id ,value pay_fee ')
            ->where('key', 'card_price')
            ->get()
            ->groupBy('school_id')->map(function ($school){
                return $school->pluck('pay_fee', 'prototype_id')->toArray();
            })
            ->toArray();

// 学校开卡记录
//         $school_cards = \DB::table('statistic_school_record_monthly')
//             ->selectRaw('school_id, school_cards')
//             ->where('date_type', $date_type )
//             ->get()->keyBy('school_id')
//             ->map(function ($school){
//                 return  json_decode($school->school_cards, true);
//             })
//             ->toArray();
        $sql = <<<EOF
SELECT
	school_id, prototype_id ,count(*) total
FROM
	`learning`.`card` 
WHERE
	`school_id` IN (
	2020,
	2024,
	2030,
	2031,
	2032,
	2034,
	2035,
	2038,
	2040,
	2042,
	2050,
	2083,
	20,
	2167,
	2191,
	2239,
	2243,
	2684 
	) 
	AND `created_at` >= '2019-12-16 00:00:10' 
	AND `created_at` <= '2020-02-29 23:59:59'
	GROUP BY school_id, prototype_id
	ORDER BY school_id asc ,prototype_id asc
EOF;
        $school_cards_tmp = \DB::select(\DB::raw($sql));

        $school_cards_tmp = json_decode(json_encode($school_cards_tmp),true);

        $school_cards = collect($school_cards_tmp)->groupBy('school_id')->map(
            function ($school){

                return ($school->pluck('total', 'prototype_id')->toArray());

            }
        )->toArray();



        // 学校学习人数
        $school_learn = \DB::table('statistic_school_record_monthly')
            ->selectRaw('school_id, learn_student')
            ->where('date_type', $date_type )
            ->get()->keyBy('school_id')
            ->pluck('learn_student','school_id')
            ->toArray();



         // 单词速记那边的开卡记录

        config(['database.default' => 'DCSJ_online']);

        // 2 月数据  年卡
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$month_11_start_str)
            ->get();
        $student_ids = [];
        foreach ($student_info as $item){
            $student_ids[] = $item->user_id;
        }

        $student_ids_str = implode(',', $student_ids);


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness in (300,350,365)
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 300 
	    AND rec.created_at <= '$month_11_end_str'
	    AND rec.created_at >= '$month_11_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $year_last_month = \DB::select(\DB::raw($sql));
        $year_last_month = json_decode(json_encode($year_last_month),true);

        $year_last_month = collect($year_last_month)->pluck('num_count', 'relation_id')->toArray();


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness in (183,182,180)
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
          AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300 
	    AND rec.created_at <= '$month_11_end_str'
	    AND rec.created_at >= '$month_11_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $half_year_last_month = \DB::select(\DB::raw($sql));
        $half_year_last_month = json_decode(json_encode($half_year_last_month),true);

        $half_year_last_month = collect($half_year_last_month)->pluck('num_count', 'relation_id')->toArray();

// 学习人数
        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region
FROM
	school
	LEFT JOIN users ON users.school_id = school.id
	    AND user_type_id = 2 
	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
        AND rec.key = 'word_cout'
	    AND rec.created_at <= '$end_date'
	    AND rec.created_at >= '$start_date'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id
	    AND relation.`key` = 'relation_id'
WHERE
	school.id NOT IN ( 1, 5 )
GROUP BY
	school.id
HAVING
	num_count > 0
order by
    relation_id+0 asc
EOF;
        $learn_info = \DB::select(\DB::raw($sql));
        $learn_info = json_decode(json_encode($learn_info),true);

        $DCSJ_learn_info = collect($learn_info)->pluck('num_count', 'relation_id')->toArray();


        // 拼接数据
        foreach ($school_cards as $school_id=>$card_arr){

            $region = $school_info[$school_id]['region'];
            $region_arr = explode('/', $region);
            config(['database.default' => 'BXG_online']);
            $school_card_use_true = [];
            // 计算一个学校的
//            if (isset($school_card_use_info[$school_id])){
//                foreach ($school_card_use_info[$school_id] as $card=>$student_ids){
//                    $school_card_use_true[$card] = count(array_unique($student_ids));
//
//                }
//            }


            $rep[] = [
                1=>$school_id,
                2=>$school_info[$school_id]['name'],
                3=>$region_arr[0],
                4=>$region_arr[1],
                5=>isset($region_arr[2]) ? $region_arr[2] : '',
                6=>1,
                7=>'',
                8=>'',
                9=>$school_info[$school_id]['marketer_name'],
                10=>$school_info[$school_id]['oper_name'],
                11=>'',
                12=>'',
                13=>'',
                14=>$school_learn[$school_id] ? $school_learn[$school_id] : '0',
                15=>isset($school_cards[$school_id][39]) ? $school_cards[$school_id][39] : '0',
                16=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][39]) ? $school_fee[$school_id][39] : $card_fee[39],
                17=>isset($school_card_use_true[39])?$school_card_use_true[39] : '0',

                18=>isset($school_cards[$school_id][40]) ? $school_cards[$school_id][40] : '0',
                19=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][40]) ? $school_fee[$school_id][40] : $card_fee[40],
                20=>isset($school_card_use_true[40])?$school_card_use_true[40] : '0',

                21=>isset($school_cards[$school_id][53]) ? $school_cards[$school_id][53] : '0',
                22=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][53]) ? $school_fee[$school_id][53] : $card_fee[53],
                23=>isset($school_card_use_true[53])?$school_card_use_true[53] : '0',


                24=>isset($year_last_month[$school_id]) ? $year_last_month[$school_id] : '0',
                25=>isset($half_year_last_month[$school_id]) ? $half_year_last_month[$school_id] : '0',
                26=>isset($DCSJ_learn_info[$school_id]) ? $DCSJ_learn_info[$school_id] : '0',

                27=>isset($school_cards[$school_id][50]) ? $school_cards[$school_id][50] : '0',
                28=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][50]) ? $school_fee[$school_id][50] : $card_fee[50],
                29=>isset($school_card_use_true[50])?$school_card_use_true[50] : '0',

                30=>isset($school_cards[$school_id][49]) ? $school_cards[$school_id][49] : '0',
                31=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][49]) ? $school_fee[$school_id][49] : $card_fee[49],
                32=>isset($school_card_use_true[49])?$school_card_use_true[49] : '0',

                33=>isset($school_cards[$school_id][41]) ? $school_cards[$school_id][41] : '0',
                34=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][41]) ? $school_fee[$school_id][41] : $card_fee[41],
                35=>isset($school_card_use_true[41])?$school_card_use_true[41] : '0',

                36=>isset($school_cards[$school_id][42]) ? $school_cards[$school_id][42] : '0',
                37=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][42]) ? $school_fee[$school_id][42] : $card_fee[42],
                38=>isset($school_card_use_true[42])?$school_card_use_true[42] : '0',

                39=>isset($school_cards[$school_id][43]) ? $school_cards[$school_id][43] : '0',
                40=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][43]) ? $school_fee[$school_id][43] : $card_fee[43],
                41=>isset($school_card_use_true[43])?$school_card_use_true[43] : '0',

                42=>isset($school_cards[$school_id][44]) ? $school_cards[$school_id][44] : '0',
                43=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][44]) ? $school_fee[$school_id][44] : $card_fee[44],
                44=>isset($school_card_use_true[44])?$school_card_use_true[44] : '0',


                45=>isset($school_cards[$school_id][80]) ? $school_cards[$school_id][80] : '0',
                46=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][80]) ? $school_fee[$school_id][80] : $card_fee[80],
                47=>isset($school_card_use_true[80])?$school_card_use_true[80] : '0',


                48=>isset($school_cards[$school_id][81]) ? $school_cards[$school_id][81] : '0',
                49=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][81]) ? $school_fee[$school_id][81] : $card_fee[81],
                50=>isset($school_card_use_true[81])?$school_card_use_true[81] : '0',

                51=>isset($school_cards[$school_id][60]) ? $school_cards[$school_id][60] : '0',
                52=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][60]) ? $school_fee[$school_id][60] : $card_fee[60],
                53=>isset($school_card_use_true[60])?$school_card_use_true[60] : '0',

                54=>isset($school_cards[$school_id][61]) ? $school_cards[$school_id][61] : '0',
                55=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][61]) ? $school_fee[$school_id][61] : $card_fee[61],
                56=>isset($school_card_use_true[61])?$school_card_use_true[61] : '0',

                57=>isset($school_cards[$school_id][45]) ? $school_cards[$school_id][45] : '0',
                58=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][45]) ? $school_fee[$school_id][45] : $card_fee[45],
                59=>isset($school_card_use_true[45])?$school_card_use_true[45] : '0',

                60=>isset($school_cards[$school_id][46]) ? $school_cards[$school_id][46] : '0',
                61=>isset($school_fee[$school_id]) && isset($school_fee[$school_id][46]) ?
                    $school_fee[$school_id][46] :
                    $card_fee[46],
                62=>isset($school_card_use_true[46]) ? $school_card_use_true[46] : '0',

                63=>isset($school_cards[$school_id][82]) ? $school_cards[$school_id][82] : '0',
                64=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][82]) ? $school_fee[$school_id][82] : $card_fee[82],
                65=>isset($school_card_use_true[82])?$school_card_use_true[82] : '0',

                66=>isset($school_cards[$school_id][83]) ? $school_cards[$school_id][83] : '0',
                67=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][83]) ? $school_fee[$school_id][83] : $card_fee[83],
                68=>isset($school_card_use_true[83])?$school_card_use_true[83] : '0',

                69=>isset($school_cards[$school_id][58]) ? $school_cards[$school_id][58] : '0',
                70=>isset($school_fee[$school_id])&& isset($school_fee[$school_id][58]) ? $school_fee[$school_id][58] : $card_fee[58],
                71=>isset($school_card_use_true[58])?$school_card_use_true[58] : '0',
            ];



        }




        $this->store('开卡数据月份数据00_'.rand(0,100), $rep, '.xlsx');


        dd('done....');

        $name_arr = [
            62=>'【抗疫卡】神奇拼读',
            63=>'【抗疫卡】神奇拼读-引流卡',
            64=>'【抗疫卡】智能背单词',
            65=>'【抗疫卡】智能背单词-引流卡',
            66=>'【抗疫卡】人机对话背课文',
            67=>'【抗疫卡】人机对话背课文-引流卡',
            68=>'【抗疫卡】小学口语100句',
            69=>'【抗疫卡】小学口语100句-引流卡',
            70=>'【抗疫卡】英语语法',
            71=>'【抗疫卡】英语语法-引流卡',
            72=>'【抗疫卡】音标速拼',
            73=>'【抗疫卡】音标速拼-引流卡',
            74=>'【抗疫卡】英语同步复习',
            75=>'【抗疫卡】英语同步复习-引流卡',
            76=>'【抗疫卡】英语中考三件套',
            77=>'【抗疫卡】英语中考三件套-引流卡',
            78=>'【抗疫卡】初中历史/地理/生物',
            79=>'【抗疫卡】初中历史/地理/生物-引流卡',
        ];



        $sql = <<<EOF
SELECT
	school_id,prototype_id, count(*) total
FROM
	`learning`.`card` 
WHERE
	 `is_activated` = '1' 
	AND `deleted_at` IS NULL 
	and prototype_id in (62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79)
	GROUP BY school_id,prototype_id
	ORDER BY school_id asc ,prototype_id asc
EOF;



        $school_info = \DB::select(\DB::raw($sql));

        $school_info = json_decode(json_encode($school_info),true);


        $school_tmp = collect($school_info)->groupBy('school_id')->map(function ($school){
            return $school->pluck( 'total','prototype_id')->toArray();

        })->toArray();


        $sql = <<<EOF
SELECT
	school.id, school.name, school_attribute.value region,mark.name  marketer_name ,operator_user.name oper_name
FROM
	school
	left join school_attribute on school.id = school_attribute.school_id and school_attribute.`key` = 'region'
	
	left join school_attribute  marketer on school.id = marketer.school_id and marketer.`key` = 'marketer_id'
	left join user mark on mark.id = marketer.`value` 
	
	
	left join school_attribute  operator on school.id = operator.school_id and operator.`key` = 'operator_id'
	left join user operator_user on operator_user.id = operator.`value` 
	where school.id in (
	2,4,8,9,10,13,18,19,22,24,26,29,30,31,32,2003,2006,2007,2008,2013,2014,2015,2016,2019,2020,2022,2023,2024,2025,2026,2027,2028,2029,2030,2032,2033,2034,2038,2039,2042,2043,2045,2049,2050,2051,2053,2057,2059,2061,2062,2065,2066,2068,2069,2070,2072,2074,2076,2081,2083,2084,2085,2086,2087,2089,2090,2091,2092,2094,2095,2096,2097,2099,2100,2103,2104,2106,2107,2108,2111,2112,2118,2119,2123,2124,2126,2131,2132,2133,2134,2135,2136,2137,2138,2140,2141,2144,2145,2149,2150,2151,2152,2153,2154,2155,2157,2158,2161,2163,2171,2172,2174,2175,2176,2177,2178,2181,2182,2183,2185,2186,2187,2188,2191,2195,2197,2198,2199,2200,2201,2203,2204,2205,2206,2207,2210,2212,2213,2214,2215,2218,2219,2220,2221,2222,2223,2225,2226,2227,2228,2231,2232,2234,2235,2237,2238,2239,2240,2241,2242,2243,2244,2245,2247,2248,2249,2250,2251,2252,2254,2255,2256,2257,2258,2259,2260,2262,2263,2264,2266,2267,2268,2269,2270,2271,2273,2274,2275,2276,2277,2279,2280,2282,2284,2285,2286,2287,2288,2289,2290,2291,2293,2295,2297,2299,2300,2302,2303,2304,2305,2306,2307,2308,2309,2310,2312,2313,2314,2315,2316,2317,2319,2320,2321,2322,2324,2325,2326,2327,2328,2329,2331,2332,2333,2334,2335,2336,2337,2339,2340,2342,2343,2345,2346,2347,2349,2350,2351,2353,2354,2356,2357,2358,2362,2363,2364,2365,2366,2367,2368,2369,2370,2371,2372,2373,2376,2377,2381,2382,2384,2386,2387,2388,2389,2390,2391,2393,2394,2397,2398,2399,2400,2401,2404,2405,2406,2407,2410,2411,2414,2415,2416,2417,2418,2419,2421,2422,2425,2426,2427,2428,2429,2430,2431,2432,2433,2434,2437,2438,2439,2440,2442,2443,2444,2446,2447,2448,2450,2451,2455,2456,2457,2458,2459,2460,2462,2463,2464,2465,2466,2468,2469,2470,2473,2477,2479,2480,2481,2482,2484,2485,2486,2488,2489,2490,2491,2492,2493,2494,2496,2497,2498,2499,2502,2503,2504,2505,2506,2507,2508,2509,2510,2512,2514,2516,2517,2519,2520,2521,2522,2523,2524,2525,2526,2527,2530,2534,2536,2538,2539,2540,2541,2542,2543,2545,2546,2547,2548,2549,2550,2551,2552,2553,2554,2555,2558,2560,2562,2563,2565,2566,2567,2569,2571,2572,2573,2574,2575,2576,2577,2578,2580,2581,2583,2584,2585,2586,2587,2590,2592,2594,2595,2598,2600,2602,2603,2604,2606,2607,2608,2609,2610,2611,2612,2613,2614,2615,2617,2618,2622,2623,2625,2626,2631,2632,2634,2635,2637,2638,2639,2640,2642,2643,2644,2645,2646,2648,2651,2652,2653,2654,2655,2656,2657,2658,2661,2662,2665,2666,2668,2669,2670,2671,2673,2675,2681,2682,2684,2685,2686,2687,2688,2690,2691,2693,2694,2696,2698,2699,2700,2701,2702,2703,2704,2708,2709,2710,2711,2712,2713,2714,2716,2717,2718,2719,2722,2723,2724,2725,2728,2730,2731,2732,2734,2735,2737,2738,2739,2740,2742,2743,2744,2745,2746,2747,2748,2749,2751,2753,2754,2755,2757,2760,2761,2762,2763,2765,2766,2767,2769,2770,2771,2773,2774,2775,2776,2777,2778,2779,2780,2782,2783,2784,2785,2787,2788,2789,2794,2795,2796,2798,2799,2800,2802,2804,2806,2807,2808,2810,2811,2812,2814,2815,2816,2817,2818,2819,2820,2821
	)
EOF;



        $school = \DB::select(\DB::raw($sql));

        $school = json_decode(json_encode($school),true);

        $school_key = collect($school)->keyBy('id')->toArray();

        $rep = [];
        $rep[] = [
            0=>'id',
            1=>'学校',
            2=>'市场',
            3=>'运营',
            4=>'地区',
            62=>'【抗疫卡】神奇拼读',
            63=>'【抗疫卡】神奇拼读-引流卡',
            64=>'【抗疫卡】智能背单词',
            65=>'【抗疫卡】智能背单词-引流卡',
            66=>'【抗疫卡】人机对话背课文',
            67=>'【抗疫卡】人机对话背课文-引流卡',
            68=>'【抗疫卡】小学口语100句',
            69=>'【抗疫卡】小学口语100句-引流卡',
            70=>'【抗疫卡】英语语法',
            71=>'【抗疫卡】英语语法-引流卡',
            72=>'【抗疫卡】音标速拼',
            73=>'【抗疫卡】音标速拼-引流卡',
            74=>'【抗疫卡】英语同步复习',
            75=>'【抗疫卡】英语同步复习-引流卡',
            76=>'【抗疫卡】英语中考三件套',
            77=>'【抗疫卡】英语中考三件套-引流卡',
            78=>'【抗疫卡】初中历史/地理/生物',
            79=>'【抗疫卡】初中历史/地理/生物-引流卡',
        ];



        foreach ([
                     2,4,8,9,10,13,18,19,22,24,26,29,30,31,32,2003,2006,2007,2008,2013,2014,2015,2016,2019,2020,2022,2023,2024,2025,2026,2027,2028,2029,2030,2032,2033,2034,2038,2039,2042,2043,2045,2049,2050,2051,2053,2057,2059,2061,2062,2065,2066,2068,2069,2070,2072,2074,2076,2081,2083,2084,2085,2086,2087,2089,2090,2091,2092,2094,2095,2096,2097,2099,2100,2103,2104,2106,2107,2108,2111,2112,2118,2119,2123,2124,2126,2131,2132,2133,2134,2135,2136,2137,2138,2140,2141,2144,2145,2149,2150,2151,2152,2153,2154,2155,2157,2158,2161,2163,2171,2172,2174,2175,2176,2177,2178,2181,2182,2183,2185,2186,2187,2188,2191,2195,2197,2198,2199,2200,2201,2203,2204,2205,2206,2207,2210,2212,2213,2214,2215,2218,2219,2220,2221,2222,2223,2225,2226,2227,2228,2231,2232,2234,2235,2237,2238,2239,2240,2241,2242,2243,2244,2245,2247,2248,2249,2250,2251,2252,2254,2255,2256,2257,2258,2259,2260,2262,2263,2264,2266,2267,2268,2269,2270,2271,2273,2274,2275,2276,2277,2279,2280,2282,2284,2285,2286,2287,2288,2289,2290,2291,2293,2295,2297,2299,2300,2302,2303,2304,2305,2306,2307,2308,2309,2310,2312,2313,2314,2315,2316,2317,2319,2320,2321,2322,2324,2325,2326,2327,2328,2329,2331,2332,2333,2334,2335,2336,2337,2339,2340,2342,2343,2345,2346,2347,2349,2350,2351,2353,2354,2356,2357,2358,2362,2363,2364,2365,2366,2367,2368,2369,2370,2371,2372,2373,2376,2377,2381,2382,2384,2386,2387,2388,2389,2390,2391,2393,2394,2397,2398,2399,2400,2401,2404,2405,2406,2407,2410,2411,2414,2415,2416,2417,2418,2419,2421,2422,2425,2426,2427,2428,2429,2430,2431,2432,2433,2434,2437,2438,2439,2440,2442,2443,2444,2446,2447,2448,2450,2451,2455,2456,2457,2458,2459,2460,2462,2463,2464,2465,2466,2468,2469,2470,2473,2477,2479,2480,2481,2482,2484,2485,2486,2488,2489,2490,2491,2492,2493,2494,2496,2497,2498,2499,2502,2503,2504,2505,2506,2507,2508,2509,2510,2512,2514,2516,2517,2519,2520,2521,2522,2523,2524,2525,2526,2527,2530,2534,2536,2538,2539,2540,2541,2542,2543,2545,2546,2547,2548,2549,2550,2551,2552,2553,2554,2555,2558,2560,2562,2563,2565,2566,2567,2569,2571,2572,2573,2574,2575,2576,2577,2578,2580,2581,2583,2584,2585,2586,2587,2590,2592,2594,2595,2598,2600,2602,2603,2604,2606,2607,2608,2609,2610,2611,2612,2613,2614,2615,2617,2618,2622,2623,2625,2626,2631,2632,2634,2635,2637,2638,2639,2640,2642,2643,2644,2645,2646,2648,2651,2652,2653,2654,2655,2656,2657,2658,2661,2662,2665,2666,2668,2669,2670,2671,2673,2675,2681,2682,2684,2685,2686,2687,2688,2690,2691,2693,2694,2696,2698,2699,2700,2701,2702,2703,2704,2708,2709,2710,2711,2712,2713,2714,2716,2717,2718,2719,2722,2723,2724,2725,2728,2730,2731,2732,2734,2735,2737,2738,2739,2740,2742,2743,2744,2745,2746,2747,2748,2749,2751,2753,2754,2755,2757,2760,2761,2762,2763,2765,2766,2767,2769,2770,2771,2773,2774,2775,2776,2777,2778,2779,2780,2782,2783,2784,2785,2787,2788,2789,2794,2795,2796,2798,2799,2800,2802,2804,2806,2807,2808,2810,2811,2812,2814,2815,2816,2817,2818,2819,2820,2821
                 ] as $school_id){


            $rep[] = [
                0=> $school_id,
                1=> $school_key[$school_id]['name'],
                2=> $school_key[$school_id]['marketer_name'],
                3=> $school_key[$school_id]['oper_name'],
                4=> $school_key[$school_id]['region'],

                62=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][62]) ? $school_tmp[$school_id][62] : '0',
                63=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][63]) ? $school_tmp[$school_id][63] : '0',
                64=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][64]) ? $school_tmp[$school_id][64] : '0',
                65=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][65]) ? $school_tmp[$school_id][65] : '0',
                66=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][66]) ? $school_tmp[$school_id][66] : '0',
                67=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][67]) ? $school_tmp[$school_id][67] : '0',
                68=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][68]) ? $school_tmp[$school_id][68] : '0',
                69=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][69]) ? $school_tmp[$school_id][69] : '0',
                70=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][70]) ? $school_tmp[$school_id][70] : '0',
                71=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][71]) ? $school_tmp[$school_id][71] : '0',
                72=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][72]) ? $school_tmp[$school_id][72] : '0',
                73=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][73]) ? $school_tmp[$school_id][73] : '0',
                74=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][74]) ? $school_tmp[$school_id][74] : '0',
                75=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][75]) ? $school_tmp[$school_id][75] : '0',
                76=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][76]) ? $school_tmp[$school_id][76] : '0',
                77=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][77]) ? $school_tmp[$school_id][77] : '0',
                78=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][78]) ? $school_tmp[$school_id][78] : '0',
                79=>isset($school_tmp[$school_id]) && isset($school_tmp[$school_id][79]) ? $school_tmp[$school_id][79] : '0',
                ];
        }

        $this->store('学校开卡统计_'.rand(0,100), $rep, '.xlsx');
        dd($rep);

//
//        $tmp = new ExportSchoolLearningStudent();
//
//        $tmp->handle();
//
//
//
//        dd('done');













**/


        config(['database.default' => 'BXG_online']);

        $last_week = Carbon::now()->subWeek();
        $start_week = $last_week->startOfWeek()->toDateString();
        $start_week_str = $last_week->startOfWeek()->toDateTimeString();
        $end_week = $last_week->endOfWeek()->toDateString();
        $end_week_str = $last_week->endOfWeek()->toDateTimeString();

        $end_week = '2019-11-24';
        $end_week_str = '2019-11-24 23:59:59';


        $last_month = Carbon::now()->startOfMonth()->subDay();
        $last_month_start = $last_month->startOfMonth()->toDateString();
        $last_month_start_str = $last_month->startOfMonth()->toDateTimeString();
        $last_month_end = $last_month->endOfMonth()->toDateString();
        $last_month_end_str = $last_month->endOfMonth()->toDateTimeString();


        $school_database_info = [];

        // 查找学校的地区信息
        $sql = <<<EOF
SELECT
	school.id school_id, school.name school_name, attribute.value region, user.name marketer_name
FROM
	school
	left join school_attribute attribute on attribute.school_id = school.id and attribute.key = 'region'
	left join school_attribute m_attribute on m_attribute.school_id = school.id and m_attribute.key = 'marketer_id'
	LEFT join user on `user`.id = m_attribute.value
EOF;

        $school_region_info = \DB::select(\DB::raw($sql));
        foreach ($school_region_info as $item){
            $region_tmp = $item->region;
            $region = explode('/', $region_tmp);
            $school_database_info[$item->school_id] = [
                'school_id'         => $item->school_id,
                'school_name'       => $item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                "marketer"          => $item->marketer_name,
            ];
        }

        $export_card_data = [];
        $export_card_data[] = [
            'school_id' => 'ID',
            'school_name' => '学校',
            'region_sheng' => '省',
            'region_shi' => '市',
            'region_qu' => '区县',
            'region_jiedao' => '街道',
            'marketer' => '市场专员',
            'card_type' => '卡类型',
            'subject' => '科目',
            'total_count' => '本年累计激活量',
            'curr_month_count' => '11月激活数',  //11
            'curr_month_learn' => '11月学习人数',  //11
            'last_month_count' => '10月激活数',  //10
            'last_month_learn' => '10月学习人数',  //10
            'last_2_month_count' => '9月激活数', // 9
            'last_2_month_learn' => '9月学习人数', // 9
        ];

        // 获取  截止到 上周末的 已被升级 的引流卡
        $ignore_card = \DB::table('card_attribute')
            ->selectRaw('card_id')
            ->where('key', 'normal')
            ->where('created_at', '<=', $end_week_str)
            ->get();
        $ignore_card = $ignore_card->pluck('card_id')->toArray();
        $this->ignore_ids = implode(',', $ignore_card);

        // 获得 学习卡 的 价格
        $card_fee = \DB::table('card_prototype')
            ->selectRaw('name, pay_fee')
//            ->where('is_available', 1)
            ->get();

        $card_fee = $card_fee->pluck('pay_fee','name')->toArray();

        $school_fee = \DB::table('card_prototype_school_customization')
            ->selectRaw('school_id, card_prototype.name,value pay_fee ')
            ->leftJoin('card_prototype',
                'card_prototype_school_customization.prototype_id','=','card_prototype.id')
            ->where('key', 'card_price')
            ->get()
            ->groupBy('school_id')->map(function ($school){
                return $school->pluck('pay_fee', 'name')->toArray();
            })
            ->toArray();

/**
        ###################################################英语卡####################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getNewEnglishCard('2019-01-01 00:00:00', $end_week_str);
        $yinbiao_end_last = $this->handleNewEnglishCard($yinbiao_total);


        // 上月的数据
        $yinbiao_total = $this->getNewEnglishCard($last_month_start_str, $last_month_end_str);
        $yinbiao_last_month = $this->handleNewEnglishCard($yinbiao_total);

        // 上周的数据
        $yinbiao_total = $this->getNewEnglishCard($start_week_str, $end_week_str);
        $yinbiao_last_week = $this->handleNewEnglishCard($yinbiao_total);


        foreach ($yinbiao_end_last as $school_id=>$items){
            foreach ($items as $subject=>$count){
                $export_yinbiao_data[] = [
                    'school_id'         =>  $school_id,
                    'school_name'       =>  $school_database_info[$school_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$school_id]['marketer'],
                    'card_type'         =>  $subject,
                    'subject'           =>  '英语',
                    'total_count'       =>  $count,
                    'last_month_count'  =>  isset($yinbiao_last_month[$school_id]) && isset($yinbiao_last_month[$school_id][$subject]) ? $yinbiao_last_month[$school_id][$subject] : '0',
                    'last_week_count'   =>  isset($yinbiao_last_week[$school_id]) && isset($yinbiao_last_week[$school_id][$subject]) ? $yinbiao_last_week[$school_id][$subject] : '0',
                    'pay_fee'   =>
                        isset($school_fee[$school_id]) && isset($school_fee[$school_id][$subject]) ?
                            $school_fee[$school_id][$subject] :
                            $card_fee[$subject],
                ];
            }
        }

        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

        ###################################################同步卡（2019秋季）####################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getNewAutumnCard('2019-01-01 00:00:00', $end_week_str);
        $yinbiao_end_last = $this->handleNewAutumnCard($yinbiao_total);

        // 上月的
        $yinbiao_total = $this->getNewAutumnCard($last_month_start_str, $last_month_end_str);
        $yinbiao_last_month = $this->handleNewAutumnCard($yinbiao_total);

        // 上周的
        $yinbiao_total = $this->getNewAutumnCard($start_week_str, $end_week_str);
        $yinbiao_last_week = $this->handleNewAutumnCard($yinbiao_total);

        foreach ($yinbiao_end_last as $school_id=>$items){
            foreach ($items as $card=>$subjects){
                foreach ($subjects as $subject=>$count){
                    $export_yinbiao_data[] = [
                        'school_id'         =>  $school_id,
                        'school_name'       =>  $school_database_info[$school_id]['school_name'],
                        "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                        "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                        "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                        "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                        'marketer'          =>  $school_database_info[$school_id]['marketer'],
                        'card_type'         =>  $card,
                        'subject'           =>  $subject,
                        'total_count'       =>  $count,
                        'last_month_count'  =>  isset($yinbiao_last_month[$school_id])
                        && isset($yinbiao_last_month[$school_id][$card])
                        && isset($yinbiao_last_month[$school_id][$card][$subject]) ?
                            $yinbiao_last_month[$school_id][$card][$subject]: '0',
                        'last_week_count'   =>  isset($yinbiao_last_week[$school_id])
                        && isset($yinbiao_last_week[$school_id][$card])
                        && isset($yinbiao_last_week[$school_id][$card][$subject]) ?
                            $yinbiao_last_week[$school_id][$card][$subject]: '0',
                        'pay_fee'   =>
                            isset($school_fee[$school_id]) && isset($school_fee[$school_id][$card]) ? $school_fee[$school_id][$card] : $card_fee[$card],

                    ];
                }
            }

        }

        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


        ###################################################会考卡####################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getNewUnitTestCard('2019-01-01 00:00:00', $end_week_str);
        $yinbiao_end_last = $this->handleNewUnitTestCard($yinbiao_total);

        // 上月的
        $yinbiao_total = $this->getNewUnitTestCard($last_month_start_str, $last_month_end_str);
        $yinbiao_last_month = $this->handleNewUnitTestCard($yinbiao_total);

        // 上周的
        $yinbiao_total = $this->getNewUnitTestCard($start_week_str, $end_week_str);
        $yinbiao_last_week = $this->handleNewUnitTestCard($yinbiao_total);
        foreach ($yinbiao_end_last as $school_id=>$items){
            foreach ($items as $card=>$subjects){
                foreach ($subjects as $subject=>$count){
                    $export_yinbiao_data[] = [
                        'school_id'         =>  $school_id,
                        'school_name'       =>  $school_database_info[$school_id]['school_name'],
                        "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                        "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                        "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                        "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                        'marketer'          =>  $school_database_info[$school_id]['marketer'],
                        'card_type'         =>  $card,
                        'subject'           =>  $subject,
                        'total_count'       =>  $count,
                        'last_month_count'  =>  isset($yinbiao_last_month[$school_id])
                        && isset($yinbiao_last_month[$school_id][$card])
                        && isset($yinbiao_last_month[$school_id][$card][$subject]) ?
                            $yinbiao_last_month[$school_id][$card][$subject]: '0',
                        'last_week_count'   =>  isset($yinbiao_last_week[$school_id])
                        && isset($yinbiao_last_week[$school_id][$card])
                        && isset($yinbiao_last_week[$school_id][$card][$subject]) ?
                            $yinbiao_last_week[$school_id][$card][$subject]: '0',
                        'pay_fee'   =>
                            isset($school_fee[$school_id]) && isset($school_fee[$school_id][$card]) ? $school_fee[$school_id][$card] : $card_fee[$card],

                    ];
                }
            }

        }

        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


 ###################################################音标卡 (3M)####################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getYinbiaoCard('2019-01-01', $end_week);
        $yinbiao_end_last = $this->handleYinbiaoCard($yinbiao_total);

        // 上月
        $yinbiao_total = $this->getYinbiaoCard($last_month_start, $last_month_end);
        $yinbiao_last_month = $this->handleYinbiaoCard($yinbiao_total);

        // 上周
        $yinbiao_total = $this->getYinbiaoCard($start_week, $end_week);
        $yinbiao_last_week = $this->handleYinbiaoCard($yinbiao_total);


        foreach ($yinbiao_end_last as $school_id=>$items){
            foreach ($items as $subject=>$count){
                $export_yinbiao_data[] = [
                    'school_id'         =>  $school_id,
                    'school_name'       =>  $school_database_info[$school_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$school_id]['marketer'],
                    'card_type'         => '音标卡',
                    'subject'           =>  $subject,
                    'total_count'       =>  $count,
                    'last_month_count'  =>  isset($yinbiao_last_month[$school_id]) && isset($yinbiao_last_month[$school_id][$subject]) ? $yinbiao_last_month[$school_id][$subject] : '0',
                    'last_week_count'   =>  isset($yinbiao_last_week[$school_id]) && isset($yinbiao_last_week[$school_id][$subject]) ? $yinbiao_last_week[$school_id][$subject] : '0',
                ];
            }
        }

        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


##############################################  语法卡 (3M) #########################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getEnglishCardInfo(8, '= 92' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => $yinbiao_item->card_prototype_name,
                'subject'           => '英语',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getEnglishCardInfo(8, '= 92' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getEnglishCardInfo(8, '= 92' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

##############################################  口语 (3M) #########################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getEnglishCardInfo(32, '= 92' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => $yinbiao_item->card_prototype_name,
                'subject'           => '英语',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getEnglishCardInfo(32, '= 92' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getEnglishCardInfo(32, '= 92' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));
##############################################  单词卡(标准卡 6M)  ###################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getEnglishCardInfo(38, '= 183' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => $yinbiao_item->card_prototype_name.'(标准卡)',
                'subject'           => '英语',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据

        $yinbiao_last_month = $this->getEnglishCardInfo(38, '= 183' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getEnglishCardInfo(38, '= 183' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

##############################################  单词卡(引流卡 1M)  #######################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getEnglishCardInfo(38, '<= 31' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => $yinbiao_item->card_prototype_name.'(引流卡)',
                'subject'           => '英语',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

        }

        // 上月的数据
        $yinbiao_last_month = $this->getEnglishCardInfo(38, '<= 31' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getEnglishCardInfo(38, '<= 31' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

######################################## 课文卡 (标准卡 6M) #####################################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getEnglishCardInfo(7, '= 183' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => $yinbiao_item->card_prototype_name,
                'subject'           => '英语',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getEnglishCardInfo(7, '= 183' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getEnglishCardInfo(7, '= 183' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


######################################## 课文卡 (引流卡 1M) #####################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getEnglishCardInfo(7, '<= 31' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => $yinbiao_item->card_prototype_name.'(引流卡)',
                'subject'           => '英语',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getEnglishCardInfo(7, '<= 31' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getEnglishCardInfo(7, '<= 31' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 新的 会考卡  #############################################################

        // 新的会考卡 (标准卡)
        // 截止到上周
        $new_union_test_info_end_last_week =  $this->getNewUnitTestInfo(33, '2019-01-01', $end_week);
        // 上月的数据
        $new_union_test_info_last_month = $this->getNewUnitTestInfo(33, $last_month_start, $last_month_end);
        // 上月周的数据
        $new_union_test_info_last_week = $this->getNewUnitTestInfo(33, $start_week, $end_week);

#############################################会考卡 （生物）#############################################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getUnitTestInfo(2, '>= 60' ,'2019-01-01', $end_week);
        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $new_card = isset($new_union_test_info_end_last_week) && isset($new_union_test_info_end_last_week[$school_id]) &&
                isset($new_union_test_info_end_last_week[$school_id]['生物']) ? $new_union_test_info_end_last_week[$school_id]['生物'] : 0;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '会考卡',
                'subject'           => '生物',
                'total_count'       => $yinbiao_item->num_count + $new_card,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据

        $yinbiao_last_month = $this->getUnitTestInfo(2, '>= 60' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;

            $new_card = isset($new_union_test_info_last_month) && isset($new_union_test_info_last_month[$school_id]) &&
            isset($new_union_test_info_last_month[$school_id]['生物']) ? $new_union_test_info_end_last_week[$school_id]['生物'] : 0;

            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count + $new_card;
        }

        // 上周的数据
        $yinbiao_last_week =  $this->getUnitTestInfo(2, '>= 60' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;


            $new_card = isset($new_union_test_info_last_week) && isset($new_union_test_info_last_week[$school_id]) &&
            isset($new_union_test_info_last_week[$school_id]['生物']) ? $new_union_test_info_end_last_week[$school_id]['生物'] : 0;

            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count + $new_card;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

#############################################会考卡 （地理）#############################################################

        $export_yinbiao_data = [];
//        // 截止到上周
        $yinbiao_total = $this->getDiLiTestInfo(1, '>= 60' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $new_card = isset($new_union_test_info_end_last_week) && isset($new_union_test_info_end_last_week[$school_id]) &&
            isset($new_union_test_info_end_last_week[$school_id]['地理']) ? $new_union_test_info_end_last_week[$school_id]['地理'] : 0;

            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '会考卡',
                'subject'           => '地理',
                'total_count'       => $yinbiao_item->num_count + $new_card,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

        }

        // 上月的数据
        $yinbiao_last_month = $this->getDiLiTestInfo(1, '>= 60' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;

            $new_card = isset($new_union_test_info_last_month) && isset($new_union_test_info_last_month[$school_id]) &&
            isset($new_union_test_info_last_month[$school_id]['地理']) ? $new_union_test_info_end_last_week[$school_id]['地理'] : 0;

            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count + $new_card;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getDiLiTestInfo(1, '>= 60' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;

            $new_card = isset($new_union_test_info_last_week) && isset($new_union_test_info_last_week[$school_id]) &&
            isset($new_union_test_info_last_week[$school_id]['地理']) ? $new_union_test_info_end_last_week[$school_id]['地理'] : 0;

            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count + $new_card;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));



#############################################会考卡 冲刺卡（生物）#############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getUnitTestInfo(2, '< 58' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $new_card = isset($new_union_test_info_end_last_week_cut) && isset($new_union_test_info_end_last_week_cut[$school_id]) &&
            isset($new_union_test_info_end_last_week_cut[$school_id]['生物']) ? $new_union_test_info_end_last_week_cut[$school_id]['生物'] : 0;

            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '会考冲刺卡',
                'subject'           => '生物',
                'total_count'       => $yinbiao_item->num_count + $new_card,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

        }
        // 上月的数据
        $yinbiao_last_month =  $this->getUnitTestInfo(2, '< 58' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;

            $new_card = isset($new_union_test_info_last_month_cut) && isset($new_union_test_info_last_month_cut[$school_id]) &&
            isset($new_union_test_info_last_month_cut[$school_id]['生物']) ? $new_union_test_info_last_month_cut[$school_id]['生物'] : 0;

            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count + $new_card;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getUnitTestInfo(2, '< 58' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;

            $new_card = isset($new_union_test_info_last_week_cut) && isset($new_union_test_info_last_week_cut[$school_id]) &&
            isset($new_union_test_info_last_week_cut[$school_id]['生物']) ? $new_union_test_info_last_week_cut[$school_id]['生物'] : 0;

            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count + $new_card;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


#############################################会考卡 冲刺卡（地理）#############################################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total =  $this->getUnitTestInfo(1, '< 58' ,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $new_card = isset($new_union_test_info_end_last_week_cut) && isset($new_union_test_info_end_last_week_cut[$school_id]) &&
            isset($new_union_test_info_end_last_week_cut[$school_id]['地理']) ? $new_union_test_info_end_last_week_cut[$school_id]['地理'] : 0;

            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '会考冲刺卡',
                'subject'           => '地理',
                'total_count'       => $yinbiao_item->num_count + $new_card,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

        }

        // 上月的数据
        $yinbiao_last_month = $this->getUnitTestInfo(1, '< 58' ,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;

            $new_card = isset($new_union_test_info_last_month_cut) && isset($new_union_test_info_last_month_cut[$school_id]) &&
            isset($new_union_test_info_last_month_cut[$school_id]['地理']) ? $new_union_test_info_last_month_cut[$school_id]['地理'] : 0;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count +  $new_card;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getUnitTestInfo(1, '< 58' ,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;

            $new_card = isset($new_union_test_info_last_week_cut) && isset($new_union_test_info_last_week_cut[$school_id]) &&
            isset($new_union_test_info_last_week_cut[$school_id]['地理']) ? $new_union_test_info_last_week_cut[$school_id]['地理'] : 0;

            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count + $new_card;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 春季卡(地理) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getSpringCard('3', "< '2019-08-31'" , '2019-01-01', $end_week);
        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '春季卡',
                'subject'           => '地理',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month  = $this->getSpringCard('3', "< '2019-08-31'" , $last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getSpringCard('3', "< '2019-08-31'" , $start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 春季卡(历史) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getSpringCard('10,11,12,13,14,15,19,20', "< '2019-08-31'" , '2019-01-01', $end_week);


        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '春季卡',
                'subject'           => '历史',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getSpringCard('10,11,12,13,14,15,19,20', "< '2019-08-31'" , $last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getSpringCard('10,11,12,13,14,15,19,20', "< '2019-08-31'" , $start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 春季卡(生物) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getSpringCard('4,5,6,16,17,18', "< '2019-08-31'" , '2019-01-01', $end_week);


        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '春季卡',
                'subject'           => '生物',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getSpringCard('4,5,6,16,17,18', "< '2019-08-31'" , $last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getSpringCard('4,5,6,16,17,18', "< '2019-08-31'" , $start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));



############################################# 暑假卡(地理) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getSummerCard(3,'2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '暑假卡',
                'subject'           => '地理',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getSummerCard(3,$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getSummerCard(3,$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


############################################# 暑假卡(历史) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getSummerCard('10,11,12,13,14,15,19,20','2019-01-01', $end_week);
        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '暑假卡',
                'subject'           => '历史',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getSummerCard('10,11,12,13,14,15,19,20',$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week = $this->getSummerCard('10,11,12,13,14,15,19,20',$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 暑假卡(生物) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getSummerCard('4,5,6,16,17,18','2019-01-01', $end_week);

        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '暑假卡',
                'subject'           => '生物',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

        // 上月的数据
        $yinbiao_last_month = $this->getSummerCard('4,5,6,16,17,18',$last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $yinbiao_last_week =  $this->getSummerCard('4,5,6,16,17,18',$start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));
############################################# 秋季卡(地理) #############################################################

$export_yinbiao_data = [];
// 截止到上周
$yinbiao_total =  $this->getAutumnCard('3', '2019-01-01', $end_week);


foreach ($yinbiao_total as $yinbiao_item){
    $school_id = $yinbiao_item->school_id;
    $export_yinbiao_data[$yinbiao_item->school_id] = [
        'school_id'         => $yinbiao_item->school_id,
        'school_name'       => $school_database_info[$school_id]['school_name'],
        "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
        "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
        "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
        "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
        'marketer'          =>  $school_database_info[$school_id]['marketer'],
        'card_type'         => '秋季卡',
        'subject'           => '地理',
        'total_count'       => $yinbiao_item->num_count,
        'last_month_count'  => '0',
        'last_week_count'   => '0',
    ];
}

// 上月的数据
$yinbiao_last_month = $this->getAutumnCard('3', $last_month_start, $last_month_end);
foreach ($yinbiao_last_month as $yinbiao_last_month_item){
$school_id = $yinbiao_last_month_item->school_id;
$last_month_count = $yinbiao_last_month_item->num_count;
$export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
}

// 上周的数据
        $yinbiao_last_week = $this->getAutumnCard('3', $start_week, $end_week);
foreach ($yinbiao_last_week as $yinbiao_last_week_item){
$school_id = $yinbiao_last_week_item->school_id;
$last_week_count = $yinbiao_last_week_item->num_count;
$export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
}
$export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

        ############################################# 秋季卡(不分科目) #############################################################
        $yinbiao_total = $this->getAutumnCard_v2('2019-01-01', $end_week);
        $qiu_end_last = $this->handleAutumnCard_v2($yinbiao_total);


        $yinbiao_total = $this->getAutumnCard_v2($last_month_start, $last_month_end);
        $qiu_last_month = $this->handleAutumnCard_v2($yinbiao_total);

        $yinbiao_total = $this->getAutumnCard_v2($start_week, $end_week);
        $qiu_last_week = $this->handleAutumnCard_v2($yinbiao_total);


############################################# 秋季卡(历史) #############################################################
    $export_yinbiao_data = [];
    // 截止到上周
    $yinbiao_total = $this->getAutumnCard('10,11,12,13,14,15,19,20', '2019-01-01', $end_week);


    $this->handleAutumnCard_v3($yinbiao_total, $qiu_end_last, $qiu_end_last);

    // 上月的数据
    $yinbiao_last_month = $this->getAutumnCard('10,11,12,13,14,15,19,20', $last_month_start, $last_month_end);
    $this->handleAutumnCard_v3($yinbiao_last_month, $qiu_last_month,$qiu_end_last);

    // 上周的数据
    $yinbiao_last_week = $this->getAutumnCard('10,11,12,13,14,15,19,20', $start_week, $end_week);
    $this->handleAutumnCard_v3($yinbiao_last_week, $qiu_last_week,$qiu_end_last);

    foreach ($qiu_end_last as $school_id=>$items){
        foreach ($items as $subject=>$count){
            $export_yinbiao_data[] = [
                'school_id'         =>  $school_id,
                'school_name'       =>  $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '秋季卡',
                'subject'           =>  $subject,
                'total_count'       =>  $count,
                'last_month_count'  =>  isset($qiu_last_month[$school_id]) && isset($qiu_last_month[$school_id][$subject]) ? $qiu_last_month[$school_id][$subject] : '0',
                'last_week_count'   =>  isset($qiu_last_week[$school_id]) && isset($qiu_last_week[$school_id][$subject]) ? $qiu_last_week[$school_id][$subject] : '0',
            ];
        }
    }

    $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 秋季卡(生物) #############################################################

$export_yinbiao_data = [];
// 截止到上周
$yinbiao_total = $this->getAutumnCard('4,5,6,16,17,18', '2019-01-01', $end_week);
foreach ($yinbiao_total as $yinbiao_item){
    $school_id = $yinbiao_item->school_id;
    $export_yinbiao_data[$yinbiao_item->school_id] = [
        'school_id'         => $yinbiao_item->school_id,
        'school_name'       => $school_database_info[$school_id]['school_name'],
        "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
        "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
        "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
        "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
        'marketer'          =>  $school_database_info[$school_id]['marketer'],
        'card_type'         => '秋季卡',
        'subject'           => '生物',
        'total_count'       => $yinbiao_item->num_count,
        'last_month_count'  => '0',
        'last_week_count'   => '0',
    ];
}
// 上月的数据
$yinbiao_last_month = $this->getAutumnCard('4,5,6,16,17,18', $last_month_start, $last_month_end);
foreach ($yinbiao_last_month as $yinbiao_last_month_item){
$school_id = $yinbiao_last_month_item->school_id;
$last_month_count = $yinbiao_last_month_item->num_count;
$export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
}
// 上周的数据
$yinbiao_last_week = $this->getAutumnCard('4,5,6,16,17,18', $start_week, $end_week);
foreach ($yinbiao_last_week as $yinbiao_last_week_item){
$school_id = $yinbiao_last_week_item->school_id;
$last_week_count = $yinbiao_last_week_item->num_count;
$export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
}
$export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 秋季卡 引流卡 (不分科目) #############################################################

        $export_yinbiao_data = [];
// 截止到上周
        $yinbiao_total = $this->getLittleAutumnCard('2019-01-01', $end_week);


        foreach ($yinbiao_total as $yinbiao_item){
            $school_id = $yinbiao_item->school_id;
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $school_database_info[$school_id]['school_name'],
                "region_sheng"      =>  $school_database_info[$school_id]['region_sheng'],
                "region_shi"        =>  $school_database_info[$school_id]['region_shi'],
                "region_qu"         =>  $school_database_info[$school_id]['region_qu'],
                "region_jiedao"     =>  $school_database_info[$school_id]['region_jiedao'],
                'marketer'          =>  $school_database_info[$school_id]['marketer'],
                'card_type'         => '秋季卡引流卡',
                'subject'           => '',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

// 上月的数据
        $yinbiao_last_month = $this->getLittleAutumnCard($last_month_start, $last_month_end);
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

// 上周的数据
        $yinbiao_last_week = $this->getLittleAutumnCard($start_week, $end_week);
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 单词卡 (年卡) #############################################################
**/
        config(['database.default' => 'DCSJ_online']);
        $export_yinbiao_data = [];
// 截止到昨天的数据
        $day_1126_str = Carbon::parse('2020-03-31')->endOfDay()->toDateTimeString();

        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	AND user_type_id = 2 and timeliness in (300,350,365)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 300 
	    AND rec.created_at <= '$day_1126_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;


        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $relation_id = $yinbiao_item->relation_id;
            if (empty($relation_id)){
                $region_tmp = $yinbiao_item->region;
                $region = explode('/', $region_tmp);
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => '',
                    'school_name'       => $yinbiao_item->school_name,
                    "region_sheng"      => isset($region[0]) ? $region[0] : '',
                    "region_shi"        => isset($region[1]) ? $region[1] : '',
                    "region_qu"         => isset($region[2]) ? $region[2] : '',
                    "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                    'marketer'          => '/',
                    'card_type'         => '单词卡年卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'curr_month_count' => '0',  //11
//                    'curr_month_learn' => '0',  //11
//                    'last_month_count' => '0',  //10
//                    'last_month_learn' => '0',  //10
//                    'last_2_month_count' => '0', // 9
//                    'last_2_month_learn' => '0', // 9
                ];
            }else{
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => intval($relation_id),
                    'school_name'       =>  $school_database_info[$relation_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$relation_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$relation_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$relation_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$relation_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$relation_id]['marketer'],
                    'card_type'         => '单词卡年卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'curr_month_count' => '0',  //11
//                    'curr_month_learn' => '0',  //11
//                    'last_month_count' => '0',  //10
//                    'last_month_learn' => '0',  //10
//                    'last_2_month_count' => '0', // 9
//                    'last_2_month_learn' => '0', // 9
                ];
            }

        }

        // 11 月数据
        $month_11_start_str = '2020-02-01 00:00:00';
        $month_11_end_str = '2020-02-29 23:59:59';

        $month_11_start = '2020-02-01';
        $month_11_end = '2020-02-29';

        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$month_11_start_str)
            ->get();
        $student_ids = [];
        foreach ($student_info as $item){
            $student_ids[] = $item->user_id;
        }

        $student_ids_str = implode(',', $student_ids);


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness in (300,350,365)
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 300 
	    AND rec.created_at <= '$month_11_end_str'
	    AND rec.created_at >= '$month_11_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['curr_month_count'] = $last_month_count;
        }

//        // 学习人数
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (300,350,365)
//	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
//        AND rec.key = 'word_cout'
//	    AND rec.created_at <= '$month_11_end'
//	    AND rec.created_at >= '$month_11_start'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['curr_month_learn'] = $last_month_count;
//        }


// // 10月 数据
//        $month_10_start_str = '2019-10-01 00:00:00';
//        $month_10_end_str = '2019-10-31 23:59:59';
//
//        $month_10_start = '2019-10-01';
//        $month_10_end = '2019-10-31';
//
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$month_10_start_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (300,350,365)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 300
//	    AND rec.created_at <= '$month_10_end_str'
//	    AND rec.created_at >= '$month_10_start_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
//        }
//
//        // 学习人数
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (300,350,365)
//	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
//        AND rec.key = 'word_cout'
//	    AND rec.created_at <= '$month_10_end'
//	    AND rec.created_at >= '$month_10_start'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_month_learn'] = $last_month_count;
//        }
//
//// 9月数据
//        $month_9_start_str = '2019-09-01 00:00:00';
//        $month_9_start = '2019-09-01';
//        $month_9_end_str = '2019-09-30 23:59:59';
//        $month_9_end = '2019-09-30';
//
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$month_9_start_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (300,350,365)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 300
//	    AND rec.created_at <= '$month_9_end_str'
//	    AND rec.created_at >= '$month_9_start_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_2_month_count'] = $last_month_count;
//        }
//
//        // 学习人数
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (300,350,365)
//	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
//        AND rec.key = 'word_cout'
//	    AND rec.created_at <= '$month_9_end'
//	    AND rec.created_at >= '$month_9_start'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_2_month_learn'] = $last_month_count;
//        }


//// 上周的数据
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$start_week_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (300,350,365)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 300
//	    AND rec.created_at <= '$end_week_str'
//	    AND rec.created_at >= '$start_week_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_week = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
//            $school_id = $yinbiao_last_week_item->school_id;
//            $last_week_count = $yinbiao_last_week_item->num_count;
//            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
//        }


        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));



        ############################################# 单词卡 (半年卡) #############################################################

        config(['database.default' => 'DCSJ_online']);

        $export_yinbiao_data = [];


// 总的
        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	AND user_type_id = 2 and timeliness in (183,182,180)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300 
	    AND rec.created_at <= '$day_1126_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;


        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $relation_id = $yinbiao_item->relation_id;
            if (empty($relation_id)){
                $region_tmp = $yinbiao_item->region;
                $region = explode('/', $region_tmp);
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => '',
                    'school_name'       => $yinbiao_item->school_name,
                    "region_sheng"      => isset($region[0]) ? $region[0] : '',
                    "region_shi"        => isset($region[1]) ? $region[1] : '',
                    "region_qu"         => isset($region[2]) ? $region[2] : '',
                    "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                    'marketer'          => '/',
                    'card_type'         => '单词卡半年卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'curr_month_count' => '0',  //11
//                    'curr_month_learn' => '0',  //11
//                    'last_month_count' => '0',  //10
//                    'last_month_learn' => '0',  //10
//                    'last_2_month_count' => '0', // 9
//                    'last_2_month_learn' => '0', // 9
                ];
            }else{
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => intval($relation_id),
                    'school_name'       => $school_database_info[$relation_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$relation_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$relation_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$relation_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$relation_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$relation_id]['marketer'],
                    'card_type'         => '单词卡半年卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'curr_month_count' => '0',  //11
//                    'curr_month_learn' => '0',  //11
//                    'last_month_count' => '0',  //10
//                    'last_month_learn' => '0',  //10
//                    'last_2_month_count' => '0', // 9
//                    'last_2_month_learn' => '0', // 9
                ];
            }

        }

        // 11 月
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$month_11_start_str)
            ->get();
        $student_ids = [];
        foreach ($student_info as $item){
            $student_ids[] = $item->user_id;
        }

        $student_ids_str = implode(',', $student_ids);


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness in (183,182,180)
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
          AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300 
	    AND rec.created_at <= '$month_11_end_str'
	    AND rec.created_at >= '$month_11_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['curr_month_count'] = $last_month_count;
        }
// 学习人数
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
//        AND rec.key = 'word_cout'
//	    AND rec.created_at <= '$month_11_end'
//	    AND rec.created_at >= '$month_11_start'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//
//
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['curr_month_learn'] = $last_month_count;
//        }
//
//
//
//        // 10
//
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$month_10_start_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//          AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180
//        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300
//	    AND rec.created_at <= '$month_10_end_str'
//	    AND rec.created_at >= '$month_10_start_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//
//
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
//        }
//
//// 学习人数
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
//        AND rec.key = 'word_cout'
//	    AND rec.created_at <= '$month_10_end'
//	    AND rec.created_at >= '$month_10_start'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//
//
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_month_learn'] = $last_month_count;
//        }
//
//        // 9
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$month_9_start_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//          AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180
//        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300
//	    AND rec.created_at <= '$month_9_end_str'
//	    AND rec.created_at >= '$month_9_start_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//
//
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_2_month_count'] = $last_month_count;
//        }
//
//// 学习人数
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id
//        AND rec.key = 'word_cout'
//	    AND rec.created_at <= '$month_9_end'
//	    AND rec.created_at >= '$month_9_start'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_month = \DB::select(\DB::raw($sql));
//
//
//        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
//            $school_id = $yinbiao_last_month_item->school_id;
//            $last_month_count = $yinbiao_last_month_item->num_count;
//            $export_yinbiao_data[$school_id]['last_2_month_learn'] = $last_month_count;
//        }




//// 上周的数据
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$start_week_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//          AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180
//        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300
//	    AND rec.created_at <= '$end_week_str'
//	    AND rec.created_at >= '$start_week_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_week = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
//            $school_id = $yinbiao_last_week_item->school_id;
//            $last_week_count = $yinbiao_last_week_item->num_count;
//            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
//        }


        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));



        ############################################# 单词卡 (体验卡) #############################################################
/*
        config(['database.default' => 'DCSJ_online']);

        $export_yinbiao_data = [];


// 总的
        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	AND user_type_id = 2 and timeliness <180
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 180 
	    AND rec.created_at <= '$day_1126_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;


        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $relation_id = $yinbiao_item->relation_id;
            if (empty($relation_id)){
                $region_tmp = $yinbiao_item->region;
                $region = explode('/', $region_tmp);
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => '',
                    'school_name'       => $yinbiao_item->school_name,
                    "region_sheng"      => isset($region[0]) ? $region[0] : '',
                    "region_shi"        => isset($region[1]) ? $region[1] : '',
                    "region_qu"         => isset($region[2]) ? $region[2] : '',
                    "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                    'marketer'          => '/',
                    'card_type'         => '体验卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'curr_month_count' => '0',  //11
                    'curr_month_learn' => '0',  //11
                    'last_month_count' => '0',  //10
                    'last_month_learn' => '0',  //10
                    'last_2_month_count' => '0', // 9
                    'last_2_month_learn' => '0', // 9
                ];
            }else{
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => intval($relation_id),
                    'school_name'       => $school_database_info[$relation_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$relation_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$relation_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$relation_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$relation_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$relation_id]['marketer'],
                    'card_type'         => '体验卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'curr_month_count' => '0',  //11
                    'curr_month_learn' => '0',  //11
                    'last_month_count' => '0',  //10
                    'last_month_learn' => '0',  //10
                    'last_2_month_count' => '0', // 9
                    'last_2_month_learn' => '0', // 9
                ];
            }

        }

        // 11 月
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$month_11_start_str)
            ->get();
        $student_ids = [];
        foreach ($student_info as $item){
            $student_ids[] = $item->user_id;
        }

        $student_ids_str = implode(',', $student_ids);


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness < 180
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 180 
	    AND rec.created_at <= '$month_11_end_str'
	    AND rec.created_at >= '$month_11_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['curr_month_count'] = $last_month_count;
        }
// 学习人数
        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness <180
	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id 
        AND rec.key = 'word_cout'
	    AND rec.created_at <= '$month_11_end'
	    AND rec.created_at >= '$month_11_start'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['curr_month_learn'] = $last_month_count;
        }



        // 10

        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$month_10_start_str)
            ->get();
        $student_ids = [];
        foreach ($student_info as $item){
            $student_ids[] = $item->user_id;
        }

        $student_ids_str = implode(',', $student_ids);


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness <180
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 180 
	    AND rec.created_at <= '$month_10_end_str'
	    AND rec.created_at >= '$month_10_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

// 学习人数
        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness <180
	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id 
        AND rec.key = 'word_cout'
	    AND rec.created_at <= '$month_10_end'
	    AND rec.created_at >= '$month_10_start'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_learn'] = $last_month_count;
        }

        // 9
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$month_9_start_str)
            ->get();
        $student_ids = [];
        foreach ($student_info as $item){
            $student_ids[] = $item->user_id;
        }

        $student_ids_str = implode(',', $student_ids);


        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness <180
	    AND users.id not in ($student_ids_str)
	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id 
        AND DATEDIFF( rec.expire_time, rec.start_time ) < 180 
	    AND rec.created_at <= '$month_9_end_str'
	    AND rec.created_at >= '$month_9_start_str'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_2_month_count'] = $last_month_count;
        }

// 学习人数
        $sql = <<<EOF
SELECT
	relation.value relation_id,
	school.id AS school_id,
	school.`name` AS school_name,
	count( DISTINCT rec.user_id ) AS num_count,
	attribute.`value` AS region 
FROM
	school
	LEFT JOIN users ON users.school_id = school.id 
	    AND user_type_id = 2 and timeliness <180
	LEFT JOIN statistic_student_data_day AS rec ON rec.user_id = users.id 
        AND rec.key = 'word_cout'
	    AND rec.created_at <= '$month_9_end'
	    AND rec.created_at >= '$month_9_start'
	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id 
	    AND attribute.`key` = 'region_copy'
	LEFT JOIN school_attribute relation ON relation.school_id = school.id 
	    AND relation.`key` = 'relation_id' 
WHERE
	school.id NOT IN ( 1, 5 ) 
GROUP BY
	school.id 
HAVING
	num_count > 0
order by 
    relation_id+0 asc
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_2_month_learn'] = $last_month_count;
        }




//// 上周的数据
//        $student_info = \DB::table('user_courses')
//            ->selectRaw('distinct user_id')
//            ->where('created_at', '<=',$start_week_str)
//            ->get();
//        $student_ids = [];
//        foreach ($student_info as $item){
//            $student_ids[] = $item->user_id;
//        }
//
//        $student_ids_str = implode(',', $student_ids);
//
//
//        $sql = <<<EOF
//SELECT
//	relation.value relation_id,
//	school.id AS school_id,
//	school.`name` AS school_name,
//	count( DISTINCT rec.user_id ) AS num_count,
//	attribute.`value` AS region
//FROM
//	school
//	LEFT JOIN users ON users.school_id = school.id
//	    AND user_type_id = 2 and timeliness in (183,182,180)
//	    AND users.id not in ($student_ids_str)
//	LEFT JOIN user_courses_record AS rec ON rec.user_id = users.id
//          AND DATEDIFF( rec.expire_time, rec.start_time ) >= 180
//        AND DATEDIFF( rec.expire_time, rec.start_time ) < 300
//	    AND rec.created_at <= '$end_week_str'
//	    AND rec.created_at >= '$start_week_str'
//	LEFT JOIN school_attribute attribute ON attribute.school_id = school.id
//	    AND attribute.`key` = 'region_copy'
//	LEFT JOIN school_attribute relation ON relation.school_id = school.id
//	    AND relation.`key` = 'relation_id'
//WHERE
//	school.id NOT IN ( 1, 5 )
//GROUP BY
//	school.id
//HAVING
//	num_count > 0
//order by
//    relation_id+0 asc
//EOF;
//
//        $yinbiao_last_week = \DB::select(\DB::raw($sql));
//        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
//            $school_id = $yinbiao_last_week_item->school_id;
//            $last_week_count = $yinbiao_last_week_item->num_count;
//            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
//        }


        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

*/

        $export_card_data = collect($export_card_data)->sortBy('school_id')->toArray();


        $this->store('学校卡片'.rand(0,100), $export_card_data, '.xlsx');



        dd('done');



        $sql = <<<EOF
SELECT
	marketer_user.name marketer_user_name, school.id school_id,school.name school_name,  attribute.value  region,principal.name p_name,principal.phone p_phone,finance_school_balance.balance balance,finance_school_statement.received,finance_school_statement.student_order,finance_school_statement.payment
FROM
	`school`
	LEFT JOIN `user` principal on principal.id = school.principal_id
	LEFT JOIN school_attribute marketer on marketer.school_id = school.id and marketer.key = 'marketer_id'
	LEFT JOIN `user` marketer_user on marketer.`value` = marketer_user.id
	LEFT join finance_school_balance on finance_school_balance.school_id = school.id
	LEFT JOIN school_attribute attribute on attribute.school_id = school.id and attribute.key = 'region'
	LEFT JOIN (
				SELECT
				school_id,
				sum(if(type='received',fee,0 ))  received,
				sum(if(type='student_order',fee,0 )) student_order,
				sum(if(type='payment',fee,0 )) payment
			FROM
				`finance_school_statement`
				where id > 3832
				GROUP BY school_id
	) finance_school_statement on finance_school_statement.school_id = school.id
EOF;

        $school_info = \DB::select(\DB::raw($sql));

        $export_school_data = [];
        $export_school_data[] = [
            "marketer_user_name" => '市场专员',
            "school_id" => '学校id',
            "school_name" => '学校',
            "region_sheng" => '省',
            "region_shi" => '市',
            "region_qu" => '区',
            "region_jiedao" => '街道',
            "p_name" => '校长',
            "p_phone" => '校长电话',
            "received" => '累计收款',
            "student_order" => '学生开卡金额',
            "payment" => '累计退款',
            "balance" => '学校余额',
        ];

        foreach ($school_info as $school_item){
            $region_tmp = $school_item->region;
            $region = explode('/', $region_tmp);
            $export_school_data[] = [
                "marketer_user_name" => $school_item->marketer_user_name,
                "school_id" => $school_item->school_id,
                "school_name" => $school_item->school_name,
                "region_sheng" => isset($region[0]) ? $region[0] : '',
                "region_shi" => isset($region[1]) ? $region[1] : '',
                "region_qu" => isset($region[2]) ? $region[2] : '',
                "region_jiedao" => isset($region[3]) ? $region[3] : '',
                "p_name" => $school_item->p_name,
                "p_phone" => $school_item->p_phone,
                "received" => empty($school_item->received) ? '0' : $school_item->received,
                "student_order" => empty($school_item->student_order) ? '0' : $school_item->student_order,
                "payment" => empty($school_item->payment) ? '0' : $school_item->payment,
                "balance" => empty($school_item->balance) ? '0' : $school_item->balance,
            ];
        }

        $this->store('学校详情_'.rand(0,100), $export_school_data, '.xlsx');

        dd('done');

    }


    public function getUnitTestInfo($prototype_id, $relation_info ,$start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id` = $prototype_id
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND validity_days $relation_info
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return \DB::select(\DB::raw($sql));
    }


    public function getNewUnitTestInfo($prototype_id, $start, $end)
    {
        $new_union_test_info = [];
        $sql = <<<EOF
SELECT
    card.id, card.school_id, card.validity_days, card_prototype.name, SUBSTRING(course_book.`name`, 3 ,2)  subject_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
        LEFT join course_user_book_record on course_user_book_record.card_id = card.id
        left join course_book on course_book.id = course_user_book_record.book_id
WHERE
    `prototype_id` = $prototype_id
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY card.id
EOF;
        $new_union_test = \DB::select(\DB::raw($sql));
        $new_union_test = json_decode(json_encode($new_union_test), true);
        collect($new_union_test)->groupBy('school_id')->map(function ($school) use (&$new_union_test_info){
            $school->groupBy('subject_name')->map(function ($subject) use ( &$new_union_test_info ){
                $base = $subject->first();
                $school_id = $base['school_id'];
                $subject_name = $base['subject_name'];
                $new_union_test_info[$school_id][$subject_name] = $subject->count();
            });
        });
        return $new_union_test_info;
    }


    public function getEnglishCardInfo($prototype_id, $relation_info ,$start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id` = $prototype_id
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND validity_days $relation_info
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;


        return \DB::select(\DB::raw($sql));
    }


    public function getSpringCard($prototype_ids, $relation_info ,$start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
     `prototype_id`  in ( $prototype_ids )
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND `expired_at`   $relation_info
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return  \DB::select(\DB::raw($sql));
    }


    public function getSummerCard($prototype_ids, $start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id`  in ( $prototype_ids )
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return \DB::select(\DB::raw($sql));
    }


    public function getAutumnCard($prototype_ids, $start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id`  in ( $prototype_ids )
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND `expired_at` = '2020-01-17'
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return \DB::select(\DB::raw($sql));
    }

    public function getLittleAutumnCard($start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id`  = 34
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND `expired_at` < '2020-01-17'
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return \DB::select(\DB::raw($sql));
    }

    public function getAutumnCard_v2($start, $end)
    {
        $sql = <<<EOF
SELECT
    card.id, card.school_id, card.validity_days, card_prototype.name, LEFT(course_book.`name`, 2 )  subject_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
        LEFT join course_user_book_record on course_user_book_record.card_id = card.id
        left join course_book on course_book.id = course_user_book_record.book_id
WHERE
    `prototype_id` = 34
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND validity_days >= 60
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY card.id
EOF;
        return \DB::select(\DB::raw($sql));
    }


    public function handleAutumnCard_v2($yinbiao_total)
    {
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);
        return  collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();
    }

    public function handleAutumnCard_v3($yinbiao_last_week, &$qiu_last_week,$qiu_end_last)
    {
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            if (isset($qiu_last_week[$school_id])){
                if (isset($qiu_end_last[$school_id]['历史'])){
                    $qiu_last_week[$school_id]['历史'] += $yinbiao_last_week_item->num_count;
                }else{
                    $qiu_last_week[$school_id]['历史'] = $yinbiao_last_week_item->num_count;
                }
            }else{
                $qiu_last_week[$school_id] = [];
                $qiu_last_week[$school_id]['历史'] = $yinbiao_last_week_item->num_count;
            }
        }
    }


    public function getYinbiaoCard($start, $end)
    {
        $sql = <<<EOF
SELECT
    card.id, card.school_id, card.validity_days, card_prototype.name, course_book.`name` subject_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
        LEFT join course_user_book_record on course_user_book_record.card_id = card.id
        left join course_book on course_book.id = course_user_book_record.book_id
WHERE
    `prototype_id` = 9
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY card.id
EOF;


        return  \DB::select(\DB::raw($sql));

    }

    public function handleYinbiaoCard($yinbiao_total)
    {
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);
        return collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();
    }



    public function getDiLiTestInfo($prototype_id, $relation_info ,$start, $end)
    {
        $sql = <<<EOF
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id` = $prototype_id
    AND `card`.`deleted_at` IS NULL
    and card.id not in (11452,11453,11454,11455,11456,11457,11458,11459,11460,11461,11462,11463,11464,11465,11466,11467,11468,11469,11470,11471,11472,11473,11474,11475,11476,11477,11478,11479,11480,11481,11482,11483,11484,11485,11486,11487,11488,11489,11490,11491,11492,11493,11494,11495,11496,11497,11498,11499,11500,11501,11502,11503)
    AND `is_activated` = 1
    AND card.`school_id` not in (1,2000,2010,2033)
    AND card.`student_id` <> 1751
    AND validity_days $relation_info
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return \DB::select(\DB::raw($sql));
    }


    public function getNewEnglishCard($start_str, $end_str)
    {
        $sql = <<<EOF
SELECT
	card.school_id ,card_prototype.`name` subject_name, card_prototype.`pay_fee`
FROM
	`learning`.`card` 
	left JOIN card_prototype on card.prototype_id = card_prototype.id 
WHERE
	`prototype_id` IN (39,40,41,42,43,44,49,50,52,53,54) 
	AND card.`school_id` NOT IN ( 1, 2000, 2010, 2033 )
	AND card.`id` NOT IN (  $this->ignore_ids )
	AND `student_id` <> 1751
	and is_activated = 1
	and card.deleted_at is NULL
	and card.created_at >=  '$start_str'
	and card.created_at <=  '$end_str'
EOF;
        return \DB::select(\DB::raw($sql));
    }

    public function handleNewEnglishCard($yinbiao_total)
    {
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);

        return  collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();
    }


    public function getNewAutumnCard($start_str, $end_str)
    {
        $sql = <<<EOF
SELECT
	card.school_id ,card_prototype.`name` card_name,SUBSTRING(course_book.`name`, 1 ,2)  subject_name
	FROM
	`learning`.`card` 
	left JOIN card_prototype on card.prototype_id = card_prototype.id 
	LEFT join course_user_book_record on course_user_book_record.card_id = card.id
  left join course_book on course_book.id = course_user_book_record.book_id
WHERE
	`prototype_id` IN (47,48) 
	AND card.`school_id` NOT IN ( 1, 2000, 2010, 2033 )
    AND card.`id` NOT IN (  $this->ignore_ids )
	and is_activated = 1
	AND card.`student_id` <> 1751
	and card.deleted_at is NULL
	and card.created_at >=  '$start_str'
	and card.created_at <=  '$end_str'
EOF;
        return \DB::select(\DB::raw($sql));
    }

    public function handleNewAutumnCard($yinbiao_total)
    {
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);

        return collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('card_name')->map(function ($card){
                return $card->groupBy('subject_name')->map(function ($subject){
                    return $subject->count();
                });
            });
        })->toArray();
    }


    public function getNewUnitTestCard($start_str, $end_str)
    {
        $sql = <<<EOF
SELECT
	card.school_id ,card_prototype.`name` card_name,SUBSTRING(course_book.`name`, 3 ,2)  subject_name
FROM
	`learning`.`card` 
	left JOIN card_prototype on card.prototype_id = card_prototype.id 
	LEFT join course_user_book_record on course_user_book_record.card_id = card.id
  left join course_book on course_book.id = course_user_book_record.book_id
WHERE
	`prototype_id` IN (45, 46) 
	AND card.`school_id` NOT IN ( 1, 2000, 2010, 2033 )
	AND card.`id` NOT IN (  $this->ignore_ids )
	and is_activated = 1
	and card.deleted_at is NULL
	AND card.`student_id` <> 1751
	and card.created_at >=  '$start_str'
	and card.created_at <=  '$end_str'
EOF;
        return \DB::select(\DB::raw($sql));
    }

    public function handleNewUnitTestCard($yinbiao_total)
    {
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);

        return  collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('card_name')->map(function ($card){
                return $card->groupBy('subject_name')->map(function ($subject){
                    return $subject->count();
                });
            });
        })->toArray();
    }
}
