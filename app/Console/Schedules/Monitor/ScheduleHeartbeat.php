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
    public function handle()
    {
        // 在线助教
//        foreach (['dev', 'test', 'online'] as $platfrom) {
        foreach (['dev'] as $platfrom) {
            $this->checkSchedule('core', $platfrom);
        }
        // 百项过
//        foreach (['dev', 'test', 'teach', 'trail', 'online'] as $platfrom) {
//            $this->checkSchedule('learning', $platfrom);
//        }

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

    /**
     * 给 钉钉 发消息
     * @param $project
     * @param $platfrom
     */
    protected function send($project, $platfrom)
    {
        $message = "exception:  项目：$project, 数据库：$platfrom, 定时任务异常";
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=ce5c72c9912ce080c86c82a097b86817ae6be026ac66b9e927b36eaabcdc7ff5';
        $data = json_encode(['msgtype' => 'text', 'text' => ['content' => $message]]);
        Curl::curlPost($url, $data);
    }
}
