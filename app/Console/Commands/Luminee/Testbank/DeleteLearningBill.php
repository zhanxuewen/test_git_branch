<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class DeleteLearningBill extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:learning:bill {--with_testbank} {bill_ids} {conn=dev} {--is_testbank}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        $bill_ids = $this->argument('bill_ids');
        $with = $this->option('with_testbank');
        $conn = $this->argument('conn');
        $this->learn_pdo = $this->getConnPdo('learning', $conn);
        DB::setPdo($this->learn_pdo);
        if ($this->option('is_testbank')) {
            $ids = DB::table('testbank')->whereRaw("core_related_id in ($bill_ids)")->pluck('id')->toArray();
            $this->deleteTestbankS(implode(',', $ids));
            $this->info(count($ids));
            return;
        }
        if ($with) {
            $bills = DB::table('testbank_collection')->selectRaw('item_ids')->whereRaw("core_related_id in ($bill_ids)")->get();
            $this->output->progressStart(count($bills));
            foreach ($bills as $bill) {
                $this->deleteTestbankS($bill->item_ids);
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        }
        DB::table('testbank_collection')->whereRaw("core_related_id in ($bill_ids)")->delete();
    }

    protected function deleteTestbankS($ids)
    {
        DB::table('testbank_entity')->whereRaw("testbank_id in ($ids)")->delete();
        DB::table('testbank')->whereRaw("id in ($ids)")->delete();
    }

}
