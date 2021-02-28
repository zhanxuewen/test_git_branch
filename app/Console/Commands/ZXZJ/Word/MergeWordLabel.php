<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MergeWordLabel extends Command
{

    use PdoBuilder;
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merge:word:label';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '合并单词的标签';


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


    public function getLabelLink($label)
    {
        $link = [];
        $label_info = \DB::table('label')
            ->where('id', $label)
            ->first();
        if (empty($label_info)) return $label;
        $link[] = $label_info->name;
        while($label_info->level != 1){
            $label_info = \DB::table('label')
                ->where('id', $label_info->parent_id)
                ->first();
            $link[] = $label_info->name;
        }
        $arr = array_reverse($link);
        return implode('->', $arr);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        config(['database.default' => 'local']);
      // SELECT * FROM `word`.`wordbank_2` WHERE `id` IN (133,134,183,183,184,184,418,418,419,419,427,428,429,500,500,501,501,601,602,603,604,605,606,607,608,609,610,611,612,613,614,615,616,617,618,619,620,621,622,623,624,625,626,627,628,629,630,631)
        $sql = <<<EOF

select * from 
(
select 

wordbank.id, wordbank.vocabulary , wordbank_translation.id translation_id, wordbank_translation.part_of_speech , wordbank_translation.translation,
wordbank_sentence.sentence , wordbank_sentence.`explain` 


from  wordbank 
left join wordbank_translation on wordbank_translation.wordbank_id = wordbank.id and wordbank_translation.deleted_at is NULL
left join wordbank_sentence on wordbank_sentence.translation_id = wordbank_translation.id and wordbank_sentence.deleted_at is NULL
where  wordbank.vocabulary in ( 'contrast','deal','kid','late','ring','equal','toward','blame','cure','reserve','tense','ruin','suspect','scale','switch','brake','track','broke','chief' )
) compare 
left join 
(

select translation_id, GROUP_CONCAT(DISTINCT label_id ORDER BY  label_id)  label_ids from wordbank_translation_label where translation_id in (2088,2108,66,3371,111,113,6815,2157,2160,2166,2175,2179,3422,3444,10191,184,193,197,3458,212,2236,9317,223,234,250,2248,255,271,3493,5583,294,4457,3505,3511,3527,4473,322,2280,332,3538,351,360,368,365,4482,4488,11983,4500,3546,2297,4516,3555,9079,3560,377,381,384,3565,393,397,405,3575,413,2309,423,2314,2320,436,437,448,2327,17194,452,459,3626,3645,3655,12284,494,3679,498,3682,504,3684,511,518,522,528,550,9216,559,564,8624,571,572,578,587,2357,2382,616,625,3748,638,643,651,4719,3768,700,8734,707,711,3772,720,2420,732,2424,739,746,3776,2442,757,3784,760,3786,766,781,3794,785,4765,796,803,5903,812,3809,835,837,842,8685,866,874,2498,890,901,931,4813,3842,960,4832,2527,4862,3874,1009,1013,2551,17260,1020,1043,1050,1056,1065,9365,1072,1074,9334,1088,1099,1102,1107,1109,4892,8658,1144,1152,2584,4961,3929,2667,1317,1364,2798,1454,1460,2886,1504,1509,17198,1519,2893,1527,1530,1539,11936,1543,1555,1560,14118,1569,2924,2929,6346,1581,2944,2946,4199,1598,1608,9443,4210,6414,1644,4214,3012,3016,6469,3020,3041,1699,3047,3054,4228,1711,1717,3059,3069,1876,3167,1967,2002,2368,3154,3436,3586,6269,6539,7326,8011,10254,4257,4419,8055,13998,3521)
GROUP BY translation_id
) translation_label_map on translation_label_map.translation_id = compare.translation_id

EOF;

        $word_info = \DB::select(\DB::raw($sql));

        $return = [];
        $return[] = [
            'id'=>'单词id',
            'vocabulary' => '单词',
            'translation_id' => '解释id',
            'part_of_speech' => '词性',
            'translation' => '解释',
            'sentence' => '句子',
            'explain' => '翻译',
//            'word_id' => '单词',
            'label_id' => '标签',
            'link' => '标签链',
        ];
        foreach ($word_info as $word_item){

            $excel_label_ids = $word_item->label_ids;
            $excel_label_ids = str_replace('，', ',', $excel_label_ids);
            $excel_label_ids = explode(',',$excel_label_ids);
            $excel_label_ids = array_unique($excel_label_ids);
            sort($excel_label_ids );
            foreach ($excel_label_ids as $label_id ){
                $return[] = [
                    'id'=>$word_item->id,
                    'vocabulary' => $word_item->vocabulary,
                    'translation_id' => $word_item->translation_id,
                    'part_of_speech' => $word_item->part_of_speech,
                    'translation' => $word_item->translation,
                    'sentence' => $word_item->sentence,
                    'explain' => $word_item->explain,
                    'label_id' => $label_id,
                    'link' => $this->getLabelLink($label_id),
                ];
                echo '+';
            }
        }
        $this->store('一词多义数据库单词'.rand(0,100), $return, '.xlsx');
        dd($return);




        $path = storage_path('imports');
        $this->traverse($path);

        $word_arr1 = [];
        $word_arr2 = [];
        config(['database.default' => 'local']);
        foreach ($this->filePath as  $key=>$file) {
            $file_tmp = str_replace($path, '', $file);
            $contents = $this->import($file_tmp);
            array_shift($contents);
//dd($this->filePath);

            foreach ($contents as $content){
                if ($key == 0){
//                    \DB::table('wordbank_2')->insert([
//                        'vocabulary'=>$content[0],
//                        'part_of_speech'=>$content[1],
//                        'translation'=>$content[2],
//                        'sentence'=>$content[3],
//                        'explain'=>$content[4],
//                        'word_id'=>$content[5],
//                        'label_ids'=>$content[6],
//                    ]);
                }else if($key == 2){
//                    \DB::table('wordbank_1')->insert([
//                        'vocabulary'=>$content[0],
//                        'part_of_speech'=>$content[1],
//                        'translation'=>$content[2],
//                        'sentence'=>$content[3],
//                        'explain'=>$content[4],
//                        'word_id'=>$content[5],
//                        'label_ids'=>$content[6],
//                    ]);
                }else{
                    $excel_label_ids = $content[1];
                    $excel_label_ids = str_replace('，', ',', $excel_label_ids);
                    $excel_label_ids = explode(',',$excel_label_ids);
                    $excel_label_ids = array_unique($excel_label_ids);
                    sort($excel_label_ids );

                    \DB::table('wordbank_3')->insert([
                        'excel_id'=>$content[0],
                        'translation_id'=>$content[2],
                        'label_ids'=>$content[3],
                        'excel_label_ids'=>implode(',', $excel_label_ids),
                        'exception'=>implode(',',array_diff($excel_label_ids, explode(',',$content[3]))),
                    ]);
                }

                echo '+';
            }

            continue;

            dd($contents);

            if (strpos($file, '单词例句解释') !== false){
                $word_arr1 = $contents;
            }

            if (strpos($file, '1词多义-修改版') !== false){
                $word_arr2 = $contents;
            }

        }

        dd('done');

        $this->output->progressStart(count($word_arr1));
        foreach ($word_arr1 as $word_info){

            foreach ($word_arr2 as $key=>$value){
                if (trim($value[0]) == trim($word_info[0])){
                    $cixing_arr = explode('/', $value[1]);
                    if (in_array($word_info[1],$cixing_arr)){
                        is_string($word_info[6]) ? null : $word_info[6] = ''.intval($word_info[6]);
                        is_string($value[6]) ? null : $value[6] = ''.intval($value[6]);
                        $word_arr2[$key][6] =  $value[6].','.$word_info[6];
                    }
                }
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();

        $this->store('一词多义'.rand(0,100), $word_arr2, '.xlsx');
    }

}
