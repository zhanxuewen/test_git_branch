<?php

namespace App\Console\Commands\Luminee\Finance;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class FinanceCleanBalance extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:clean:balance {conn=dev} ';

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
        $conn = $this->argument('conn');
        $balances = DB::setPdo($this->getConnPdo('learning', $conn))->table('finance_school_balance')->where('balance', '>', 0)->get();
        $this->output->progressStart(count($balances));
        $create = [];
        $date = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        foreach ($balances as $balance) {
            $amount = $balance->balance;
            $create[] = [
                'school_id' => $balance->school_id,
                'account_id' => 1,
                'type' => 'payment',
                'content' => "系统清零扣款 $amount 元, 目前剩余 0 元",
                'fee' => $amount,
                'before' => $amount,
                'after' => 0,
                'date' => $date,
                'status' => 2,
                'created_at' => $now,
                'updated_at' => $now
            ];
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        DB::table('finance_school_statement')->insert($create);
        DB::table('finance_school_balance')->where('school_id', '>', 0)->update(['balance' => 0]);
        $this->info('Success');
    }

}
