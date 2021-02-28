<?php

namespace App\Console\Commands\Utils;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:sql';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成 特定sql ';

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
        $start_id = 0;
        $chunk = 1000000;
        $table = 'system_activity';
        $range = 48;
        $sleep = 3;
        for ($i=0; $i<$range; $i++){
            $start =    $start_id + $i  *  $chunk;
            $end = $start + $chunk;
            $name = $table.'_'.$i;
            $str = "mysqldump -h172.17.0.180 -P3306 -uLuminEe -p'mysql#0056' --add-locks=0 --no-create-info --single-transaction  --set-gtid-purged=OFF b_vanthink_online $table --where='id>$start and id <= $end' --result-file=./$name.sql && sleep $sleep  ";
            $this->info($str);
        }
        dd('done');
    }

}
