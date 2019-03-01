<?php

namespace App\Console\Commands\Schedule;

use Illuminate\Console\Command;
use App\Console\Schedules\Order\ExportOrderList;
use App\Console\Schedules\Order\ExportOfflineList;

class RecallOrderSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recall:order:schedule {day} {type=order} {--send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Capture The Project';

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
        $day = $this->argument('day');
        if (strstr($day, ',')) {
            $days = explode(',', $day);
            $day = ['start' => $days[0], 'end' => $days[1]];
        }
        $type = $this->argument('type');
        $send = $this->option('send');
        if ($type == 'order') {
            (new ExportOrderList())->handle($day, $send);
        } else if ($type == 'offline') {
            (new ExportOfflineList())->handle($day, $send);
        } else {
            $this->error('Type Error!');
        }
    }

}
