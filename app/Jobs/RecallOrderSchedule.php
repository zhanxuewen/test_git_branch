<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Console\Schedules\Order\ExportOrderList;

class RecallOrderSchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $day;

    /**
     * Create a new job instance.
     *
     * @param $day
     * @return void
     */
    public function __construct($day)
    {
        $this->day = $day;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new ExportOrderList())->handle($this->day, false);
    }
}
