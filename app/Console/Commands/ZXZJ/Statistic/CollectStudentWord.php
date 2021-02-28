<?php

namespace App\Console\Commands\Statistic;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CollectStudentWord extends Command
{
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:student:word';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计 学生的 背单词数量';

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
//        config(['database.default' => 'online_DML']);
//        config(['database.default' => 'online']);
        config(['database.default' => 'zxzj_test_new']);

        // 获得 所有的 自增id
        $max_record = \DB::table('statistic_schedule_tmp')
            ->selectRaw("value->'$.word_homework_student_record' max_id, created_date")
            ->where('key', 'table_nearly_max_id')
            ->get()->pluck('max_id','created_date') -> toArray();


        $start_date = '2020-01-01';

        for($i=0; $i<365; $i++){

            $record_date = Carbon::parse($start_date)->addDays($i)->toDateString();
            $before_date = Carbon::parse($start_date)->addDays($i)->subDay()->toDateString();
            $start_id = $max_record[$before_date];
            $after_date = Carbon::parse($start_date)->addDays($i)->addDay()->toDateString();
            $end_id = $max_record[$after_date];

            $start_time = Carbon::parse($start_date)->addDays($i)->startOfDay()->toDateTimeString();
            $end_time = Carbon::parse($start_date)->addDays($i)->endOfDay()->toDateTimeString();

            config(['database.default' => 'zxzj_test_new']);
            $str = <<<EOF
SELECT
	LEFT(created_at,10) date, student_id,GROUP_CONCAT( fluency_ids ) fluency_ids
FROM
	`word_homework_student_record`  force index(word_homework_student_record_created_at_index) 
	WHERE id >= $start_id     
    and id <= $end_id
	and created_at >= '$start_time'
	and created_at <= '$end_time'
GROUP BY LEFT(created_at,10), student_id
EOF;

            $statistic_record = \DB::select(\DB::raw($str));

            config(['database.default' => 'online_DML']);
            // 学生统计记录
            $records = \DB::table('statistic_student_word')
                ->selectRaw('id,student_id, extra')
                ->where('created_date',$record_date)
                ->get()->keyBy('student_id')->toArray();

            $update_data = [];

            foreach ( $statistic_record as $value){
                $date = $value->date;
                $student_id = $value->student_id;
                $fluency_ids = $value->fluency_ids;

                if (!isset( $records[$student_id])) continue;

                $record = $records[$student_id];


                $fluency_ids_arr = array_filter(array_unique( explode(',', $fluency_ids)));

                $extra = $record->extra;
                $new = [];
                if (empty($extra)){
                    $new = ['fluency_count' => count($fluency_ids_arr)];
                }else{
                    $extra_arr = json_decode( $extra, true);
                    $new = $extra_arr;
                    $new['fluency_count'] = count($fluency_ids_arr);
                }


                $update_data[] = [
                    'id' => $record->id,
                    'extra' => json_encode($new)
                ];
//                \DB::table('statistic_student_word')
//                    ->where('id', $record->id)
//                    ->update([
//                        'extra' => json_encode($new),
//                    ]);
                echo  '+';
            }

            DB::beginTransaction();
            foreach ( array_chunk( $update_data ,100) as $update_data_chunk){
                $this->batchUpdate('statistic_student_word', $update_data_chunk);
                echo  '-';
            }
            DB::commit();
            $this->info( $record_date . '--->done');
        }
        dd( 'done' );




/**
        //===============================================
        $str = "
SELECT
	LEFT(created_at,10) date, student_id,GROUP_CONCAT( fluency_ids ) fluency_ids
FROM
	`word_homework_student_record` 
	WHERE id > 5710010     
GROUP BY LEFT(created_at,10), student_id";

        $statistic_record = \DB::select(\DB::raw($str));


        foreach ( $statistic_record as $value){
            $date = $value->date;
            $student_id = $value->student_id;
            $fluency_ids = $value->fluency_ids;

            $record = \DB::table('statistic_student_word')
                ->selectRaw('id, extra, word_total')
                ->where('student_id',$student_id)
                ->where('created_date',$date)
                ->first();

            if (empty( $record)) continue;


            $fluency_ids_arr = array_filter(array_unique( explode(',', $fluency_ids)));

            $extra = $record->extra;
            $new = [];
            if (empty($extra)){
                $new = ['fluency_count' => count($fluency_ids_arr)];
            }else{
                $extra_arr = json_decode( $extra, true);
                $new = $extra_arr;
                $new['fluency_count'] = count($fluency_ids_arr);
            }
            \DB::table('statistic_student_word')
                ->where('id', $record->id)
                ->update([
                    'extra' => json_encode($new),
                ]);
        }
        echo '+';
 * **/



    }



    protected function batchUpdate($table, $multipleData = array())
    {
        if (empty($multipleData)) {
            return false;
        }
        $updateColumn = array_keys($multipleData[0]);
        $referenceColumn = $updateColumn[0]; //e.g id
        unset($updateColumn[0]);
        $whereIn = "";

        $q = "UPDATE " . $table . " SET ";
        foreach ($updateColumn as $uColumn) {
            $q .= $uColumn . " = CASE ";

            foreach ($multipleData as $data) {
                $q .= "WHEN " . $referenceColumn . " = " . $data[$referenceColumn] . " THEN '" . $data[$uColumn] . "' ";
            }
            $q .= "ELSE " . $uColumn . " END, ";
        }
        foreach ($multipleData as $data) {
            $whereIn .= "'" . $data[$referenceColumn] . "', ";
        }
        $q = rtrim($q, ", ") . " WHERE " . $referenceColumn . " IN (" . rtrim($whereIn, ', ') . ")";

        // Update
        return \DB::update(\DB::raw($q));
    }
}
