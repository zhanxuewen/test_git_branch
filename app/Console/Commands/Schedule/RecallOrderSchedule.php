<?php

namespace App\Console\Commands\Schedule;

use Illuminate\Console\Command;
use App\Console\Schedules\Order\ExportOrderList;

class RecallOrderSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recall:order:schedule {day} {--send}';

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
        $send = $this->option('send');
        (new ExportOrderList())->handle($day, $send);
    }

}
