<?php

namespace App\Console\Commands\Statistic;

use App\Console\Common\ZXZJ\SchoolAccountant;
use App\Console\Schedules\Learning\ExportBookLearningProcess;
use App\Console\Schedules\Monitor\ScheduleHeartbeat;
use App\Foundation\Excel;
use App\Foundation\PdoBuilder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CollectUserAction extends Command
{
    use Excel;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collect:user:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获得用的行为';

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
        config(['database.default' => 'local']);

        /**
        $keys = Redis::keys('qianci_monitor_data*');
        sort($keys);
        foreach ($keys as $key ){
            $list = Redis::command('lrange', [$key, 0, -1]);
            $list = array_reverse($list);
            foreach ( $list as $item){
                $data = json_decode( $item,true);
                $timestamps = $data['timestamps'];
                $time = date('Y-m-d H:i:s', $timestamps);
                if ( 1614232539 < $timestamps) {
                \DB::table('ods')->insert([
                    'account' => json_decode( $data['request']['account'] ,true)['account_id'],
                    'request' => json_encode($data['request']),
                    'request_url' => $data['request']['uri'],
                    'platform' => $data['request']['platform'],
                    'Ip' => $data['request']['Ip'],
                    'timestamps' => $timestamps,
                    'response' => json_encode($data['response'] ),
                    'mid_data' => json_encode($data['mid_data'][0] ),
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
                }
                echo '+';
            }
            $this->info('-----------------------');
        }



dd('done');
**/

        $keys = Redis::keys('core_monitor_data*');
        sort($keys);

        config(['database.default' => 'local']);
        foreach ($keys as $key ){
            $list = Redis::command('lrange', [$key, 0, -1]);
            $list = array_reverse($list);
            foreach ( $list as $item){
                $data = json_decode( $item,true);
                $timestamps = $data['timestamps'];
                $time = date('Y-m-d H:i:s', $timestamps);
                if ( 1614234734 < $timestamps && !empty($data['request'])) {

                \DB::table('ods')->insert([
                    'account' => $data['request']['account'],
                    'request' => json_encode($data['request']),
                    'request_url' => $data['request']['uri'],
                    'platform' => $data['request']['platform'],
                    'Ip' => $data['request']['Ip'],
                    'timestamps' => $timestamps,
                    'response' => json_encode($data['response'] ),
                    'mid_data' => json_encode($data['mid_data'][0] ),
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
                }
                echo '+';
            }
            $this->info('-----------------------');
        }

        dd($keys);


    }

}
