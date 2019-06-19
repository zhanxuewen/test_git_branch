<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class SyncTestbankToLearning extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:testbank_to:learning {account_id} {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync testbank and bill to learning testbank';

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
        $account_id = $this->argument('account_id');
        $connections = [
            'online' => ['core' => 'online', 'learning' => 'online_learning'],
            'dev' => ['core' => 'dev', 'learning' => 'dev_learning']
        ];
        $conn = $this->argument('conn');
        $this->core_pdo = $this->getPdo($connections[$conn]['core']);
        $this->learn_pdo = $this->getPdo($connections[$conn]['learning']);
        if ($account_id == 0) {
            $testbank_ids = [];
            $bill_ids = [];
            DB::setPdo($this->core_pdo)->table('testbank')->whereIn('id', $testbank_ids)
                ->whereNull('deleted_at')->orderBy('id')->chunk(1000, function ($testbank_s) {
                    $this->handleTestbank($testbank_s);
                });
            DB::setPdo($this->core_pdo)->table('testbank_collection')->whereIn('id', $bill_ids)
                ->whereNull('deleted_at')->orderBy('id')->chunk(1000, function ($bills) {
                    $this->handleBill($bills);
                });
        } else {
            DB::setPdo($this->core_pdo)->table('testbank')->where('account_id', $account_id)
                ->whereNull('deleted_at')->orderBy('id')->chunk(1000, function ($testbank_s) {
                    $this->handleTestbank($testbank_s);
                });
            DB::setPdo($this->core_pdo)->table('testbank_collection')->where('account_id', $account_id)
                ->whereNull('deleted_at')->orderBy('id')->chunk(1000, function ($bills) {
                    $this->handleBill($bills);
                });
        }

    }

    protected function handleTestbank($testbank_s)
    {
        $this->info('Total: ' . count($testbank_s));
        foreach ($testbank_s as $testbank) {
            $create = json_decode(json_encode($testbank), true);
            $id = $create['id'];
            $items = DB::setPdo($this->core_pdo)->table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->get();
            $create['core_related_id'] = $id;
            $create['is_public'] = 1;
            unset($create['id'], $create['system_label_ids']);
            $t_bank = DB::setPdo($this->learn_pdo)->table('testbank')->where('core_related_id', $id)->whereNull('deleted_at')->first();
            if (!$t_bank) {
                $new_id = DB::table('testbank')->insertGetId($create);
                $ids = $this->handleEntity($items, $new_id);
                DB::table('testbank')->where('id', $new_id)->update(['item_ids' => $ids]);
                echo '+ ';
            } else {
                echo '- ';
            }
        }
    }

    protected function handleEntity($items, $new_id)
    {
        $item_create = [];
        foreach ($items as $item) {
            $item_create[] = [
                'testbank_id' => $new_id,
                'testbank_extra_value' => $item->testbank_extra_value,
                'testbank_item_value' => $item->testbank_item_value,
                'fix' => $item->fix,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'deleted_at' => $item->deleted_at
            ];
        }
        DB::table('testbank_entity')->insert($item_create);
        return DB::table('testbank_entity')->selectRaw('GROUP_CONCAT(id) as ids')->where('testbank_id', $new_id)
            ->whereNotNull('testbank_item_value')->whereNull('deleted_at')->first()->ids;
    }

    protected function handleBill($bills)
    {
        foreach ($bills as $bill) {
            $create = json_decode(json_encode($bill), true);
            $id = $create['id'];
            $create['core_related_id'] = $id;
            $create['is_public'] = 1;
            unset($create['id'], $create['system_label_ids']);
            $b_ill = DB::setPdo($this->learn_pdo)->table('testbank_collection')
                ->where('core_related_id', $id)->whereNull('deleted_at')->first();
            if (!$b_ill) {
                $item_ids = $create['item_ids'];
                if (strstr($item_ids, 'c')) {
                    $this->error('With copy: ' . $id);
                    continue;
                }
                $ids = $this->getIds($item_ids, $id);
                if ($ids === false) continue;
                $this->createBill($ids, $create);
                echo '+ ';
            } else {
                echo '- ';
            }
        }
    }

    protected function getIds($item_ids, $id)
    {
        $ids = DB::table('testbank')->selectRaw('id, core_related_id')
            ->whereRaw("core_related_id in ($item_ids)")->whereNull('deleted_at')->get()->keyBy('core_related_id')->toArray();
        $keys = array_keys($ids);
        $diff = array_diff(explode(',', $item_ids), $keys);
        if (!empty($diff)) {
            $this->info('Trans testbank for bill ' . $id);
            $testbank_s = DB::setPdo($this->core_pdo)->table('testbank')->whereIn('id', $diff)->whereNull('deleted_at')->get();
            $this->handleTestbank($testbank_s);
            $ids = DB::setPdo($this->learn_pdo)->table('testbank')->selectRaw('id, core_related_id')
                ->whereRaw("core_related_id in ($item_ids)")->whereNull('deleted_at')->get()->keyBy('core_related_id')->toArray();
            $keys = array_keys($ids);
            $diff = array_diff(explode(',', $item_ids), $keys);
            if (!empty($diff)) {
                $this->error('Error items bill ' . $id);
                return false;
            }
        }
        $_ids = [];
        foreach (explode(',', $item_ids) as $id) {
            $_ids[] = $ids[$id];
        }
        return $_ids;
    }

    protected function createBill($ids, $create)
    {
        $_ids = [];
        foreach ($ids as $_id) {
            $_ids[] = $_id->id;
        }
        $create['item_ids'] = implode(',', $_ids);
        DB::table('testbank_collection')->insert($create);
    }

}
