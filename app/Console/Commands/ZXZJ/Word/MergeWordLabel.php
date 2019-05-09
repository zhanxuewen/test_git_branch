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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $path = storage_path('imports');
        $this->traverse($path);

        $word_arr1 = [];
        $word_arr2 = [];

        foreach ($this->filePath as  $file) {
            $file_tmp = str_replace('/var/www/html/sql_analyze/storage/imports/', '', $file);
            $contents = $this->import($file_tmp);
            array_shift($contents);

            if (strpos($file, '单词例句解释') !== false){
                $word_arr1 = $contents;
            }

            if (strpos($file, '1词多义-修改版') !== false){
                $word_arr2 = $contents;
            }

        }

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
