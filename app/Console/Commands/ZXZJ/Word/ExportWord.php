<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportWord extends Command
{

    use PdoBuilder;
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:word {pdo=local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import word and translation by upload excel';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    private $filePath; //文件路径数组
    protected function traverse($path = '.')
    {
        $current_dir = opendir($path);    //opendir()返回一个目录句柄,失败返回false
        while (($file = readdir($current_dir)) !== false) {    //readdir()返回打开目录句柄中的一个条目
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file;    //构建子目录路径
            if ($file == '.' || $file == '..') {
                continue;
            } else if (is_dir($sub_dir)) {    //如果是目录,进行递归
                $this->traverse($sub_dir); //嵌套遍历子文件夹
            } else {    //如果是文件,直接输出路径和文件名
                $path_tmp = str_replace('/home/vagrant/code/sql_analyze/storage/imports', '',$path);
                $this->filePath[] = $path_tmp . '/' . $file;//把文件路径赋值给数组
            }
        }
        return $this->filePath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $path = '/home/vagrant/code/sql_analyze/storage/imports';
        $this->traverse($path);

        $this->output->progressStart(count($this->filePath));

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getConnPdo('core', $pdo_type);

        $save_data = [];
        foreach ($this->filePath as  $file) {
            if (strpos($file, '.gitignore') || strpos($file, 'abc')) {
                $this->output->progressAdvance();
                continue;
            }
            \Log::info($file);
            $contents = $this->import($file);
            $header = array_shift($contents);
            $header = array_filter($header);
            $key_tran = array_flip($header);
            ###检查文件####
            #$save_count = 0;
            #$empty_count = 0;
            ##############
            foreach ($contents as $content) {
                ###检查文件####
//                if(empty($content[$key_tran['单词']])&& empty($content[$key_tran['词性1']])
//                    && empty($content[$key_tran['解释1']]) && empty($content[$key_tran['最小标签的ID']])){
//                    $empty_count++;
//                    if ($save_count<3&&$empty_count>5){
//                        \Log::info($file);
//                        break;
//                    }
//                    continue;
//                }
//                $save_count++;
                ##############

                if(empty($content[$key_tran['单词']])&& empty($content[$key_tran['词性1']])
                    && empty($content[$key_tran['解释1']]) && empty($content[$key_tran['最小标签的ID']])){
                    continue;
                }
                $word = trim($content[$key_tran['单词']]);
                $speed_1 = $this->handleSpeed($content[$key_tran['词性1']]);
                $trans_1 = $this->handleTranslation($content[$key_tran['解释1']]);
                $label_id = trim($content[$key_tran['最小标签的ID']]);


                if (!isset($save_data[$word])){
                    $save_data[$word] = [];
                }
                if (!isset($save_data[$word][$speed_1])){
                    $save_data[$word][$speed_1] = [];
                }
                if (!isset($save_data[$word][$speed_1][$trans_1])){
                    $this->handleTrans($save_data[$word][$speed_1], $trans_1);
//                    $save_data[$word][$speed_1][$trans_1] = [];
                }
                $save_data[$word][$speed_1][$trans_1][] = $label_id;


                if (isset($key_tran['词性2'])&&isset($key_tran['解释2'])&&!empty($content[$key_tran['词性2']]) && empty($content[$key_tran['解释2']])){
                    $speed_2 = $this->handleSpeed($content[$key_tran['词性2']]);
                    $trans_2 = $this->handleTranslation($content[$key_tran['解释2']]);

                    if (!isset($save_data[$word][$speed_2])){
                        $save_data[$word][$speed_2] = [];
                    }
                    if (!isset($save_data[$word][$speed_2][$trans_2])){
                        $this->handleTrans($save_data[$word][$speed_2], $trans_2);
//                        $save_data[$word][$speed_2][$trans_2] = [];
                    }
                    $save_data[$word][$speed_2][$trans_2][] = $label_id;

                }
            }
            $this->output->progressAdvance();
        }


        // 获取 库里的 数据
        $vocabulary = array_keys($save_data);
        $words_str = str_repeat("?,", count($vocabulary)-1) . "?";;
        // 获得所有单词的例句解释
        $sql = 'select  `vocabulary`,`wordbank_id`,`sentence` , `explain` from `wordbank_sentence` 
                INNER  JOIN  `wordbank` ON `wordbank`.`id` = `wordbank_sentence`.`wordbank_id`
                where `vocabulary` in ('.$words_str.') and `wordbank_sentence`.`deleted_at` is null 
                and `wordbank`.`deleted_at` is null';
        $res  = $pdo->prepare($sql);
        $res->execute($vocabulary);
        $word_sentence_list = $res->fetchAll(\PDO::FETCH_ASSOC  );
        $word_sentence_list = collect($word_sentence_list)->keyBy('vocabulary')->toArray();

        // 获取所有的单词
        $sql = 'select  `vocabulary`,`id` from `wordbank` where `vocabulary` in ('.$words_str.') and `wordbank`.`deleted_at` is null';
        $res  = $pdo->prepare($sql);
        $res->execute($vocabulary);
        $word_list = $res->fetchAll(\PDO::FETCH_ASSOC  );
        $word_list = collect($word_list)->pluck('id','vocabulary')->toArray();

        // 拼接数据
        $export_data = [];
        $export_data[] = ['vocabulary', 'part_of_speech', 'translation',  'sentence', 'explain', 'word_id','label_ids'];
        foreach ($save_data as $word=>$speeds){
            $is_first = true;
            $had_word =     isset($word_list[$word])? 1 : 0;
            $had_sentence = isset($word_sentence_list[$word])? 1 : 0;
            foreach ($speeds as $speed=>$trans){
                foreach ($trans as $tran=>$labels){
                    if (!$had_word){
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            '',
                            '',
                            '单词未录入',
                            implode(',', $labels)
                        ];
                        continue;
                    }
                    if (!$had_sentence){
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            '单词未上传例句',
                            '单词未上传例句',
                            $word_list[$word],
                            implode(',', $labels)
                        ];
                        continue;
                    }
                    if ($is_first){
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            $word_sentence_list[$word]['sentence'],
                            $word_sentence_list[$word]['explain'],
                            $word_list[$word],
                            implode(',', $labels)
                        ];
                        $is_first = false;
                    }else{
                        $export_data[] = [
                            $word,
                            $speed,
                            $tran,
                            '',
                            '',
                            $word_list[$word],
                            implode(',', $labels)
                        ];
                    }
                }
            }
        }

        // 保存到文件
        $this->store('单词例句解释'.rand(0,100), $export_data, '.xlsx');
        $this->output->progressFinish();
    }

    // 处理词性
    public function handleSpeed($speed)
    {
        $speed = str_replace(chr(194).chr(160), ' ',$speed);
        $speed = trim($speed);
        if (strpos($speed, '.')===false){
            $speed = $speed.'.';
        }
        return $speed;
    }

    // 处理解释
    public function handleTranslation($translation)
    {
        $translation = trim($translation);

        $trans1 = [
            ";"     => "；",
            "，"    => "；",
            ","     => "；",
            "："     => "；",

            "..."   => "......",
            "……"    => "......",
            "⋯⋯"    => "......",


            "("    => "（",
            ")"    => "）",



            "0"     => '零',
            "—"     => "一",
            "1"     => "一",
            "2"     => "二",
            "3"     => "三",
            "4"     => "四",
            "5"     => "五",
            "6"     => "六",
            "7"     => "七",
            "8"     => "八",
            "9"     => "九",
            "10"     => "十",
            "11"     => "十一",
            "12"     => "十二",
            "13"     => "十三",
            "14"     => "十四",
            "15"     => "十五",
            "16"     => "十六",
            "17"     => "十七",
            "18"     => "十八",
            "19"     => "十九",
            "20"     => "二十",
            "21"     => "二十一",
            "30"     => "三十",
            "40"     => "四十",
            "50"     => "五十",
            "60"     => "六十",
            "70"     => "七十",
            "80"     => "八十",
            "90"     => "九十",
            "100"    => "一百",

            '周一'		=> '星期一',
            '周二'		=> '星期二',
            '周三'		=> '星期三',
            '周四'		=> '星期四',
            '周五'		=> '星期五',
            '周六'		=> '星期六',
            '星期天'		=> '星期日',
            '周日'		=> '星期日',

        ];


        $translation = strtr($translation, $trans1);

        return $translation;
    }

    // 处理解释
    public function handleTrans(&$list, &$item)
    {
        if (count($list)){
            $is_did = false;
            // 已存在
            $trans_list = array_keys($list);
            foreach ($trans_list as $old_tran){
                $old_tran_list = explode('；', $old_tran);
                $item_list = explode('；', $item);
                if (count($old_tran_list) > 1 && count($item_list) > 1){
                    // 两个相等
                    if (count($old_tran_list) == count(array_intersect($old_tran_list, $item_list))){
                        $item = $old_tran;
                        $is_did = true;
                        break;
                    }
                    // old 大
                    if (count(array_diff($old_tran_list,$item_list)) && !count(array_diff($item_list,$old_tran_list))){
                        $item = $old_tran;
                        $is_did = true;
                        break;
                    }

                    // item 大
                    if (!count(array_diff($old_tran_list,$item_list)) && count(array_diff($item_list,$old_tran_list))){
                        $tmp = $list[$old_tran];
                        $list[$item] = $tmp;
                        unset($list[$old_tran]);

                        $is_did = true;
                        break;
                    }

                    // 互不覆盖
                }


                if (count($old_tran_list) == 1  && count($item_list) > 1){
                    if (in_array($old_tran, $item_list)){
                        if (!isset($list[$item])){
                            $list[$item] = [];
                        }
                        $list[$item] = array_merge($list[$item], $list[$old_tran]);
                        unset($list[$old_tran]);
                    }
                    $is_did = true;
                    continue;
                }

                if (count($old_tran_list) > 1 && count($item_list) == 1){
                    if (in_array($item, $old_tran_list)){
                        $item = $old_tran;
                        $is_did = true;
                        break;
                    }
                }

                if (count($old_tran_list) == 1 && count($item_list) == 1){
                    if($old_tran == $item){
                        $is_did = true;
                        break;
                    }
                }
            }

            if (!$is_did){
                $list[$item] = [];
            }
        }else{
            $list[$item] = [];
        }
    }
}
