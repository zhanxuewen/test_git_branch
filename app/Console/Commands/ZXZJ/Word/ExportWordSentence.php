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
        $path = '/home/vagrant/code/sql_analyze/storage/imports';
        $this->traverse($path);

        $this->output->progressStart(count($this->filePath));

        $pdo_type = $this->argument('pdo');
        $pdo = $this->getPdo($pdo_type);

        foreach ($this->filePath as  $file) {
            if (strpos($file, '.gitignore') || strpos($file, 'abc')) {
                $this->output->progressAdvance();
                continue;
            }
            \Log::info($file);
            $contents = $this->import($file);
            $header = array_shift($contents);
            $header = array_filter($header);
            \Log::info(json_encode($header));
            if (count($header) != 4) \Log::info($file.'================================');
            $key_tran = array_flip($header);

            $save_data = [];
            $save_data[] = ['单词', '词性1', '解释1', '最小标签的ID', 'word', 'sentence', 'explain', 'wid'];

            foreach ($contents as $content) {
                if(empty($content[$key_tran['单词']])&& empty($content[$key_tran['词性1']])
                    && empty($content[$key_tran['解释1']]) && empty($content[$key_tran['最小标签的ID']])){
                    continue;
                }
                $word = $content[$key_tran['单词']];
                $sql = 'select `id`, `vocabulary`from `wordbank` WHERE `vocabulary` = "' . $word . '" AND `deleted_at` IS NULL';
                $vocabulary = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
                if (!empty($vocabulary)) {
                    $word_id = $vocabulary['id'];
                    $sql = 'select `id`, `sentence`,`explain` from `wordbank_sentence` WHERE `wordbank_id` = ' . $word_id
                        .' AND `deleted_at` IS NULL';
//                    dd($sql);
                    $sentence = $pdo->query($sql)->fetch(\PDO::FETCH_ASSOC);
                    if (empty($sentence)) {
                        $save_data[] = [
                            $content[$key_tran['单词']],
                            $content[$key_tran['词性1']],
                            $content[$key_tran['解释1']],
                            $content[$key_tran['最小标签的ID']],
                            $vocabulary['vocabulary'],
                            '**********************************************',
                            '**********************************************',
                            $word_id,
                        ];
                    } else {
                        $save_data[] = [
                            $content[$key_tran['单词']],
                            $content[$key_tran['词性1']],
                            $content[$key_tran['解释1']],
                            $content[$key_tran['最小标签的ID']],
                            $vocabulary['vocabulary'],
                            $sentence['sentence'],
                            $sentence['explain'],
                            $word_id,
                        ];
                    }
                } else {
                    $save_data[] = [
                        $content[$key_tran['单词']],
                        $content[$key_tran['词性1']],
                        $content[$key_tran['解释1']],
                        $content[$key_tran['最小标签的ID']],
                        '**********************************************',
                        '**********************************************',
                        '**********************************************',
                        '**********************************************',
                    ];
                }

            }
            // 保存错误信息
            $file_tmp = explode('.',$file)[0];
            $this->store($file_tmp, $save_data, '.xlsx');
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

}
