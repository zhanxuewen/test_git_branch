<?php

namespace App\Console\Commands\Luminee\Resource;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class SyncResourceAudioToLearning extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:resource_audio:to_learning {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync resource audio to learning resource';

    protected $core_pdo;
    protected $learn_pdo;

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
        $conn = $this->argument('conn');
        if (strstr($conn, '-')) {
            list($from, $to) = explode('-', $conn);
        } else {
            $from = $to = $conn;
        }
        $this->core_pdo = $this->getConnPdo('core', $from);
        $this->learn_pdo = $this->getConnPdo('learning', $to);
        $max_learn = DB::setPdo($this->learn_pdo)->table('resource_audio')->max('id');
        $max_core = DB::setPdo($this->core_pdo)->table('resource_audio')->max('id');
        if ($max_core > $max_learn) {
            $this->handleResource($max_core, $max_learn);
            $this->info('Resource audio sync complete!');
        } else {
            $this->line('No resource to be synced');
        }
    }

    protected function handleResource($max, $min)
    {
        $this->output->progressStart($max - $min);
        $resources = DB::table('resource_audio')->where('id', '>', $min)->where('id', '<=', $max)->get();
        $create = [];
        foreach ($resources as $resource) {
            $create[] = json_decode(json_encode($resource), true);
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        DB::setPdo($this->learn_pdo)->table('resource_audio')->insert($create);
    }

}
