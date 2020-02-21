<?php

namespace App\Console\Schedules\Monitor;

use Carbon\Carbon;
use DB;
use App\Library\Curl;
use App\Console\Schedules\BaseSchedule;

class ScheduleHeartbeat extends BaseSchedule
{
    protected $logs = [];

    protected $ignore = ['test'];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle($check_result = 0)
    {
        if (Carbon::now()->gt(Carbon::now()->startOfDay()->addMinutes(5))){
            // 在线助教
            foreach (['dev', 'test', 'online'] as $platfrom) {
                $this->checkSchedule('core', $platfrom);
            }
            // 百项过
            foreach (['dev', 'test', 'teach', 'trail', 'online'] as $platfrom) {
                $this->checkSchedule('learning', $platfrom);
            }
        }

        // 检查是否执行完了
        if ($check_result){
            // 在线助教
            foreach (['dev', 'test', 'online'] as $platfrom) {
                $this->checkScheduleResult('core', $platfrom);
            }
        }
    }

    protected function checkSchedule($project, $platfrom){
        $record = \DB::setPdo($this->getConnPdo($project, $platfrom))
            ->table('statistic_schedule_tmp')
            ->where('key', 'schedule_heart_beat')
            ->where('created_date', date('Y-m-d'))
            ->first();
        if (empty($record)){
            $this->send($project, $platfrom);
        }
        $last_time = str_replace('test Start At ','', $record->value);
        $interval_minutes = Carbon::now()->diffInMinutes(Carbon::parse($last_time));
        if ($interval_minutes > 20) $this->send($project, $platfrom);
    }


    protected function checkScheduleResult($project, $platfrom){
        $arr = [
            'Statistic Index Start',
            'Statistic Index Has Done',
//            'empty student listening sign in Start',
//            'empty student listening sign in Done',
            'Count school Student Available Status Start',
            'Count school Student Available Status Done',
            'Statistic Weekly Index Start',
            'Statistic Weekly Index Done',
            'Statistic Monthly Index Start',
            'Statistic Monthly Index Done',
            'Statistic school daily Index Start',
            'Statistic school daily Index  Done',
            'Count free Student Available Status Start',
            'Count free Student Available Status Done',
            'Check Expired Student Has Start',
            'Check Expired Student Has Done',
            'Update School Logs Has Done',
            'update E_cards Student Start',
            'update E_cards Student  Done',
            'update student word fluency when change word under label Start',
            'update student word fluency when change word under label  Done',
            'transfer_vanclass do Start',
            'transfer_vanclass  Done',
            'Statistic school weekly Index Start',
            'Statistic school weekly Index  Done',
            'Statistic school monthly Index Start',
            'Statistic school monthly Index  Done',
            'Statistic marketer Index Start',
            'Statistic marketer Index  Done',
            'Get Export Recruit Student Excel Data Start',
            'Get Export Recruit Student Excel Data Has Done',
            'Statistic Listening People Count Has Done',
            'Check Expired Group Has Done',
        ];

        foreach ($arr as $item){
            $record = \DB::setPdo($this->getConnPdo($project, $platfrom))
                ->table('statistic_schedule_tmp')
                ->where('key', 'schedule_log')
                ->where('value', 'like',$item.'%')
                ->where('created_date', date('Y-m-d'))
                ->first();
            if (empty($record)){
                if ($item == 'Check Expired Group Has Done' && Carbon::today()->isFriday()){
                }else{
                    $this->sendCheckResult($project, $platfrom,$item);
                }
            }
        }
    }


    /**
     * 给 钉钉 发消息
     * @param $project
     * @param $platfrom
     */
    protected function send($project, $platfrom)
    {
        $message = "exception:  项目：$project, 数据库：$platfrom, 未检测到定时任务";
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=ce5c72c9912ce080c86c82a097b86817ae6be026ac66b9e927b36eaabcdc7ff5';
        $data = json_encode(['msgtype' => 'text', 'text' => ['content' => $message]]);
        Curl::curlPost($url, $data);
    }

    /**
     * 给 钉钉 发消息
     * @param $project
     * @param $platfrom
     */
    protected function sendCheckResult($project, $platfrom,$task)
    {
//        $task = str_replace('Start', '',$task);
//        $task = str_replace('Done', '',$task);
        $task = str_replace('Has', '',$task);
        $message = "exception:  项目：$project, 数据库：$platfrom, 定时任务: '.$task.'异常";
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=ce5c72c9912ce080c86c82a097b86817ae6be026ac66b9e927b36eaabcdc7ff5';
        $data = json_encode(['msgtype' => 'text', 'text' => ['content' => $message]]);
        Curl::curlPost($url, $data);
    }
}
