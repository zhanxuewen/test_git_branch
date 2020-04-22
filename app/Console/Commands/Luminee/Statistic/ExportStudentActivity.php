<?php

namespace App\Console\Commands\Luminee\Statistic;

use DB;
use Carbon\Carbon;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class ExportStudentActivity extends Command
{
    use PdoBuilder, Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:student:activity';

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
        DB::setPdo($this->getConnPdo('core', 'online4'));
        $start = Carbon::parse('2018-06-01');
        $report = [];
        $report[] = ['月份', '活跃学生'];
        for ($i = 0; $i < 40; $i++) {
            if ($start->gte('2020-04-01')) break;
            $between = [];
            $between[] = $start->toDateString();
            $start->addMonth();
            $between[] = $start->toDateString();
            $sql = DB::table('statistic_student_activity')->where('created_date', '>=', $between[0])
                ->where('created_date', '<', $between[1])->selectRaw('count(DISTINCT student_id) as coo')->get();
            $t = substr($between[0], 0, 7);
            $report[] = [$t, $sql[0]->coo];
            $this->line('Finished at ' . $t);
        }
        $filename = '月活跃度All';
        $path = 'summary';
        $file = $this->store($path . '/' . $filename, $report);

    }

    protected function monthExp(){
        $start = Carbon::parse('2018-06-01');
        $report = [];
        $report[] = ['月份', '活跃学生'];
        for ($i = 0; $i < 40; $i++) {
            if ($start->gte('2020-04-01')) break;
            $between = [];
            $between[] = $start->toDateString();
            $start->addMonth();
            $between[] = $start->toDateString();
            $sql = DB::table('statistic_student_activity')->where('has_login', 1)->where('created_date', '>=', $between[0])
                ->where('created_date', '<', $between[1])->selectRaw('count(DISTINCT student_id) as coo')->get();
            $t = substr($between[0], 0, 7);
            $report[] = [$t, $sql[0]->coo];
            $this->line('Finished at ' . $t);
        }
    }

    protected function daysExp(){
        $start = Carbon::parse('2020-01-01');
        $report = [];
        $report[] = ['日期', '活跃学生'];
        for ($i = 0; $i < 200; $i++) {
            if ($start->gte('2020-04-01')) break;
            $t = $start->toDateString();
            $sql = DB::table('statistic_student_activity')->where('has_login', 1)->where('created_date', $t)
                ->selectRaw('count(DISTINCT student_id) as coo')->get();
            $report[] = [$t, $sql[0]->coo];
            $this->line('Finished at ' . $t);
            $start->addDay();
        }
    }

}
