<?php

namespace App\Console\Schedules\Monitor;

use App\Console\Schedules\BaseSchedule;

class RecordTableIncrement extends BaseSchedule
{
    protected $ignore = [
        'core' => [
            'seeders',
            'migrations',
            'user_teacher_testbank',
            'user_teacher_testbank_entity',
        ],
        'learning' => [
            'seeders',
            'migrations',
        ]
    ];

    /**
     * Execute the console command.
     *
     * @param $day
     * @return void
     */
    public function handle($day)
    {
        foreach (['core', 'learning'] as $project) {
            $this->record($project, $day);
        }
    }

    protected function record($project, $day)
    {
        if (\DB::table('monitor_table_increment')->where('project', $project)->where('created_date', $day)->count() > 0) {
            echo 'M_T_I ' . $project . ' at ' . $day . ' Already Done!';
            return;
        }
        $local_pdo = \DB::getPdo();
        $database = $this->getConnDB($project, 'online');
        $sql = "SELECT table_name, auto_increment FROM information_schema.tables where table_schema = '$database'";
        $tables = \DB::setPdo($this->getConnPdo($project, 'online'))->select($sql);
        $create = [];
        foreach ($tables as $table) {
            $name = $table->table_name;
            if (in_array($name, $this->ignore[$project])) continue;
            $rows = \DB::setPdo($this->getConnPdo($project, 'online'))->select("SELECT COUNT(id) as count FROM `$database`.`$name`");
            $rows = $rows[0]->count;
//            $rows = $table->table_rows;
            $create[] = [
                'project' => $project,
                'table' => $name,
                'rows' => $rows,
                'auto_increment_id' => $table->auto_increment,
                'level' => $rows > 5000000 ? 5 : ($rows > 100000 ? 4 : ($rows > 5000 ? 3 : 2)),
                'created_date' => $day
            ];
        }
        \DB::setPdo($local_pdo)->table('monitor_table_increment')->insert($create);
    }
}
