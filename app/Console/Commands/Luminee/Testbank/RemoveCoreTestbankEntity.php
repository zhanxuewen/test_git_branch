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

    protected $now;

    protected $field = 'testbank_item_value';

    protected $level = [1, 2, 3, 4, 5, 6];

    protected $loops = 6;

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
        $testbank_ids = [];
        $this->now = date('Y-m-d H:i:s');
        DB::setPdo($this->getConnPdo('core', $conn));
        foreach ($testbank_ids as $testbank_id) {
            $this->comment('[[At]] ' . $testbank_id);
            $this->handleFunc($testbank_id);
        }
    }

    public function handleFunc($testbank_id)
    {
        $count = DB::table('testbank_entity')->where('testbank_id', $testbank_id)
            ->where('testbank_item_value', 'like', '%index":%')->whereNull('deleted_at')->count();
        $ids = [$testbank_id];
        $level = $this->level;
        for ($i = 1; $i <= $this->loops; $i++) {
            $this->comment("At Level $i");
            $ids = $this->queryHandleQuoted($count, $ids, $i, $level);
            if (is_null($ids)) {
                $this->line('***** Empty [break]****');
                break;
            }
        }
    }

    protected function queryHandleQuoted($count, $_ids, $now_l, $level)
    {
        $type = $now_l > 1 ? 'quotedTestbank' : 'testbank';
        $ids = DB::table('user_quoted_testbank')->whereIn('origin_id', $_ids)->where('origin_type', $type)
            ->whereNull('deleted_at')->pluck('id')->toArray();
        if (empty($ids)) return null;
        $this->line('Total ids : ' . count($ids));
        if (in_array($now_l, $level)) {
            $this->removeItem($count, $ids);
            $this->removeItem($count, $ids);
        }
        return $ids;
    }

    protected function removeItem($count, $ids)
    {
        $tmp = DB::table('user_quoted_testbank')->whereIn('id', $ids)->whereNull('deleted_at')->where('item_count', '>', $count)->selectRaw('id, item_count, item_ids')->get()->toArray();
        if (empty($tmp)) return;
        $ent_ids = DB::table('user_quoted_testbank_entity')->whereIn('quoted_testbank_id', $ids)->whereRaw("testbank_item_value->'$.index' >= " . $count)->whereNull('deleted_at')->pluck('id')->toArray();
        $update = [];
        foreach ($tmp as $item) {
            $__ids = [];
            foreach (explode(',', $item->item_ids) as $__id) {
                !in_array($__id, $ent_ids) ? $__ids[] = $__id : null;
            }
            $update[] = ['id' => $item->id, 'item_ids' => implode(',', $__ids)];
        }
        DB::table('user_quoted_testbank_entity')->whereIn('id', $ent_ids)->update(['deleted_at' => $this->now]);
        $this->multiUpdate($update, $count);
        $this->info('Update: ' . count($update));
    }

    protected function multiUpdate($data, $count)
    {
        $ids = $when = '';
        foreach ($data as $column) {
            $id = $column['id'];
            $ids .= $id . ',';
            $when .= " WHEN " . $id . " THEN '" . $column['item_ids'] . "'";
        }
        $ids = rtrim($ids, ',');
        $query = "UPDATE user_quoted_testbank SET item_ids = (CASE id" . $when . " END), item_count = $count WHERE id IN (" . $ids . ")";
        \DB::select($query);
    }

}
