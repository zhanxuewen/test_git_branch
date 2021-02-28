<?php

namespace App\Console\Commands\ZXZJ\Word;

use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportWordSentence extends Command
{

    use PdoBuilder;
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:word:sentence {pdo=local}';

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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // 获取文件列表
        $file = 'abc/demo.xlsx';
        $contents = $this->import($file);

        $header = array_shift($contents);
        $key_tran = array_flip($header);

        $words = array_column($contents,0);
        $words_str = str_repeat("?,", count($words)-1) . "?";;

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getConnPdo('core', $pdo_type);

        // 获取所有的单词及其解释
        $sql = 'select `wordbank`.`id` wordbank_id, `vocabulary`, `wordbank`.`deleted_at` w_del, 
                `wordbank_id`,`part_of_speech` , `translation`, `wordbank_translation`.`id` translation_id , `wordbank_translation`.`deleted_at` t_del
                from `wordbank` INNER  JOIN  `wordbank_translation` ON `wordbank`.`id` = `wordbank_translation`.`wordbank_id`
                where `vocabulary` in ('.$words_str.')';
        $res  = $pdo->prepare($sql);
        $res->execute($words);

        $word_list = $res->fetchAll(\PDO::FETCH_ASSOC  );

        // 库里的单词信息
        $word_info = collect($word_list)->groupBy('vocabulary')->map(function ($word){
            return $word->groupBy('part_of_speech')->map(function ($part_of_speech){
                return $part_of_speech->groupBy('translation')->map(function ($translation){
                    $item = $translation->first();
                    if (empty($item['w_del'])&&empty($item['t_del'])){
                        return [
                            'translation_id' => $item['translation_id'],
                            'wordbank_id'    => $item['wordbank_id'],
                        ];
                    }else{
                        return [];
                    }
                });
            });
        })->toArray();

        // 需要保留的 id
        $wordbank_translation_label_save = [];
        // 需要新建的 记录
        $wordbank_translation_label_create = [];
        // 需要删除的 id
        $wordbank_translation_label_delete = [];
        // 新建 解释 例句 对应关系
        $wordbank_sentence_create = [];

        $save_time = Carbon::now()->toDateTimeString();

        $err = [];
        // 遍历每个单词
        foreach ($contents as $content){
            // 单词
            $word = $content[$key_tran['word']];
            if (!isset($word_info[$word])){
                $content[] = '词库中未录入该单词';
                $err[] = $content;
                continue;
            }

            // 单词 词性
            $part_of_speech = $content[$key_tran['part_of_speech']];
            if (!isset($word_info[$word][$part_of_speech])){
                // 获取 单词词性解析
                $sql = 'select `part_of_speech`, `translation`, `deleted_at` from `wordbank_translation` WHERE `wordbank_id` = '. $content[$key_tran['word_id']];
                $part_of_speech_arr = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                $part_of_speech_str = '';
                foreach ($part_of_speech_arr as $part_of_speech){
                    if (empty($part_of_speech['deleted_at'])){
                        $part_of_speech_str .= $part_of_speech['part_of_speech'] . $part_of_speech['translation'] . ';  ';
                    }
                }
                $content[] = '词库中未录入该单词词性, 现有词性: '.$part_of_speech_str;
                $err[] = $content;
                continue;
            }

            // 单词 解释
            $translation = $content[$key_tran['translation']];
            if (!isset($word_info[$word][$part_of_speech][$translation])){
                // 获取 单词词性解析
                $sql = 'select `part_of_speech`, `translation`, `deleted_at` from `wordbank_translation` WHERE `wordbank_id` = '. $content[$key_tran['word_id']];
                $part_of_speech_arr = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                $part_of_speech_str = '';
                foreach ($part_of_speech_arr as $part_of_speech){
                    if (empty($part_of_speech['deleted_at'])){
                        $part_of_speech_str .= $part_of_speech['part_of_speech'] . $part_of_speech['translation'] . ';  ';
                    }
                }
                $content[] = '词库中未录入该单词解释, 现有词性: '.$part_of_speech_str;
                $err[] = $content;
                continue;
            }
            // 单词 解释 被删除
            if (empty($word_info[$word][$part_of_speech][$translation])){
                // 获取 单词词性解析
                $sql = 'select `part_of_speech`, `translation`, `deleted_at` from `wordbank_translation` WHERE `wordbank_id` = '. $content[$key_tran['word_id']];
                $part_of_speech_arr = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
                $part_of_speech_str = '';
                foreach ($part_of_speech_arr as $part_of_speech){
                    if (empty($part_of_speech['deleted_at'])){
                        $part_of_speech_str .= $part_of_speech['part_of_speech'] . $part_of_speech['translation'] . ';  ';
                    }
                }
                $content[] = '词库中该单词或该解释已被删除, 现有词性: '.$part_of_speech_str;
                $err[] = $content;
                continue;
            }

            // 获取单词数据库信息
            $translation_id = intval($word_info[$word][$part_of_speech][$translation]['translation_id']);
            $wordbank_id = intval($word_info[$word][$part_of_speech][$translation]['wordbank_id']);
            $label_id = intval($content[$key_tran['label_id']]);

            // 检查 解释 标签对应关系
            $sql = 'select `id`,`translation_id` from `wordbank_translation_label` where `label_id` = '.$label_id
                . ' and `wordbank_id` = ' . $wordbank_id;

            $translation_label_arr_pre = $pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $translation_label_arr = collect($translation_label_arr_pre)->sortBy('id')->pluck('translation_id', 'id')->toArray();

            // 维护 wordbank_translation_label
            $save_record_id = 0 ;
            foreach ($translation_label_arr as $record_id=>$translationID){
                if ($translationID == $translation_id){
                    $save_record_id = $record_id;
                    break;
                }
            }
            if ($save_record_id){ // 找到啦
                // 删掉的id
                unset($translation_label_arr[$save_record_id]);
                $delete_ids = array_keys($translation_label_arr);
                $wordbank_translation_label_save[] = $save_record_id;
                $wordbank_translation_label_delete = array_merge($wordbank_translation_label_delete,$delete_ids);

            }else{ // 没找到 创建新内容
                $wordbank_translation_label_create[] = [
                     'translation_id'=> $translation_id,
                     'wordbank_id'=> $wordbank_id,
                     'label_id'=> $label_id,
                     'created_at'=> $save_time,
                     'updated_at'=> $save_time,
                ];
            }

            // 创建单词 解释 例句
            $wordbank_sentence_create[] = [
                'translation_id' => $translation_id,
                'teacher_id' => $this->getTeacherId(),
                'sentence' => $content[$key_tran['sentence']],
                'explain' => $content[$key_tran['explain']],
                'created_at' => $save_time,
                'updated_at' => $save_time,
            ];
        }


        dd($wordbank_sentence_create, $err, $wordbank_translation_label_save, $wordbank_translation_label_create, $wordbank_translation_label_delete);

         // 保存错误信息
        $file = 'abc/单词例句错误实例';
        array_unshift($err,
            ["vocabulary","part_of_speech","translation","label_id","sentence","explain","word_id","error"]
        );
        $this->store($file, $err,'.xlsx');
        dd('done');
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
}
