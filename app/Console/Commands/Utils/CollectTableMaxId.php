<?php

namespace App\Console\Commands\Utils;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CollectTableMaxId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:table:max_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '补全 表的 最大id';

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
        $start_date = '2021-02-03';
        $start_id = 34592226;
        $table_name = 'activity_student_book_audio_record';

        for ( $i = 0; $i <403;$i++ ){
            $date = Carbon::parse( $start_date)->addDays($i)->toDateString();
            $end_time = Carbon::parse( $start_date)->addDays($i)->endOfDay()->toDateTimeString();
            $tmp_id  = $start_id;
            do {
                $tmp_id  = $tmp_id + 10000;
                $tmp_record = \DB::table($table_name)->where('id', $tmp_id)->first();
                $this->info ( $tmp_id );
            } while ($tmp_record->created_at < $end_time);
            $start_id = $tmp_id - 10000;

            $this->info('-----------------------');

            $chunk = 1000;
            $tmp_id_2  = $start_id;
            do {
                $tmp_id_2  = $tmp_id_2 + $chunk;
                $tmp_record = \DB::table($table_name)->where('id', $tmp_id_2)->first();
                $this->info ( $tmp_id_2 );
            } while ($tmp_record->created_at < $end_time);
            $start_id = $tmp_id_2 - $chunk;

            $this->info('+++++++++++++++++++++++');

            $tmp_id_3  = $start_id;
            do {
                $tmp_id_3  = $tmp_id_3 + 100;
                $tmp_record = \DB::table($table_name)->where('id', $tmp_id_3)->first();
                $this->info ( $tmp_id_3 );
            } while ($tmp_record->created_at < $end_time);
            $start_id = $tmp_id_3 - 100;

            $this->info('XXXXXXXXXXXXXXXXXXXXXXX');


            $tmp_id_4  = $start_id;
            $chunk = 10;
            do {
                $tmp_id_4  = $tmp_id_4 + $chunk;
                $tmp_record = \DB::table($table_name)->where('id', $tmp_id_4)->first();
                $this->info ( $tmp_id_4 );
                if ( empty( $tmp_record)){
                    $tmp_id_4  = $tmp_id_4 + $chunk;
                    $tmp_record = \DB::table($table_name)->where('id', $tmp_id_4)->first();
                    $this->info ( $tmp_id_4 );
                }
            } while ($tmp_record->created_at < $end_time);
            $start_id = $tmp_id_4 - $chunk;

            $this->info('??????????????????????');

            $this->info('start_id----'.$start_id);
            $end_id = $tmp_id_4;
            $this->info('end_id----'.$end_id);
            $max_id = \DB::table($table_name)
                ->where('id','>=', $start_id)
                ->where('id','<=', $end_id)
                ->where('created_at', '<=',$end_time )
                ->max('id');
            $this->info($date.'====>'. $max_id);


            // 维护 表的数据
            $record = \DB::table('statistic_schedule_tmp')
                ->where('key', 'table_nearly_max_id')
                ->where('created_date',$date )
                ->first();

            if (!empty($record)){
                $id = $record->id;
                $value = $record->value;
                $value = json_decode($value, true );
                $value[$table_name] = $max_id;
                \DB::table('statistic_schedule_tmp')
                    ->where('id', $id)
                    ->update( [
                        'value'=>json_encode($value)
                    ]);
                $this->info('');
            }
            $start_id = $max_id;
        }

        dd('done');
    }

}
