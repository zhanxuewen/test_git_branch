<?php

namespace App\Console\Schedules\Monitor;

use DB;
use App\Library\Log;
use App\Library\Curl;
use App\Console\Schedules\BaseSchedule;

class CanalHeartbeat extends BaseSchedule
{
    protected $value;

    protected $logs = [];

    protected $projects = [
        'learning' => [
            'conn' => ['dev', 'test', 'teach', 'trail', 'online'],
            'change' => [
                'project' => 'core',
                'conn' => ['dev', 'test', 'online']
            ],
            'rule' => [
                'table' => 'label',
                'id' => 3,
                'update' => 'code'
            ],
            'detect' => [
                'table' => 'core_label',
                'id' => 3,
                'field' => 'code'
            ],
            'ignore' => ['test']
        ],
        'kids' => [
            'conn' => ['dev'],
            'change' => [
                'project' => 'core',
                'conn' => ['dev']
            ],
            'rule' => [
                'table' => 'school_label',
                'id' => 1,
                'update' => 'updated_at'
            ],
            'detect' => [
                'table' => 'school_label',
                'id' => 1,
                'field' => 'updated_at'
            ],
            'ignore' => []
        ]
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->value = date('Y-m-d H:i:s');
        foreach ($this->projects as $project => $conf) {
            $this->change($conf['change'], $conf['rule']);
        }
        sleep(10);
        foreach ($this->projects as $project => $conf) {
            $this->detect($project, $conf['conn'], $conf['detect'], $conf['ignore']);
        }
        if (!empty($this->logs))
            $this->log();
    }

    protected function change($change, $rule)
    {
        foreach ($change['conn'] as $conn) {
            DB::setPdo($this->getConnPdo($change['project'], $conn))->table($rule['table'])
                ->where('id', $rule['id'])->update([$rule['update'] => $this->value]);
        }
    }

    protected function detect($project, $conn_s, $detect, $ignore = [])
    {
        $field = $detect['field'];
        foreach ($conn_s as $conn) {
            if (in_array($conn, $ignore)) continue;

            $check = DB::setPdo($this->getConnPdo($project, $conn))->table($detect['table'])->find($detect['id']);
            if ($check->$field != $this->value)
                $this->logs[] = $project . '@' . $conn;
        }
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
