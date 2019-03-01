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
        Commands\Capture::class,
        Commands\CaptureTcpServer::class,
        Commands\BuildDbWiki::class,
        Commands\BuildMigrationMD::class,

        Commands\Core\ModuleSeed::class,
        Commands\Core\ModuleMigrate::class,
        Commands\Core\MakeModuleSeeder::class,
        Commands\Core\MakeModuleMigration::class,

        Commands\Rpc\CaptureRpcDB::class,
        Commands\Rpc\CaptureRpcRepo::class,
        Commands\Rpc\CaptureRpcService::class,

        Commands\Schedule\RecallOrderSchedule::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $this->logSchedule('Monitor Record Start At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordTableIncrement())->handle();
            $this->logSchedule('Record Table Increment Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordDeviceUsage())->handle();
            $this->logSchedule('Record Device Usage Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordOrderIncrement())->handle();
            $this->logSchedule('Record Order Increment Done At ' . date('Y-m-d H:i:s'));
        })->dailyAt('01:00');
        $schedule->call(function (Schedules\Order\ExportOrderList $schedule) {
            $this->logSchedule('Export Order Start At ' . date('Y-m-d H:i:s'));
            $schedule->handle();
            $this->logSchedule('Export Order List Done At ' . date('Y-m-d H:i:s'));
        })->dailyAt('08:00');
        $schedule->call(function (Schedules\Order\ExportOfflineList $schedule) {
            $this->logSchedule('Export Offline Start At ' . date('Y-m-d H:i:s'));
            $schedule->handle();
            $this->logSchedule('Export Offline List Done At ' . date('Y-m-d H:i:s'));
        })->weekly()->mondays()->at('08:20');
    }

    protected function logSchedule($log)
    {
        $disk = \Storage::disk('logs');
        if (!$disk->exists('schedule.log')) {
            $disk->put('schedule.log', $log);
        } else {
            $disk->append('schedule.log', $log);
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
