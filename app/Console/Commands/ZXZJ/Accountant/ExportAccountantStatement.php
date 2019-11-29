<?php

namespace App\Console\Commands\ZXZJ\Accountant;

use DB;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;
use App\Console\Common\ZXZJ\SchoolAccountant;

class ExportAccountantStatement extends Command
{
    use PdoBuilder, Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:accountant:statement {start} {end=today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        DB::setPdo($this->getConnPdo('core', 'online'));

        $start = $this->argument('start');
        $end = $this->argument('end');
        if ($end == 'today') $end = date('Y-m-d');
        $report = SchoolAccountant::get($start, $end);
        $this->store('对账单 from ' . $start . ' to ' . $end, $report, '.xlsx');
    }

}
