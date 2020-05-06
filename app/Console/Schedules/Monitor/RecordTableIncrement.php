<?php

namespace App\Console\Schedules\Monitor;

use DB;
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
            'finance_school_balance',
        ]
    ];

    protected $local_pdo;

    /**
     * Execute the console command.
     *
     * @param $day
     * @return void
     */
    public function handle($day)
    {
        $this->local_pdo = \DB::getPdo();
        foreach (['core' => 'online4', 'learning' => 'online'] as $project => $conn) {
            $this->record($project, $conn, $day);
        }
    }

    protected function record($project, $conn, $day)
    {
        if (\DB::table('monitor_table_increment')->where('project', $project)->where('created_date', $day)->count() > 0) {
            echo 'M_T_I ' . $project . ' at ' . $day . ' Already Done!';
            return;
        }
        $database = $this->getConnDB($project, $conn);
        DB::setPdo($this->getConnPdo($project, $conn));
        $tables = \DB::select("SELECT table_name, auto_increment FROM information_schema.tables where table_schema = '$database'");
        $create = [];
        foreach ($tables as $table) {
            $name = $table->table_name;
            if (in_array($name, $this->ignore[$project])) continue;
            $rows = \DB::select("SELECT COUNT(*) as count FROM `$database`.`$name`");
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
        \DB::setPdo($this->local_pdo)->table('monitor_table_increment')->insert($create);
    }
}
