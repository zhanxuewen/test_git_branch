<?php

namespace App\Console\Commands\Luminee\Order;

use DB;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExportActStudentYearly extends Command
{
    use PdoBuilder, Excel;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:act_student:yearly';

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
        DB::setPdo($this->getConnPdo('core','online'));
        $daily = $weekly = $monthly = [];
        $records = [];
        $rows = DB::table('statistic_marketer_record')->where('marketer_type', 'system_all')->where('marketer_id',0)->where('created_date', '>', '2017-12-30')->where('created_date', '<', '2019-01-01')->selectRaw('act_student, date_type')->get();
        foreach($rows as $row){
            $type = $row->date_type;
            if (!isset($records[$type])) $records[$type] = $row->act_student;
        }
        $day = Carbon::parse('2018-01-01');
        $daily[] = ['日期', '活跃学生'];
        for($i = 0; $i < 369; $i++){
            if ($day->year == 2019) break;
            $d = $day->format('Y-m-d');
            $daily[] = [
                'day' => $d,
                'act' => isset($records[$d]) ? $records[$d] : null
            ];
            $day->addDay();
        }
        $weekly[] = ['周', '活跃学生', '区间'];
        $now = Carbon::now();
        for($i = 1; $i <= 52; $i++){
            $w = 'W2018-' . ($i < 10 ? '0' . $i : $i);
            $now->setISODate(2018, $i);
            $weekly[] = [
                'week' => $w,
                'act' => isset($records[$w]) ? $records[$w] : null,
                'bet' => $now->startOfWeek()->format('md') . '-'. $now->endOfWeek()->format('md')
            ];
        }
        $monthly[] = ['月', '活跃学生'];
        for ($i = 1; $i <= 12; $i++){
            $m = 'M2018-' . ($i < 10 ? '0' . $i : $i);
            $monthly[] = [
                'month' => $m,
                'act' => isset($records[$m]) ? $records[$m] : null
            ];
        }
        $report = [
            '日活跃' => $daily,
            '周活跃' => $weekly,
            '月活跃' => $monthly
        ];
        $filename = '2018活跃统计';
        $path = 'summary';
        $file = $this->sheetsStore($path . '/' . $filename, $report);
    }

}
