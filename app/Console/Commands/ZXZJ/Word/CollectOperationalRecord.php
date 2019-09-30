<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CollectOperationalRecord extends Command
{

    use Excel;

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


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        config(['database.default' => 'BXG_online']);

        $last_week = Carbon::now()->subWeek();
        $start_week = $last_week->startOfWeek()->toDateString();
        $start_week_str = $last_week->startOfWeek()->toDateTimeString();
        $end_week = $last_week->endOfWeek()->toDateString();
        $end_week_str = $last_week->endOfWeek()->toDateTimeString();

        // 这周天 统计本周数据
        $last_week = Carbon::now();
        $last_2_month_start = Carbon::parse('2019-07-01')->toDateString();
        $last_2_month_start_str = Carbon::parse('2019-07-01 00:00:00')->toDateTimeString();
        $last_2_month_end = Carbon::parse('2019-07-31')->toDateString();
        $last_2_month_end_str =  Carbon::parse('2019-07-31 23:59:59')->toDateTimeString();;

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
	left join school_attribute attribute on attribute.school_id = school.id and attribute.key = 'region_copy'
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
            'last_month_count' => '上月激活量',
            'last_week_count' => '上周激活量',
        ];

 ###################################################音标卡 (3M)####################################
        $export_yinbiao_data = [];
        // 截止到上周
        $yinbiao_total = $this->getYinbiaoCard('2019-01-01', $end_week);

        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);

        $yinbiao_end_last = collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();


        $yinbiao_total = $this->getYinbiaoCard($last_month_start, $last_month_end);

        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);

        $yinbiao_last_month = collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();


        $yinbiao_total = $this->getYinbiaoCard($start_week, $end_week);

        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);

        $yinbiao_last_week = collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();


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
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id`  = 3
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at <= '$end_week'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $region_tmp = $yinbiao_item->region;
            $region = explode('/', $region_tmp);
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
                'card_type'         => '暑假卡',
                'subject'           => '地理',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

            $school_database_info[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
            ];
        }

        // 上月的数据
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
     `prototype_id`  = 3
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$last_month_start'
    and activated_at <= '$last_month_end'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
        `prototype_id`  = 3
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$start_week'
    and activated_at <= '$end_week'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_last_week = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));


############################################# 暑假卡(历史) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id`  in (10,11,12,13,14,15,19,20)
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at <= '$end_week'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $region_tmp = $yinbiao_item->region;
            $region = explode('/', $region_tmp);
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
                'card_type'         => '暑假卡',
                'subject'           => '历史',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

            $school_database_info[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
            ];
        }

        // 上月的数据
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
      `prototype_id`  in (10,11,12,13,14,15,19,20)
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$last_month_start'
    and activated_at <= '$last_month_end'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
        `prototype_id`  in (10,11,12,13,14,15,19,20)
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$start_week'
    and activated_at <= '$end_week'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_last_week = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 暑假卡(生物) #############################################################

        $export_yinbiao_data = [];
        // 截止到上周
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
    `prototype_id`  in (4,5,6,16,17,18)
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at <= '$end_week'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $region_tmp = $yinbiao_item->region;
            $region = explode('/', $region_tmp);
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
                'card_type'         => '暑假卡',
                'subject'           => '生物',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];

            $school_database_info[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
            ];
        }

        // 上月的数据
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
      `prototype_id`  in (4,5,6,16,17,18)
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$last_month_start'
    and activated_at <= '$last_month_end'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

        // 上周的数据
        $sql = <<<EOF
SELECT
    base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
    (
SELECT
    school_id,
    count( * ) num_count,
    card_prototype.name  card_prototype_name
FROM
    `card`
    left join card_prototype on card_prototype.id = card.prototype_id
WHERE
        `prototype_id`  in (4,5,6,16,17,18)
    AND `card`.`deleted_at` IS NULL
    AND `is_activated` = 1
    AND `school_id` <> 1
    AND `student_id` <> 1751
    AND `expired_at` = '2019-08-31'
    and activated_at >= '$start_week'
    and activated_at <= '$end_week'
GROUP BY
    school_id
    ) base
    left join school on school.id = base.school_id
    left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
    left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
    left join user on marketer.value = user.id
EOF;

        $yinbiao_last_week = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 秋季卡(地理) #############################################################

$export_yinbiao_data = [];
// 截止到上周
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  = 3
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_total = \DB::select(\DB::raw($sql));


foreach ($yinbiao_total as $yinbiao_item){
$region_tmp = $yinbiao_item->region;
$region = explode('/', $region_tmp);
$export_yinbiao_data[$yinbiao_item->school_id] = [
'school_id'         => $yinbiao_item->school_id,
'school_name'       => $yinbiao_item->school_name,
"region_sheng"      => isset($region[0]) ? $region[0] : '',
"region_shi"        => isset($region[1]) ? $region[1] : '',
"region_qu"         => isset($region[2]) ? $region[2] : '',
"region_jiedao"     => isset($region[3]) ? $region[3] : '',
'marketer'          => $yinbiao_item->marketer_name,
'card_type'         => '秋季卡',
'subject'           => '地理',
'total_count'       => $yinbiao_item->num_count,
'last_month_count'  => '0',
'last_week_count'   => '0',
];
}

// 上月的数据
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  = 3
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at >= '$last_month_start'
and activated_at <= '$last_month_end'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_last_month = \DB::select(\DB::raw($sql));
foreach ($yinbiao_last_month as $yinbiao_last_month_item){
$school_id = $yinbiao_last_month_item->school_id;
$last_month_count = $yinbiao_last_month_item->num_count;
$export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
}

// 上周的数据
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  = 3
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at >= '$start_week'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_last_week = \DB::select(\DB::raw($sql));
foreach ($yinbiao_last_week as $yinbiao_last_week_item){
$school_id = $yinbiao_last_week_item->school_id;
$last_week_count = $yinbiao_last_week_item->num_count;
$export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
}
$export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

        ############################################# 秋季卡(不分科目) #############################################################
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
    AND card.`school_id` <> 1
    AND card.`student_id` <> 1751
    AND validity_days >= 60
    and activated_at <= '$end_week'
GROUP BY card.id
EOF;
        $yinbiao_total = \DB::select(\DB::raw($sql));
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);
        $qiu_end_last = collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();

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
    AND card.`school_id` <> 1
    AND card.`student_id` <> 1751
    AND validity_days >= 60
    and activated_at >= '$last_month_start'
    and activated_at <= '$last_month_end'
GROUP BY card.id
EOF;

        $yinbiao_total = \DB::select(\DB::raw($sql));
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);
        $qiu_last_month = collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();

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
    AND card.`school_id` <> 1
    AND card.`student_id` <> 1751
    AND validity_days >= 60
    and activated_at >= '$start_week'
    and activated_at <= '$end_week'
GROUP BY card.id
EOF;

        $yinbiao_total = \DB::select(\DB::raw($sql));
        $yinbiao_total = json_decode(json_encode($yinbiao_total), true);
        $qiu_last_week = collect($yinbiao_total)->groupBy('school_id')->map(function ($school){
            return $school->groupBy('subject_name')->map(function ($subject){
                return $subject->count();
            });
        })->toArray();


############################################# 秋季卡(历史) #############################################################

$export_yinbiao_data = [];
// 截止到上周
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  in (10,11,12,13,14,15,19,20)
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_total = \DB::select(\DB::raw($sql));


foreach ($yinbiao_total as $yinbiao_item){
    $school_id = $yinbiao_item->school_id;
    if (isset($qiu_end_last[$school_id])){
        if (isset($qiu_end_last[$school_id]['历史'])){
            $qiu_end_last[$school_id]['历史'] += $yinbiao_item->num_count;
        }else{
            $qiu_end_last[$school_id]['历史'] = $yinbiao_item->num_count;
        }
    }else{
        $qiu_end_last[$school_id] = [];
        $qiu_end_last[$school_id]['历史'] = $yinbiao_item->num_count;
    }
}

// 上月的数据
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name	
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  in (10,11,12,13,14,15,19,20)
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at >= '$last_month_start'
and activated_at <= '$last_month_end'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_last_month = \DB::select(\DB::raw($sql));
foreach ($yinbiao_last_month as $yinbiao_last_month_item){
    $school_id = $yinbiao_last_month_item->school_id;
    if (isset($qiu_last_month[$school_id])){
        if (isset($qiu_end_last[$school_id]['历史'])){
            $qiu_last_month[$school_id]['历史'] += $yinbiao_last_month_item->num_count;
        }else{
            $qiu_last_month[$school_id]['历史'] = $yinbiao_last_month_item->num_count;
        }
    }else{
        $qiu_last_month[$school_id] = [];
        $qiu_last_month[$school_id]['历史'] = $yinbiao_last_month_item->num_count;
    }
}
// 上周的数据
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  in (10,11,12,13,14,15,19,20)
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at >= '$start_week'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_last_week = \DB::select(\DB::raw($sql));
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
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  in (4,5,6,16,17,18)
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_total = \DB::select(\DB::raw($sql));


foreach ($yinbiao_total as $yinbiao_item){
$region_tmp = $yinbiao_item->region;
$region = explode('/', $region_tmp);
$export_yinbiao_data[$yinbiao_item->school_id] = [
'school_id'         => $yinbiao_item->school_id,
'school_name'       => $yinbiao_item->school_name,
"region_sheng"      => isset($region[0]) ? $region[0] : '',
"region_shi"        => isset($region[1]) ? $region[1] : '',
"region_qu"         => isset($region[2]) ? $region[2] : '',
"region_jiedao"     => isset($region[3]) ? $region[3] : '',
'marketer'          => $yinbiao_item->marketer_name,
'card_type'         => '秋季卡',
'subject'           => '生物',
'total_count'       => $yinbiao_item->num_count,
'last_month_count'  => '0',
'last_week_count'   => '0',
];
}

// 上月的数据
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  in (4,5,6,16,17,18)
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at >= '$last_month_start'
and activated_at <= '$last_month_end'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_last_month = \DB::select(\DB::raw($sql));
foreach ($yinbiao_last_month as $yinbiao_last_month_item){
$school_id = $yinbiao_last_month_item->school_id;
$last_month_count = $yinbiao_last_month_item->num_count;
$export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
}

// 上周的数据
$sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
SELECT
school_id,
count( * ) num_count,
card_prototype.name  card_prototype_name
FROM
`card`
left join card_prototype on card_prototype.id = card.prototype_id
WHERE
`prototype_id`  in (4,5,6,16,17,18)
AND `card`.`deleted_at` IS NULL
AND `is_activated` = 1
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` = '2020-01-17'
and activated_at >= '$start_week'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

$yinbiao_last_week = \DB::select(\DB::raw($sql));
foreach ($yinbiao_last_week as $yinbiao_last_week_item){
$school_id = $yinbiao_last_week_item->school_id;
$last_week_count = $yinbiao_last_week_item->num_count;
$export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
}
$export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 秋季卡 引流卡 (不分科目) #############################################################

        $export_yinbiao_data = [];
// 截止到上周
        $sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
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
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` < '2020-01-17'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

        $yinbiao_total = \DB::select(\DB::raw($sql));


        foreach ($yinbiao_total as $yinbiao_item){
            $region_tmp = $yinbiao_item->region;
            $region = explode('/', $region_tmp);
            $export_yinbiao_data[$yinbiao_item->school_id] = [
                'school_id'         => $yinbiao_item->school_id,
                'school_name'       => $yinbiao_item->school_name,
                "region_sheng"      => isset($region[0]) ? $region[0] : '',
                "region_shi"        => isset($region[1]) ? $region[1] : '',
                "region_qu"         => isset($region[2]) ? $region[2] : '',
                "region_jiedao"     => isset($region[3]) ? $region[3] : '',
                'marketer'          => $yinbiao_item->marketer_name,
                'card_type'         => '秋季卡引流卡',
                'subject'           => '',
                'total_count'       => $yinbiao_item->num_count,
                'last_month_count'  => '0',
                'last_week_count'   => '0',
            ];
        }

// 上月的数据
        $sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
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
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` < '2020-01-17'
and activated_at >= '$last_month_start'
and activated_at <= '$last_month_end'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

        $yinbiao_last_month = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_month as $yinbiao_last_month_item){
            $school_id = $yinbiao_last_month_item->school_id;
            $last_month_count = $yinbiao_last_month_item->num_count;
            $export_yinbiao_data[$school_id]['last_month_count'] = $last_month_count;
        }

// 上周的数据
        $sql = <<<EOF
SELECT
base.* ,school.name school_name, attribute.value region,`user`.`name` marketer_name
FROM
(
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
AND `school_id` <> 1
AND `student_id` <> 1751
AND `expired_at` < '2020-01-17'
and activated_at >= '$start_week'
and activated_at <= '$end_week'
GROUP BY
school_id
) base
left join school on school.id = base.school_id
left join school_attribute attribute on attribute.school_id = base.school_id and attribute.`key` = 'region_copy'
left join school_attribute marketer on marketer.school_id = base.school_id and marketer.`key` = 'marketer_id'
left join user on marketer.value = user.id
EOF;

        $yinbiao_last_week = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }
        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));

############################################# 单词卡 (年卡) #############################################################

        config(['database.default' => 'DCSJ_online']);

        $export_yinbiao_data = [];
// 截止到上周
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
	    AND rec.created_at <= '$end_week_str'
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

//        dd($yinbiao_total);

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
                    'last_month_count'  => '0',
                    'last_week_count'   => '0',
                ];
            }else{
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => $relation_id,
                    'school_name'       =>  $school_database_info[$relation_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$relation_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$relation_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$relation_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$relation_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$relation_id]['marketer'],
                    'card_type'         => '单词卡年卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'last_month_count'  => '0',
                    'last_week_count'   => '0',
                ];
            }

        }

        // 上月的数据
        // 获得上个月之前已有的学生id

        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$last_month_start_str)
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
	    AND rec.created_at <= '$last_month_end_str'
	    AND rec.created_at >= '$last_month_start_str'
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

// 上周的数据
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$start_week_str)
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
	    AND rec.created_at <= '$end_week_str'
	    AND rec.created_at >= '$start_week_str'
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

        $yinbiao_last_week = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }


        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));



        ############################################# 单词卡 (半年卡) #############################################################

        config(['database.default' => 'DCSJ_online']);

        $export_yinbiao_data = [];
// 截止到上周
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
	    AND rec.created_at <= '$end_week_str'
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
                    'last_month_count'  => '0',
                    'last_week_count'   => '0',
                ];
            }else{
                $export_yinbiao_data[$yinbiao_item->school_id] = [
                    'school_id'         => $relation_id,
                    'school_name'       => $school_database_info[$relation_id]['school_name'],
                    "region_sheng"      =>  $school_database_info[$relation_id]['region_sheng'],
                    "region_shi"        =>  $school_database_info[$relation_id]['region_shi'],
                    "region_qu"         =>  $school_database_info[$relation_id]['region_qu'],
                    "region_jiedao"     =>  $school_database_info[$relation_id]['region_jiedao'],
                    'marketer'          =>  $school_database_info[$relation_id]['marketer'],
                    'card_type'         => '单词卡半年卡(单词速记)',
                    'subject'           => '英语',
                    'total_count'       => $yinbiao_item->num_count,
                    'last_month_count'  => '0',
                    'last_week_count'   => '0',
                ];
            }

        }

        // 上月的数据
        // 获得上个月之前已有的学生id

        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$last_month_start_str)
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
	    AND rec.created_at <= '$last_month_end_str'
	    AND rec.created_at >= '$last_month_start_str'
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

// 上周的数据
        $student_info = \DB::table('user_courses')
            ->selectRaw('distinct user_id')
            ->where('created_at', '<=',$start_week_str)
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
	    AND rec.created_at <= '$end_week_str'
	    AND rec.created_at >= '$start_week_str'
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

        $yinbiao_last_week = \DB::select(\DB::raw($sql));
        foreach ($yinbiao_last_week as $yinbiao_last_week_item){
            $school_id = $yinbiao_last_week_item->school_id;
            $last_week_count = $yinbiao_last_week_item->num_count;
            $export_yinbiao_data[$school_id]['last_week_count'] = $last_week_count;
        }


        $export_card_data = array_merge($export_card_data, array_values($export_yinbiao_data));



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
	LEFT JOIN school_attribute attribute on attribute.school_id = school.id and attribute.key = 'region_copy'
	LEFT JOIN (
				SELECT
				school_id,
				sum(if(type='received',fee,0 ))  received,
				sum(if(type='student_order',fee,0 )) student_order,
				sum(if(type='payment',fee,0 )) payment
			FROM
				`finance_school_statement`
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
    AND `school_id` not in (1,2000,2010,2033)
    AND `student_id` <> 1751
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
    AND `school_id` not in (1,2000,2010,2033)
    AND `student_id` <> 1751
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
    AND `school_id` not in (1,2000,2010,2033)
    AND `student_id` <> 1751
    AND `expired_at`   $relation_info
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return  \DB::select(\DB::raw($sql));
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
    AND `school_id` not in (1,2000,2010,2033)
    AND `student_id` <> 1751
    AND validity_days $relation_info
    and activated_at >= '$start'
    and activated_at <= '$end'
GROUP BY
    school_id
EOF;

        return \DB::select(\DB::raw($sql));
    }
}
