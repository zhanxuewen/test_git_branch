<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class RemoveCoreTestbankEntity extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:core_testbank:entity {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove core testbank entity';

    protected $core_pdo;

    protected $index;

    protected $now;

    protected $field = 'testbank_item_value';

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
        $testbank_ids = [505724,505750,505754,505766,505768,505036,505072,505162,505605,505613,505623,505638,505640,505642,505648,505657,505666,505671,505678,505680,505682,505686,505701,505716,505719,505721,505723,505749,505753,505765,505767];
        $this->index = 'all';
        $this->now = date('Y-m-d H:i:s');
        DB::setPdo($this->getConnPdo('core', $conn));
        foreach ($testbank_ids as $testbank_id) {
            $this->handleFunc($testbank_id);
        }
    }

    public function handleFunc($testbank_id)
    {
        $count = DB::table('testbank_entity')->where('testbank_id', $testbank_id)
            ->where('testbank_item_value', 'like', '%index":%')->whereNull('deleted_at')->count();
        $tmp = DB::table('user_quoted_testbank')->where('origin_id', $testbank_id)->where('origin_type', 'testbank')
            ->whereNull('deleted_at')->where('item_count', '>', $count)->selectRaw('id, item_ids')->get()->toArray();
        $ids = [];
        foreach ($tmp as $item) {
            $ids[] = $item->id;
        }
        $this->line('Total ids : ' . count($ids));
        $this->handleItem($count - 1, $ids, $tmp);
    }

    protected function handleItem($max, $ids, $tmp)
    {
        $ent_ids = DB::table('user_quoted_testbank_entity')->whereIn('quoted_testbank_id', $ids)->whereRaw("testbank_item_value->'$.index' > " . $max)->whereNull('deleted_at')->pluck('id')->toArray();
        $update = [];
        foreach ($tmp as $item) {
            $item_ids = trim(str_replace($ent_ids, [''], $item->item_ids), ',');
            $update[] = ['id' => $item->id, 'item_ids' => $item_ids];
        }
        DB::table('user_quoted_testbank_entity')->whereIn('id', $ent_ids)->update(['deleted_at' => $this->now]);
        $this->multiUpdate($update);
        $this->info('Update: ' . count($update));
    }

    protected function multiUpdate($data)
    {
        $ids = $when = '';
        foreach ($data as $column) {
            $id = $column['id'];
            $ids .= $id . ',';
            $when .= " WHEN " . $id . " THEN '" . $column['item_ids'] . "'";
        }
        $ids = rtrim($ids, ',');
        $query = "UPDATE user_quoted_testbank SET item_ids = (CASE id" . $when . " END) WHERE id IN (" . $ids . ")";
        \DB::select($query);
    }

}
