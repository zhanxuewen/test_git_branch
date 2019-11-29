<?php

namespace App\Console;

use Carbon\Carbon;
use App\Library\Log;
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

        Commands\Core\ModuleSeed::class,
        Commands\Core\ModuleMigrate::class,
        Commands\Core\MakeModuleSeeder::class,
        Commands\Core\MakeModuleMigration::class,

        Commands\Schedule\RecallOrderSchedule::class,

        // Rewrite Laravel Commands
        Commands\Rewrite\MigrateRollback::class,

        Commands\Database\GenerateGrantSql::class,
        Commands\Database\GenerateUpdatePassword::class,

        Commands\Luminee\Order\AnalyzeOrder::class,
        Commands\Luminee\Order\ExportAnalyzeOrder::class,

        Commands\Luminee\Testbank\SyncTestbankToLearning::class,
        Commands\Luminee\Testbank\DeleteLearningBill::class,
        Commands\Luminee\Testbank\RebuildLearningEntityOrder::class,
        Commands\Luminee\Testbank\RebuildLearningAssessmentEntityOrder::class,
        Commands\Luminee\Testbank\UpdateLearningEntity::class,
        Commands\Luminee\Testbank\FixLearningItems::class,


        // 在线助教
        Commands\ZXZJ\Word\ImportWordSentence::class,
        Commands\ZXZJ\Word\ExportWordSentence::class,
        Commands\ZXZJ\Word\MergeWordLabel::class,
        Commands\ZXZJ\Word\ExportWord::class,

        Commands\ZXZJ\Accountant\ExportAccountantStatement::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Monitor Record
        $schedule->call(function () {
            $today = date('Y-m-d');
            $this->logSchedule('Monitor Record Start At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordTableIncrement())->handle($today);
            $this->logSchedule('Record Table Increment Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordDeviceUsage())->handle($today);
            $this->logSchedule('Record Device Usage Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordOrderIncrement())->handle($this->getYesterday());
            $this->logSchedule('Record Order Increment Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Monitor\RecordOrderStatus())->handle($this->getYesterday());
            $this->logSchedule('Record Order Status Done At ' . date('Y-m-d H:i:s'));
        })->dailyAt('01:00');

        // Export Order And Offline
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

        // Export Order And Offline Monthly
        $schedule->call(function () {
            $this->logSchedule('Export Order, Offline And Balance Monthly Start At ' . date('Y-m-d H:i:s'));
            $day = ['start' => Carbon::today()->subMonth()->toDateString(), 'end' => Carbon::today()->subDay()->toDateString()];
            (new Schedules\Order\ExportOrderList())->handle($day);
            $this->logSchedule('Export Order Monthly List Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Order\ExportOfflineList())->handle($day);
            $this->logSchedule('Export Offline Monthly List Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Order\ExportContractBalance())->handle();
            $this->logSchedule('Export Contract Balance Monthly Done At ' . date('Y-m-d H:i:s'));
        })->monthlyOn(1, '08:40');

        $schedule->call(function () {
            $this->logSchedule('Export Activity Summary Monthly Start At ' . date('Y-m-d H:i:s'));
            $day = ['start' => Carbon::today()->subMonth()->toDateString(), 'end' => Carbon::today()->subDay()->toDateString()];
            (new Schedules\Order\ExportActivitySummary())->handle($day);
            $this->logSchedule('Export Activity Summary Monthly List Done At ' . date('Y-m-d H:i:s'));
        })->monthlyOn(1, '09:00');

        $schedule->call(function () {
            $this->logSchedule('Export Balance And Accountant Statement Mid Monthly Start At ' . date('Y-m-d H:i:s'));
            $day = ['start' => Carbon::today()->subMonth()->startOfMonth()->toDateString(),
                'end' => Carbon::yesterday()->toDateString()];
            (new Schedules\Order\ExportAccountantStatement())->handle($day);
            $this->logSchedule('Export Accountant Statement Mid Monthly Done At ' . date('Y-m-d H:i:s'));
            (new Schedules\Order\ExportContractBalance())->handle();
            $this->logSchedule('Export Contract Balance Mid Monthly Done At ' . date('Y-m-d H:i:s'));
        })->monthlyOn(16, '08:40');

        $schedule->call(function () {
            (new Schedules\Monitor\CanalHeartbeat())->handle();
        })->everyFifteenMinutes();
    }

    protected function logSchedule($log)
    {
        (new Log())->info('schedule', $log);
    }

    protected function getYesterday()
    {
        return Carbon::yesterday();
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
