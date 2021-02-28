<?php

namespace App\Console\Commands\Utils;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CollectNetFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:net:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取网盘文件';

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
        config(['database.default' => 'local']);

//        dd('done');
        $record = \DB::table('iterator')->min('count');
        if ($record == -1 ) dd('done');

        if (rand(1,10) > 6 ) {
            dd('ddd');
        }else{
            sleep(rand(1,10));
        }

        $url = "https://pan.baidu.com/rest/2.0/xpan/multimedia?";
        $url .= "method=listall&path=/&access_token=121.c8f415b0a15a7d75e5fcafb9d2440091.YgrDIm-X-62zsNAwI6b2WuZe-HftMALf63C_ZnS.S8pKkg&recursion=1";
        $url .= "&start=$record&limit=1000&order=name";

        \Log::info($url);
        $res = $this->request($url);

        $res = json_decode($res, true);
        if ($res['errno'] == 0 &&  $res['errno'] == 'succ'){

            foreach (array_chunk($res['list'], 100 ) as $create){
                \DB::table('file')->insert($create);
            }

            if ($res['has_more'] == 1){
                \DB::table('iterator')->update(
                    ['count' => $res['cursor']]
                );
            }else{
                \DB::table('iterator')->update(
                    ['count' => -1 ]
                );
            }

            $this->info($res['cursor']);
            \Log::info($res['cursor']);
        }else{
            \Log::info($res);
        }

    }

    public function request($url, $https = true, $method = 'get', $data = null)
    {
        // 初始化url
        $ch = curl_init($url);
        // 设置相关的参数
        // 字符串不直接输出, 进行一个变量的存储
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 判断是不是HTTPS请求
        if ($https == true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        // 是不是post请求
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 发送请求
        $ret = curl_exec($ch);
        // 关闭连接
        curl_close($ch);
        // 返回请求结果
        return $ret;
    }
// /usr/local/bin/php /var/www/html/sql_analyze_/artisan collect:net:files


}
