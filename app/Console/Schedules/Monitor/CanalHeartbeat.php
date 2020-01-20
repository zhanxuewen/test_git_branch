<?php

namespace App\Console\Schedules\Monitor;

use DB;
use App\Library\Log;
use App\Library\Curl;
use App\Console\Schedules\BaseSchedule;

class CanalHeartbeat extends BaseSchedule
{
    protected $logs = [];

    protected $ignore = [];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $value = date('Y-m-d H:i:s');
        foreach (['dev', 'test', 'online'] as $conn) {
            $this->change($conn, $value);
        }
        sleep(10);
        foreach (['dev', 'test', 'teach', 'trail', 'online'] as $conn) {
            if (!in_array($conn, $this->ignore))
                $this->detect($conn, $value);
        }
        if (!empty($this->logs))
            $this->log();
    }

    protected function change($conn, $value)
    {
        DB::setPdo($this->getConnPdo('core', $conn))->table('label')->where('id', 3)->update(['code' => $value]);
    }

    protected function detect($conn, $value)
    {
        $check = DB::setPdo($this->getConnPdo('learning', $conn))->table('core_label')->find(3);
        if ($check->code != $value)
            $this->logs[] = $conn;
    }

    protected function log()
    {
        $log = new Log();
        $message = "Canal Heartbeat Exception: " . implode('|', $this->logs) . " sync exception";
        $log->warning('heartbeat', $message);
        $url = 'https://oapi.dingtalk.com/robot/send?access_token=ce5c72c9912ce080c86c82a097b86817ae6be026ac66b9e927b36eaabcdc7ff5';
        $data = json_encode(['msgtype' => 'text', 'text' => ['content' => $message]]);
        Curl::curlPost($url, $data);
    }
}
