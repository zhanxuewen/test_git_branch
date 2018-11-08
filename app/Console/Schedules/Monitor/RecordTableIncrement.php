<?php

namespace App\Console\Schedules\Monitor;

use App\Console\Schedules\BaseSchedule;

class RecordTableIncrement extends BaseSchedule
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $day = date('Y-m-d');
        if (\DB::table('monitor_table_increment')->where('created_date', $day)->count() > 0) {
            echo 'M_T_I '.$day.' Already Done!';
            return;
        }
        $local_pdo = \DB::getPdo();
        $database  = $this->getDbName('online');
        $sql       = "SELECT table_name, auto_increment FROM information_schema.tables where table_schema='$database'";
        $tables    = \DB::setPdo($this->getPdo('online'))->select($sql);
        $create    = [];
        foreach ($tables as $table) {
            $name = $table->table_name;
            $rows     = \DB::setPdo($this->getPdo('online'))->select("SELECT COUNT(*) as count FROM `$database`.`$name`");
            $rows = $rows[0]->count;
            $create[] = [
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
