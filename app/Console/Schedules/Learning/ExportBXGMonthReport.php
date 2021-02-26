<?php


/**
SELECT
LEFT(finance_school_statement.created_at ,10), finance_school_statement.school_id,school.`name`, finance_school_statement.approval_code,
finance_school_statement.content, finance_school_statement.fee, mark.name, operator_user.`name`, school_attribute.`value`
FROM
`learning`.`finance_school_statement`

left join school on school.id = finance_school_statement.school_id

left join school_attribute on school.id = school_attribute.school_id and school_attribute.`key` = 'region'

left join school_attribute  marketer on school.id = marketer.school_id and marketer.`key` = 'marketer_id'
left join user mark on mark.id = marketer.`value`

left join school_attribute  operator on school.id = operator.school_id and operator.`key` = 'operator_id'
left join user operator_user on operator_user.id = operator.`value`
WHERE
finance_school_statement.`label_id` = '4'
and finance_school_statement.created_at >= '2020-05-01 00:00:00'
 */

namespace App\Console\Schedules\Learning;


use App\Console\Schedules\BaseSchedule;
use Carbon\Carbon;

class ExportBXGMonthReport extends BaseSchedule
{
    public function handle()
    {
        ini_set('memory_limit', '2048M');
        config(['database.default' => 'BXG_online']);

        $day_count = 31;
        $date_type = 'M2021-01';
        $start_time_str = '2021-01-01 00:00:00';
        $end_time_str = '2021-01-31 23:59:59';
        $start_date = '2021-01-01';
        $end_date = '2021-01-31';

        $school_card_fee = $this->getSchoolCardFee($start_time_str,$end_time_str);
        $school_refund_card_fee = $this->getSchoolRefundCardFee($start_time_str,$end_time_str);


        $school_cards = $this->getSchoolCards($start_time_str,$end_time_str);
        $school_refund = $this->getSchoolRefundCard($start_time_str,$end_time_str);

        foreach ($school_refund as $school_id=>$card_info){
            foreach ($card_info as $card_id=>$fee_count){
                foreach ($fee_count as $fee=>$count){
                    if (!isset($school_cards[$school_id])){
                        $school_cards[$school_id] = [];
                    }
                    if (!isset($school_cards[$school_id][$card_id])){
                        $school_cards[$school_id][$card_id] = [];
                    }
                    if (!isset($school_cards[$school_id][$card_id][$fee])){
                        $school_cards[$school_id][$card_id][$fee] = -1 * intval($count);
                    }else{
                        $school_cards[$school_id][$card_id][$fee] = intval($school_cards[$school_id][$card_id][$fee]) - intval($count);
                    }

                }

            }
        }


        foreach ($school_cards as $school_id=>$card_info){
            foreach ($card_info as $card_id=>$fee_count){

                $count_str = '';
                $fee_str = '';
                foreach ($fee_count as $fee=>$count){
                    $count_str .= '|'.$count;
                    $fee_str .= '|'.intval($fee);
                }
                $count_str = trim($count_str,'|');
                $fee_str = trim($fee_str,'|');

                $school_cards[$school_id][$card_id] = [];
                $school_cards[$school_id][$card_id]['count'] = $count_str;
                $school_cards[$school_id][$card_id]['fee'] = $fee_str;
            }
        }


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
            11=>'售卡金额（程序算的）',
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
            72=>'7C 同步提分卡 ',
            73=>'',
            74=>'',
            75=>'9C 会考冲刺卡',
            76=>'',
            77=>'',
            78=>'10D 同步冲刺卡',
            79=>'',
            80=>'',
            81=>'7D 英语同步期中冲刺(月)',
            82=>'',
            83=>'',
            84 => '11A 语文基础训练卡(季)',
            85 => '',
            86 => '',
            87 => '11B 古诗文背诵卡(半年)',
            88 => '',
            89 => '',
            90 => '11C 古诗文背诵卡(月)',
            91 => '',
            92 => '',
            93 => '11D 语文基础训练卡(月)',
            94 => '',
            95 => '',

            96 => '4C 课文卡(月)',
            97 => '',
            98 => '',
            99 => '1E 单词卡(月)',
            100 => '',
            101 => '',

            102 => '2021会考-初中地理/生物/历史',
            103 => '',
            104 => '',
            105 => '初中化学同步进阶(月)',
            106 => '',
            107 => '',
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
            72=>'本月开卡量',
            73=>'结算价',
            74=>'学习人数',
            75=>'本月开卡量',
            76=>'结算价',
            77=>'学习人数',
            78=>'本月开卡量',
            79=>'结算价',
            80=>'学习人数',
            81=>'本月开卡量',
            82=>'结算价',
            83=>'学习人数',
            84=> '本月开卡量',
            85=> '结算价',
            86=> '学习人数',
            87=> '本月开卡量',
            88=> '结算价',
            89=> '学习人数',
            90=> '本月开卡量',
            91=> '结算价',
            92=> '学习人数',
            93=> '本月开卡量',
            94=> '结算价',
            95=> '学习人数',

            96 => '本月开卡量',
            97 => '结算价',
            98 => '学习人数',
            99 => '本月开卡量',
            100 => '结算价',
            101 => '学习人数',

            102 => '本月开卡量',
            103 => '结算价',
            104 => '学习人数',
            105 => '本月开卡量',
            106 => '结算价',
            107 => '学习人数',
        ];

        // 获得 学生练习 课程
//        $school_card_use_info = $this->getSchoolLearnInfo($start_date, $day_count);
        $tmp_resulttt1 = $this->getSchoolLearnInfo($start_date, $day_count);
        /**
        'school_card_use_info' =>$school_card_use_info,
        'book_trail_info' => $book_trail_info,
        'student_book_trail_info' => $student_book_trail_info
         */


        $school_card_use_info = $tmp_resulttt1['school_card_use_info'];

        $book_trail_info = $tmp_resulttt1['book_trail_info'];

        $student_book_trail_info = $tmp_resulttt1['student_book_trail_info'];


        $trail_book = \DB::table('course_book')
            ->selectRaw('course_book.id, CONCAT(course_book.name, "---", card_subject.name) as name')
            ->leftJoin('card_subject', 'card_subject.id', '=', 'course_book.subject_id')
            ->whereIn('course_book.id', array_unique($book_trail_info))
            ->get();



        $rep_2 = [];
        $rep_2_tmp = [
            'school_id'=>'学校ID',
            'school_name'=>'学校名称',
            'sheng'=>'省',
            'shi'=>'地市',
            'qu'=>'区县',
            'operator'=>'运营代表',
        ];

        foreach ($trail_book as $trail_book_item){
            $rep_2_tmp[$trail_book_item->id] = $trail_book_item->name;
        }
        $rep_2[] = $rep_2_tmp;

        // 学校基本信息
        $school_info = $this->getSchoolInfo();


        // 获得 学习卡 的 价格
        $card_fee = $this->getCardFee();

        // 获得 学校 的 价格
        $school_fee = $this->getSchoolFee();

        // 学校学习人数
        $school_learn_student = $this->getSchoolLearnStudent($date_type);

######################################### 单词 速记 #################################################################

        // 获得 单词速记 年卡
        $year_card = $this->getSchoolYearCard($start_time_str, $end_time_str);

        // 获得 单词速记 半年卡
        $half_year_card = $this->getSchoolHalfYearCard($start_time_str, $end_time_str);

        // 获得 单词速记 学习人数
        $DCSJ_learn_info = $this->getLearnStudentInfo($start_date, $end_date);

        // 拼接数据
        foreach ($school_info as $school_id=>$card_arr){

            $region = $school_info[$school_id]['region'];
            $region_arr = explode('/', $region);

            $school_card_use_true = [];
            // 计算一个学校的
            if (isset($school_card_use_info[$school_id])){
                foreach ($school_card_use_info[$school_id] as $card=>$student_ids){
                    $school_card_use_true[$card] = count(array_unique($student_ids));
                }
            }

           if (isset($student_book_trail_info[$school_id])){
               $rep_2_tmp = [
                   'school_id'=>$school_id,
                   'school_name'=>$school_info[$school_id]['name'],
                   'sheng'=>$region_arr[0],
                   'shi'=>$region_arr[1],
                   'qu'=>isset($region_arr[2]) ? $region_arr[2] : '',
                   'operator'=>$school_info[$school_id]['oper_name'],
               ];
               foreach ($trail_book as $trail_book_item){
                   $rep_2_tmp[$trail_book_item->id] = '0';
               }

               foreach ($student_book_trail_info[$school_id] as $book=>$students){
//                   \Log::info($school_id.':'.$book.'---'.count(array_unique($students)));
                   $rep_2_tmp[$book] = count(array_unique($students));
               }

               $rep_2[] = $rep_2_tmp;
           }

            $tmp_fee = (isset($school_card_fee[$school_id]) ?  $school_card_fee[$school_id] : 0 )
                -
                (isset($school_refund_card_fee[$school_id]) ?  $school_refund_card_fee[$school_id] : 0 );

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
                11=> $tmp_fee ? $tmp_fee.'' : '0',
                12=>'',
                13=>'',
                14=>isset($school_learn_student[$school_id])  ? ($school_learn_student[$school_id]? $school_learn_student[$school_id] : '0') : '0',
                15=>isset($school_cards[$school_id][39]) ? $school_cards[$school_id][39]['count'] : '0',
                16=>isset($school_cards[$school_id][39]) ? $school_cards[$school_id][39]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][39]) ? $school_fee[$school_id][39] : $card_fee[39]),
                17=>isset($school_card_use_true[39])?$school_card_use_true[39] : '0',

                18=>isset($school_cards[$school_id][40]) ? $school_cards[$school_id][40]['count'] : '0',
                19=>isset($school_cards[$school_id][40]) ? $school_cards[$school_id][40]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][40]) ? $school_fee[$school_id][40] : $card_fee[40]),
                20=>isset($school_card_use_true[40])?$school_card_use_true[40] : '0',

                21=>isset($school_cards[$school_id][53]) ? $school_cards[$school_id][53]['count'] : '0',
                22=>isset($school_cards[$school_id][53]) ? $school_cards[$school_id][53]['fee'] :
                (isset($school_fee[$school_id])&& isset($school_fee[$school_id][53]) ? $school_fee[$school_id][53] : $card_fee[53]),
                23=>isset($school_card_use_true[53])?$school_card_use_true[53] : '0',


                24=>isset($year_card[$school_id]) ? $year_card[$school_id] : '0',
                25=>isset($half_year_card[$school_id]) ? $half_year_card[$school_id] : '0',
                26=>isset($DCSJ_learn_info[$school_id]) ? $DCSJ_learn_info[$school_id] : '0',

                27=>isset($school_cards[$school_id][50]) ? $school_cards[$school_id][50]['count'] : '0',
                28=>isset($school_cards[$school_id][50]) ? $school_cards[$school_id][50]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][50]) ? $school_fee[$school_id][50] : $card_fee[50]),
                29=>isset($school_card_use_true[50])?$school_card_use_true[50] : '0',

                30=>isset($school_cards[$school_id][49]) ? $school_cards[$school_id][49]['count'] : '0',
                31=>isset($school_cards[$school_id][49]) ? $school_cards[$school_id][49]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][49]) ? $school_fee[$school_id][49] : $card_fee[49]),
                32=>isset($school_card_use_true[49])?$school_card_use_true[49] : '0',

                33=>isset($school_cards[$school_id][41]) ? $school_cards[$school_id][41]['count'] : '0',
                34=>isset($school_cards[$school_id][41]) ? $school_cards[$school_id][41]['fee'] :
                        (isset($school_fee[$school_id])&& isset($school_fee[$school_id][41]) ? $school_fee[$school_id][41] : $card_fee[41]),
                35=>isset($school_card_use_true[41])?$school_card_use_true[41] : '0',

                36=>isset($school_cards[$school_id][42]) ? $school_cards[$school_id][42]['count'] : '0',
                37=>isset($school_cards[$school_id][42]) ? $school_cards[$school_id][42]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][42]) ? $school_fee[$school_id][42] : $card_fee[42]),
                38=>isset($school_card_use_true[42])?$school_card_use_true[42] : '0',

                39=>isset($school_cards[$school_id][43]) ? $school_cards[$school_id][43]['count'] : '0',
                40=>isset($school_cards[$school_id][43]) ? $school_cards[$school_id][43]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][43]) ? $school_fee[$school_id][43] : $card_fee[43]),
                41=>isset($school_card_use_true[43])?$school_card_use_true[43] : '0',

                42=>isset($school_cards[$school_id][44]) ? $school_cards[$school_id][44]['count'] : '0',
                43=>isset($school_cards[$school_id][44]) ? $school_cards[$school_id][44]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][44]) ? $school_fee[$school_id][44] : $card_fee[44]),
                44=>isset($school_card_use_true[44])?$school_card_use_true[44] : '0',


                45=>isset($school_cards[$school_id][80]) ? $school_cards[$school_id][80]['count'] : '0',
                46=>isset($school_cards[$school_id][80]) ? $school_cards[$school_id][80]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][80]) ? $school_fee[$school_id][80] : $card_fee[80]),
                47=>isset($school_card_use_true[80])?$school_card_use_true[80] : '0',


                48=>isset($school_cards[$school_id][81]) ? $school_cards[$school_id][81]['count'] : '0',
//                48=> '-',
                49=>isset($school_cards[$school_id][81]) ? $school_cards[$school_id][81]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][81]) ? $school_fee[$school_id][81] : $card_fee[81]),
//                49=>'-',
                50=>isset($school_card_use_true[81])?$school_card_use_true[81] : '0',
//                50=> '-',

                51=>isset($school_cards[$school_id][60]) ? $school_cards[$school_id][60]['count'] : '0',
                52=>isset($school_cards[$school_id][60]) ? $school_cards[$school_id][60]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][60]) ? $school_fee[$school_id][60] : $card_fee[60]),
                53=>isset($school_card_use_true[60])?$school_card_use_true[60] : '0',

                54=>isset($school_cards[$school_id][61]) ? $school_cards[$school_id][61]['count'] : '0',
                55=>isset($school_cards[$school_id][61]) ? $school_cards[$school_id][61]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][61]) ? $school_fee[$school_id][61] : $card_fee[61]),
                56=>isset($school_card_use_true[61])?$school_card_use_true[61] : '0',

                57=>isset($school_cards[$school_id][45]) ? $school_cards[$school_id][45]['count'] : '0',
                58=>isset($school_cards[$school_id][45]) ? $school_cards[$school_id][45]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][45]) ? $school_fee[$school_id][45] : $card_fee[45]),
                59=>isset($school_card_use_true[45])?$school_card_use_true[45] : '0',

                60=>isset($school_cards[$school_id][46]) ? $school_cards[$school_id][46]['count'] : '0',
//                60=>'-',
//                61=>'-',
                61=>isset($school_cards[$school_id][46]) ? $school_cards[$school_id][46]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][46]) ? $school_fee[$school_id][46] : $card_fee[46]),
                62=>isset($school_card_use_true[46])?$school_card_use_true[46] : '0',
//                62=>'-',

                63=>isset($school_cards[$school_id][82]) ? $school_cards[$school_id][82]['count'] : '0',
                64=>isset($school_cards[$school_id][82]) ? $school_cards[$school_id][82]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][82]) ? $school_fee[$school_id][82] : $card_fee[82]),
                65=>isset($school_card_use_true[82])?$school_card_use_true[82] : '0',

                66=>isset($school_cards[$school_id][83]) ? $school_cards[$school_id][83]['count'] : '0',
//                66=> '-',
//                67=>'-',
                67=>isset($school_cards[$school_id][83]) ? $school_cards[$school_id][83]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][83]) ? $school_fee[$school_id][83] : $card_fee[83]),
//                68=>'-',
                68=>isset($school_card_use_true[83])?$school_card_use_true[83] : '0',

                69=>isset($school_cards[$school_id][58]) ? $school_cards[$school_id][58]['count'] : '0',
                70=>isset($school_cards[$school_id][58]) ? $school_cards[$school_id][58]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][58]) ? $school_fee[$school_id][58] : $card_fee[58]),
                71=>isset($school_card_use_true[58])?$school_card_use_true[58] : '0',


                72=>isset($school_cards[$school_id][86]) ? $school_cards[$school_id][86]['count'] : '0',
                73=>isset($school_cards[$school_id][86]) ? $school_cards[$school_id][86]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][86]) ? $school_fee[$school_id][86] : $card_fee[86]),
                74=>isset($school_card_use_true[86])?$school_card_use_true[86] : '0',

                75=>isset($school_cards[$school_id][84]) ? $school_cards[$school_id][84]['count'] : '0',
                76=>isset($school_cards[$school_id][84]) ? $school_cards[$school_id][84]['fee'] :
                    (isset($school_fee[$school_id])&& isset($school_fee[$school_id][84]) ? $school_fee[$school_id][84] : $card_fee[84]),
                77=>isset($school_card_use_true[84])?$school_card_use_true[84] : '0',

                78=>isset($school_cards[$school_id][85]) ? $school_cards[$school_id][85]['count'] : '0',
                79=>isset($school_cards[$school_id][85]) ? $school_cards[$school_id][85]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][85]) ? $school_fee[$school_id][85] : $card_fee[85]),
                80=>isset($school_card_use_true[85])?$school_card_use_true[85] : '0',

                81=>isset($school_cards[$school_id][87]) ? $school_cards[$school_id][87]['count'] : '0',
                82=>isset($school_cards[$school_id][87]) ? $school_cards[$school_id][87]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][87]) ? $school_fee[$school_id][87] : $card_fee[87]),
                83=>isset($school_card_use_true[87])?$school_card_use_true[87] : '0',

                84=>isset($school_cards[$school_id][88]) ? $school_cards[$school_id][88]['count'] : '0',
                85=>isset($school_cards[$school_id][88]) ? $school_cards[$school_id][88]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][88]) ? $school_fee[$school_id][88] : $card_fee[88]),
                86=>isset($school_card_use_true[88])?$school_card_use_true[88] : '0',

                87=>isset($school_cards[$school_id][89]) ? $school_cards[$school_id][89]['count'] : '0',
                88=>isset($school_cards[$school_id][89]) ? $school_cards[$school_id][89]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][89]) ? $school_fee[$school_id][89] : $card_fee[89]),
                89=>isset($school_card_use_true[89])?$school_card_use_true[89] : '0',

                90=>isset($school_cards[$school_id][90]) ? $school_cards[$school_id][90]['count'] : '0',
                91=>isset($school_cards[$school_id][90]) ? $school_cards[$school_id][90]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][90]) ? $school_fee[$school_id][90] : $card_fee[90]),
                92=>isset($school_card_use_true[90])?$school_card_use_true[90] : '0',

                93=>isset($school_cards[$school_id][91]) ? $school_cards[$school_id][91]['count'] : '0',
                94=>isset($school_cards[$school_id][91]) ? $school_cards[$school_id][91]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][91]) ? $school_fee[$school_id][91] : $card_fee[91]),
                95=>isset($school_card_use_true[91])?$school_card_use_true[91] : '0',



                96=>isset($school_cards[$school_id][92]) ? $school_cards[$school_id][92]['count'] : '0',
                97=>isset($school_cards[$school_id][92]) ? $school_cards[$school_id][92]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][92]) ? $school_fee[$school_id][92] : $card_fee[92]),
                98=>isset($school_card_use_true[92])?$school_card_use_true[92] : '0',

                99=>isset($school_cards[$school_id][93]) ? $school_cards[$school_id][93]['count'] : '0',
                100=>isset($school_cards[$school_id][93]) ? $school_cards[$school_id][93]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][93]) ? $school_fee[$school_id][93] : $card_fee[93]),
                101=>isset($school_card_use_true[93])?$school_card_use_true[93] : '0',


                102=>isset($school_cards[$school_id][94]) ? $school_cards[$school_id][94]['count'] : '0',
                103=>isset($school_cards[$school_id][94]) ? $school_cards[$school_id][94]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][94]) ? $school_fee[$school_id][94] : $card_fee[94]),
                104=>isset($school_card_use_true[94])?$school_card_use_true[94] : '0',

                105=>isset($school_cards[$school_id][95]) ? $school_cards[$school_id][95]['count'] : '0',
                106=>isset($school_cards[$school_id][95]) ? $school_cards[$school_id][95]['fee'] :
                    ( isset($school_fee[$school_id])&& isset($school_fee[$school_id][95]) ? $school_fee[$school_id][95] : $card_fee[95]),
                107=>isset($school_card_use_true[95])?$school_card_use_true[95] : '0',

            ];
        }

        $this->store('月数据_'.rand(0,100), $rep, '.xlsx');
        $this->store('月体验数据_'.rand(0,100), $rep_2, '.xlsx');


        dd('done....');

    }

    public function getSchoolLearnInfo($start_date, $date_total)
    {
        $school_card_use_info = [];

        $student_book_trail_info = [];

        $book_trail_info = [];
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
            $record_date = Carbon::parse($start_date)->addDays($i)->toDateString();
            $student_records = \DB::table('statistic_student_record')
                ->selectRaw('student_id, school_id,value as books')
                ->where('created_date', $record_date)
                ->where('school_id', '<>', 0)
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
                    if (isset($student_book_card_relation[$student_id])
                        && isset($student_book_card_relation[$student_id][$book_id])){
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
                    }else{
                        if (!isset($student_book_trail_info[$school_id])){
                            $student_book_trail_info[$school_id] = [];
                        }

                        if (!isset($student_book_trail_info[$school_id][$book_id])){
                            $student_book_trail_info[$school_id][$book_id] = [];
                        }
                        $student_book_trail_info[$school_id][$book_id][] = $student_id;
                        $book_trail_info[] = $book_id;
                    }
                }
                echo '=';
                usleep(100);
            }
            sleep(1);
            echo '>'.$record_date;

        }
//        \Log::info(array_unique($book_trail_info));
//        \Log::info($student_book_trail_info);
        return [
            'school_card_use_info' =>$school_card_use_info,
            'book_trail_info' => $book_trail_info,
            'student_book_trail_info' => $student_book_trail_info
        ];

    }


    public function getSchoolInfo()
    {

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

        return $school_info;
    }


    public function getCardFee()
    {
        $card_fee = \DB::table('card_prototype')
            ->selectRaw('id, pay_fee')
//            ->where('is_available', 1)
            ->get();

        $card_fee = $card_fee->pluck('pay_fee','id')->toArray();

        return $card_fee;
    }


    public function getSchoolFee()
    {
        $school_fee = \DB::table('card_prototype_school_customization')
            ->selectRaw('school_id, prototype_id ,value pay_fee ')
            ->where('key', 'card_price')
            ->get()
            ->groupBy('school_id')->map(function ($school){
                return $school->pluck('pay_fee', 'prototype_id')->toArray();
            })
            ->toArray();

        return $school_fee;
    }


    public function getSchoolCards($start_str,$end_str)
    {
        $sql = <<<EOF
SELECT
	school_id, prototype_id, price ,count(*) count
FROM
	`learning`.`card` 
WHERE
	`created_at` >= '$start_str' 
	AND `created_at` <= '$end_str' 
	AND `is_activated` = '1' 
-- 	AND `deleted_at` IS NULL 
GROUP BY 
school_id, prototype_id, price 
EOF;
        $school_baseinfo = \DB::select(\DB::raw($sql));

        $school_baseinfo = json_decode(json_encode($school_baseinfo),true);

        $school_info = collect($school_baseinfo)->groupBy('school_id')->map(function ($school){

            return $school->groupBy('prototype_id')->map(function ($prototype){

                return $prototype->pluck('count', 'price');
//                $count_str = '';
//                $fee_str = '';
//
//                foreach ($prototype as $item){
//
//                    $count_str .= '|'.$item['count'];
//                    $fee_str .= '|'.$item['price'];
//
//                }
//                $count_str = trim($count_str,'|');
//                $fee_str = trim($fee_str,'|');
//                return [
//                    'count' => $count_str,
//                    'fee' => $fee_str
//                ];
            });
        })->toArray();

        return $school_info;
    }

    public function getSchoolCardFee($start_str,$end_str)
    {
        $sql = <<<EOF
SELECT
	school_id, sum(price) fee
FROM
	`learning`.`card` 
WHERE
	`created_at` >= '$start_str' 
	AND `created_at` <= '$end_str' 
	AND `is_activated` = '1' 
GROUP BY 
school_id
EOF;
        $card_fee = \DB::select(\DB::raw($sql));

         return  array_combine(
            array_column($card_fee, 'school_id'),
            array_column($card_fee, 'fee')
        );

    }


    public function getSchoolRefundCard($start_str,$end_str)
    {
        $sql = <<<EOF
SELECT
	school_id, prototype_id, price ,count(*) count
FROM
	`learning`.`card` 
WHERE
	`deleted_at` >= '$start_str'
	AND `deleted_at` <= '$end_str' 
GROUP BY school_id, prototype_id, price 
ORDER BY school_id asc , prototype_id asc 
EOF;
        $school_baseinfo = \DB::select(\DB::raw($sql));

        $school_baseinfo = json_decode(json_encode($school_baseinfo),true);

        $school_info = collect($school_baseinfo)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('prototype_id')->map(function ($prototype){
                return $prototype->pluck('count', 'price');
            });
        })->toArray();

        return $school_info;


        
    }

    public function getSchoolRefundCardFee($start_str,$end_str)
    {
        $sql = <<<EOF
SELECT
	school_id, sum(price) fee
FROM
	`learning`.`card` 
WHERE
	`deleted_at` >= '$start_str'
	AND `deleted_at` <= '$end_str' 
GROUP BY 
school_id
EOF;
        $card_fee = \DB::select(\DB::raw($sql));

        return  array_combine(
            array_column($card_fee, 'school_id'),
            array_column($card_fee, 'fee')
        );
    }


    public function getSchoolLearnStudent($date_type)
    {
        $school_learn = \DB::table('statistic_school_record_monthly')
            ->selectRaw('school_id, learn_student')
            ->where('date_type', $date_type )
            ->get()->keyBy('school_id')
            ->pluck('learn_student','school_id')
            ->toArray();

        return $school_learn;

    }


    public function getSchoolYearCard($start_time_str, $end_time_str)
    {

        config(['database.default' => 'DCSJ_online']);
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$start_time_str)
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
	    AND rec.created_at <= '$end_time_str'
	    AND rec.created_at >= '$start_time_str'
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

        return $year_last_month;
    }


    public function getSchoolHalfYearCard($start_time_str, $end_time_str)
    {
        config(['database.default' => 'DCSJ_online']);
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$start_time_str)
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
	    AND rec.created_at <= '$end_time_str'
	    AND rec.created_at >= '$start_time_str'
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


        return $half_year_last_month;
    }


    public function getLearnStudentInfo($start_date,$end_date)
    {

        config(['database.default' => 'DCSJ_online']);

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

        return $DCSJ_learn_info;
    }

}