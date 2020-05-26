<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class SyncTestbankToCoreDev extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:testbank_to:core_dev {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync testbank and bill to core dev testbank';

    protected $online_pdo;
    protected $dev_pdo;

    protected $ids = [];

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
        $this->online_pdo = $this->getConnPdo('core', 'online');
        $this->dev_pdo = $this->getConnPdo('core', 'dev');
        $bill_ids = [];
        DB::setPdo($this->online_pdo)->table('testbank_collection')->whereIn('id', $bill_ids)
            ->whereNull('deleted_at')->orderBy('id')->chunk(1000, function ($bills) {
                $this->handleBill($bills);
            });
    }

    // Online - Dev
    protected function handleTestbank($testbank_s)
    {
        $this->info('Total: ' . count($testbank_s));
        $_ids = [];
        foreach ($testbank_s as $testbank) {
            $create = json_decode(json_encode($testbank), true);
            $id = $create['id'];
            $items = DB::setPdo($this->online_pdo)->table('testbank_entity')->where('testbank_id', $id)->whereNull('deleted_at')->get()->keyBy('id');
            unset($create['id']);
            $new_id = DB::setPdo($this->dev_pdo)->table('testbank')->insertGetId($create);
            $ids = $this->handleEntity($items, $create['item_ids'], $new_id);
            DB::table('testbank')->where('id', $new_id)->update(['item_ids' => $ids]);
            $_ids[] = $new_id;
            echo '+ ';
        }
        return $_ids;
    }

    // Dev
    protected function handleEntity($items, $ids, $new_id)
    {
        $item_create = [];
        $extra = array_diff(array_keys($items->toArray()), explode(',', $ids));
        foreach (array_merge($extra, explode(',', $ids)) as $id) {
            $item = $items[$id];
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

    // Online - Dev
    protected function handleBill($bills)
    {
        foreach ($bills as $bill) {
            $create = json_decode(json_encode($bill), true);
            $id = $create['id'];
            unset($create['id']);
            $item_ids = $create['item_ids'];
            if (strstr($item_ids, 'c')) {
                $this->error('With copy: ' . $id);
                continue;
            }
            $this->info('Trans testbank for bill ' . $id);
            $ids = $this->getIds($item_ids);
            $_id = $this->createBill($ids, $create);
            echo 'new bill: ' . $_id;
        }
    }

    // Online - Dev
    protected function getIds($item_ids)
    {
        $testbank_s = DB::setPdo($this->online_pdo)->table('testbank')->whereRaw("id in ($item_ids)")->whereNull('deleted_at')->get();;;
        return $this->handleTestbank($testbank_s);
    }

    protected function createBill($ids, $create)
    {
        $create['item_ids'] = implode(',', $ids);
        return DB::table('testbank_collection')->insertGetId($create);
    }

}
