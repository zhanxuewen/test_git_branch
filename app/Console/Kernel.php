<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Inspire::class,

        Commands\Capture::class,
        Commands\CaptureTcpServer::class,
        Commands\BuildDbWiki::class,

        Commands\Rpc\CaptureRpcDB::class,
        Commands\Rpc\CaptureRpcRepo::class,
        Commands\Rpc\CaptureRpcService::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function (Schedules\Order\ExportOrderList $schedule) {
            $schedule->handle();
            $this->logSchedule('Export Order List Done At '.date('Y-m-d H:i:s'));
        })->dailyAt('14:57');
    
        $schedule->call(function () {
            $this->logSchedule('Test At '.date('Y-m-d H:i:s'));
        })->everyMinute();
    }
    
    protected function logSchedule($log)
    {
        if (!\Storage::exists('schedule.log')) {
            \Storage::put('schedule.log', $log);
        } else {
            \Storage::append('schedule.log', $log);
        }
    }
}
