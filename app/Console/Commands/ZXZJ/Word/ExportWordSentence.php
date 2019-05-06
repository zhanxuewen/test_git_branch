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
        $path = storage_path('imports');
        $this->traverse($path);

        $this->output->progressStart(count($this->filePath));

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getPdo($pdo_type);

        $save_data = [];
        foreach ($this->filePath as  $file) {
            if (strpos($file, '.gitignore') || strpos($file, 'abc')) {
                $this->output->progressAdvance();
                continue;
            }
            $contents = $this->import($file);
            $header = array_shift($contents);
            $header = array_filter($header);
            $key_tran = array_flip($header);

            foreach ($contents as $content) {
                $vocabulary = $content[$key_tran['vocabulary']];
                $part_of_speech = $content[$key_tran['part_of_speech']];
                $translation = $content[$key_tran['translation']];
                $sentence = $content[$key_tran['sentence']];
                $explain = $content[$key_tran['explain']];
                $word_id = $content[$key_tran['word_id']];
                $label_ids = $content[$key_tran['label_ids']];

                if(empty($vocabulary)&& empty($part_of_speech)
                    && empty($translation) && empty($sentence)
                    && empty($explain) && empty($word_id) && empty($label_ids)
                ){
                    continue;
                }

                if(!isset($save_data[$vocabulary])){
                    $save_data[$vocabulary] = [];
                }

                if(!isset($save_data[$vocabulary][$part_of_speech])){
                    $save_data[$vocabulary][$part_of_speech] = [];
                }

                if(!isset($save_data[$vocabulary][$part_of_speech][$translation])){
                    $save_data[$vocabulary][$part_of_speech][$translation] = [];
                }

                if(!isset($save_data[$vocabulary][$part_of_speech][$translation]['sentence'])){
                    if (is_double($label_ids)) $label_ids = intval($label_ids);
                    $save_data[$vocabulary][$part_of_speech][$translation] = [
                        'sentence' => $sentence,
                        'explain'  => $explain,
                        'word_id'  => intval($word_id),
                        'label_ids'=> $label_ids
                    ];
                }else{
                    $old = $save_data[$vocabulary][$part_of_speech][$translation]['label_ids'];
                    if (is_string($old)) $old_arr = explode(',', $old);
                    if (is_integer($old)) $old_arr = [intval($old)];

                    if (is_string($label_ids)) $new_arr = explode(',', $label_ids);
                    if (is_double($label_ids)) $new_arr = [intval($label_ids)];

                    $label_union = array_merge($old_arr, $new_arr);
                    $label_str = implode(',', array_unique($label_union));
                    $save_data[$vocabulary][$part_of_speech][$translation]['label_ids'] = $label_str;
                }
            }

            $store_data = [];
            $store_data[0] = ['vocabulary', 'part_of_speech', 'translation', 'sentence', 'explain', 'word_id', 'label_ids'];

            foreach ($save_data as $vocabulary=>$item1){
                foreach ($item1 as $part_of_speech => $item2){
                    foreach ($item2 as $translation=> $item3){
                        $store_data[] = [
                            $vocabulary,
                            $part_of_speech,
                            $translation,
                            $item3['sentence'],
                            $item3['explain'],
                            $item3['word_id'],
                            $item3['label_ids'],
                        ];
                    }
                }
            }
//            dd($store_data);
            // 保存信息
            $file_tmp = '最终版本';
            $this->store($file_tmp, $store_data, '.xlsx');
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

}
