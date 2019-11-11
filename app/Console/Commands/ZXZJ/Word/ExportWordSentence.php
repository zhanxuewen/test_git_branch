<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportWordSentence extends Command
{

    use PdoBuilder;
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:word:sentence {pdo=local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import word and Sentence by upload excel';


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
    private $word_arr = [];
    private $other_labels = [];
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
        $save_time = Carbon::now()->toDateTimeString();
        $path = storage_path('imports');
        $this->traverse($path);

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getConnPdo('core', $pdo_type);


        // 处理文件
        foreach ($this->filePath as  $file) {
            $file_tmp = str_replace('/var/www/html/sql_analyze/storage/imports/', '', $file);
            $contents = $this->import($file_tmp);
            $header = array_shift($contents);
            $header = array_filter($header);
            $key_tran = array_flip($header);

            $this->info($file);

            if ('0word_labes.xlsx' ==  $file_tmp){
                foreach ($contents as $item){
                    $this->other_labels[$item[0]] = (string) $item[1];
                }
                continue;
            }

            // 获取所有的单词
            $words = array_column($contents,0);
            $words_str = str_repeat("?,", count($words)-1) . "?";;
            $sql = 'select `id` , `vocabulary` from `wordbank` where `vocabulary` in ('.$words_str.') and deleted_at is null';
            $res  = $pdo->prepare($sql);
            $res->execute($words);
            $word_list = $res->fetchAll(\PDO::FETCH_ASSOC  );
            $word_info_arr = array_combine(array_column($word_list, 'vocabulary'), array_column($word_list, 'id'));


            // excel 文件 单词 分组   处理分组
            $contents_chunk = array_chunk($contents, 100);
            $this->output->progressStart(count($contents_chunk));
            foreach ($contents_chunk as $contents_chunk_item){

//                // 获取所有的单词解释
//                $contents_chunk_item_word_ids = array_column($contents_chunk_item, 5);
//                $word_ids_str = str_repeat("?,", count($contents_chunk_item_word_ids)-1) . "?";;
//                $sql = 'select `id`,`wordbank_id` , `part_of_speech`, `translation`  from `wordbank_translation` where `wordbank_id` in ('.$word_ids_str.') and deleted_at is null';
//                $res  = $pdo->prepare($sql);
//                $res->execute($contents_chunk_item_word_ids);
//                $word_translation_list = $res->fetchAll(\PDO::FETCH_ASSOC  );
//
//                $word_translation_arr = collect($word_translation_list)->groupBy('wordbank_id')->map(function ($wordbank){
//                    return $wordbank->groupBy('part_of_speech')->map(function ($translation){
//                        $return_data = [];
//                        foreach ($translation as $translation_item){
//                            $return_data[] = $translation_item['id'].'##'.$translation_item['translation'];
//                        }
//                        return $return_data;
//                    });
//                })->toArray();

                // 处理 每个记录
                foreach ($contents_chunk_item as $content) {
                    $vocabulary = $content[$key_tran['vocabulary']];
                    $part_of_speech = $content[$key_tran['part_of_speech']];
                    $translation = $content[$key_tran['translation']];
                    $sentence = $content[$key_tran['sentence']];
                    $explain = $content[$key_tran['explain']];
                    $word_id = $content[$key_tran['word_id']];
                    $label_ids = $content[$key_tran['label_ids']];

                    is_string($label_ids) ?  null : $label_ids = ''.intval($label_ids);
                    isset($this->other_labels[$vocabulary]) ? $label_ids = $label_ids.','.$this->other_labels[$vocabulary] : null;

                    if(empty($vocabulary)&& empty($part_of_speech)
                        && empty($translation) && empty($sentence)
                        && empty($explain) && empty($word_id) && empty($label_ids)
                    ){
                        continue;
                    }

                    $is_new_word = false;
                    $is_new_translation = false;
                    // 查找单词
                    if (isset($word_info_arr[$vocabulary])) {
                        $word_id = $word_info_arr[$vocabulary];
                    }else{
                        $vocabulary = str_replace("'" ,"\'",$vocabulary);
                        $vocabulary = str_replace('"' ,"\"",$vocabulary);
                        $sql = "select * from `wordbank` WHERE `vocabulary` = '".$vocabulary."' and `deleted_at` is  null";
                        $word_info2 = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
                        if (empty($word_info2)){
                            $word_id = $this->createWord($pdo, $vocabulary,$save_time);
                            $is_new_word = true;
                        }else{
                            $word_id = $word_info2['id'];
                        }
                    }

                    // 查找单词的词性解释
//                    $not_find_word = true;
//                    if (isset($word_translation_arr[$word_id])){
//                        if(isset($word_translation_arr[$word_id][$part_of_speech])){
//                            foreach ($word_translation_arr[$word_id][$part_of_speech] as $translation_item){
//                                $translation_item_arr = explode('##', $translation_item);
//                                if ($translation_item_arr[1] == $translation){
//                                    $translation_id = $translation_item_arr[0];
//                                }
//                            }
//                        }
//                        $not_find_word = false;
//                    }

//                    if (!isset($translation_id)){
//                        if ($not_find_word){
                            $sql = "select * from `wordbank_translation` WHERE `wordbank_id` = $word_id and  `part_of_speech` = '".$part_of_speech.
                                "' and `translation` = '". $translation . "' and `deleted_at` is  null ";
                            $translation_info1 = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
                            if (empty($translation_info1)){
                                $translation_id = $this->createTranslation($pdo,$word_id, $part_of_speech, $translation,$save_time);
                                $is_new_translation = true;
                            }else{
                                $translation_id = $translation_info1['id'];
                            }
//                        }else{
//                            $translation_id = $this->createTranslation($pdo,$word_id, $part_of_speech, $translation,$save_time);
//                            $is_new_translation = true;
//                        }
//                    }

                    // 处理句子
                    $sql = "select * from `wordbank_sentence` WHERE `translation_id` = $translation_id and `deleted_at` is  null ";
                    $sentence_list = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                    $need_create_sentence = true;
                    $need_delete_sentence = [];
                    foreach ($sentence_list as  $sentence_item){
                        if ($sentence_item['sentence'] == $sentence && $sentence_item['explain'] == $explain){
                            $sentence_id = $sentence_item['id'];
                            $need_create_sentence = false;
                        }else{
                            $need_delete_sentence[] = $sentence_item['id'];
                        }
                    }

                    if ($need_create_sentence){
                        $sentence_id = $this->createSentence($pdo,$translation_id, $sentence, $explain,$save_time);
                    }
                    if (count($need_delete_sentence)){
                        $delete_ids_str = implode(',', $need_delete_sentence);
                        $sql = "UPDATE `wordbank_sentence` SET `deleted_at` = '".$save_time."' WHERE `id` IN ($delete_ids_str)";
                        $res=$pdo->exec($sql);
                        if ($res != count($need_delete_sentence)){
                            dd($sql, '删除单词例句失败');
                        }
                    }

                    // 处理解释 与 标签的关系
                    if ( $is_new_word||$is_new_translation){ // 是新的 直接添加
                        $this->createTranslationLabel($pdo,$word_id,$translation_id,$label_ids,$save_time);
                    }else{
                        // 查找原有的数据
                        $sql = "select * from `wordbank_translation_label` WHERE `wordbank_id` = $word_id  and `label_id` in ($label_ids) ";
                        $trans_label_info = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                        if (count($trans_label_info)){
                            $delete_ids = array_column($trans_label_info, 'id');
                            $delete_ids_str = implode(',', $delete_ids);
                            $sql="delete from `wordbank_translation_label` where `id` in ($delete_ids_str)";
                            $res=$pdo->exec($sql);
                            if ($res != count($delete_ids)){
                                dd($sql, '删除解释标签失败');
                            }
                        }
                        $this->createTranslationLabel($pdo,$word_id,$translation_id,$label_ids,$save_time);
                    }
                }

                unset($translation_id);

                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        }
    }


    protected function createWord($pdo, $vocabulary,$save_time)
    {
        $initial = substr(ucfirst($vocabulary), 0, 1);
        $sql = "INSERT INTO `wordbank` (`vocabulary`, `initial`, `created_at`, `updated_at`) VALUES ('". $vocabulary."','". $initial."','".$save_time."','". $save_time."')";
        $res = $pdo->exec($sql);
        if ($res){
            $word_id = $pdo->lastInsertId();
        }else{
            dd($sql, '插入新单词失败');
        }
        return $word_id;
    }

    protected function createTranslation($pdo,$word_id, $part_of_speech, $translation,$save_time)
    {
        // 查找已经有几个单词了
        $sql = "select * from `wordbank_translation` WHERE `wordbank_id` = $word_id";
        $translation_list = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $count = count($translation_list);

        $sql = "INSERT INTO `wordbank_translation` (`wordbank_id`, `part_of_speech`, `translation`, `power`, `created_at`, `updated_at`) VALUES ('".
            $word_id."','". $part_of_speech."','".$translation."','". (++$count) ."','".$save_time."','". $save_time."')";
        $res = $pdo->exec($sql);
        if ($res){
            $translation_id = $pdo->lastInsertId();
        }else{
            dd($sql, '插入单词解释失败');
        }
        return $translation_id;
    }

    protected function createSentence($pdo,$translation_id, $sentence, $explain,$save_time)
    {
        $sentence = str_replace("'" ,"\'",$sentence);
        $sentence = str_replace('"' ,'\"',$sentence);

        $explain = str_replace("'" ,"\'",$explain);
        $explain = str_replace('"' ,'\"',$explain);
        $sql = "INSERT INTO `wordbank_sentence` (`translation_id`, `teacher_id`, `sentence`, `explain`, `created_at`, `updated_at`) VALUES (".'"'.
            $translation_id.'","'. $this->getTeacherId() .'","'.$sentence.'","'. $explain .'","'.$save_time.'","'. $save_time.'")';
        $res = $pdo->exec($sql);
        if ($res){
            $sentence_id = $pdo->lastInsertId();
        }else{
            dd($sql, '插入单词例句失败');
        }
        return $sentence_id;
    }

    protected function getTeacherId()
    {
        $key = rand(0,11);
        $values = [
            53,84,87,95,1727,1728,1729,1730,1731,1732,3204,3205
        ];
        //获取市场部提供的老师
        return $values[$key];
    }

    protected function createTranslationLabel($pdo,$word_id,$translation_id,$label_ids,$save_time)
    {
        // 标签
        $label_arr = explode(',', $label_ids);
        $label_arr = array_unique($label_arr);

        $sql = "INSERT INTO `wordbank_translation_label` (  `translation_id`, `wordbank_id`,`label_id`, `created_at`, `updated_at`) VALUES";
        foreach ($label_arr as  $label_id){
            if (!empty($label_id)){
                $sql .=  " ('". $translation_id."','". $word_id."','".$label_id."','". $save_time."','". $save_time."'),";
            }
        }
        $sql = trim($sql, ',') . ';';

        $res = $pdo->exec($sql);
        if (!$res){
            dd($sql, '插入单词解释标签失败');
        }
    }


}
