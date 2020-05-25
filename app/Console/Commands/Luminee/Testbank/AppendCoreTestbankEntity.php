<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class AppendCoreTestbankEntity extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'append:core_testbank:entity {conn=dev}';

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
            $this->appendItem($count, $ids);
        }
        return $ids;
    }

    protected function appendItem($count, $ids)
    {
        $tmp = DB::table('user_quoted_testbank')->whereIn('id', $ids)->whereNull('deleted_at')->where('item_count', '<', $count)->selectRaw('id, item_count, item_ids')->get()->toArray();
        if (empty($tmp)) return;
        $insert = [];
        $__ids = [];
        $__tmp = [];
        $s_id = DB::table('user_quoted_testbank_entity')->max('id');
        $now = date('Y-m-d H:i:s');
        foreach ($tmp as $item) {
            $t_id = $item->id;
            for ($i = $item->item_count; $i < $count; $i++) {
                $insert[] = [
                    'quoted_testbank_id' => $t_id,
                    'testbank_item_value' => '{"index":' . $i . '}',
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }
            $__ids[] = $t_id;
            $__tmp[$t_id] = $item->item_ids;
        }
        DB::table('user_quoted_testbank_entity')->insert($insert);
        $items = DB::table('user_quoted_testbank_entity')->where('id', '>', $s_id)
            ->whereIn('quoted_testbank_id', $__ids)->selectRaw('id, quoted_testbank_id')->get()->toArray();
        $update = [];
        foreach ($items as $item) {
            $update[$item->quoted_testbank_id][] = $item->id;
        }
        $this->multiUpdate($update, $__tmp, $count);
        $this->info('Update: ' . count($update));
    }

    protected function multiUpdate($data, $__tmp, $count)
    {
        $ids = $when = '';
        foreach ($data as $t_id => $items) {
            $id = $t_id;
            $ids .= $id . ',';
            $_rep = $__tmp[$t_id] . ',' . implode(',', $items);
            $when .= " WHEN " . $id . " THEN '" . $_rep . "'";
        }
        $ids = rtrim($ids, ',');
        $query = "UPDATE user_quoted_testbank SET item_ids = (CASE id" . $when . " END), item_count = $count WHERE id IN (" . $ids . ")";
        \DB::select($query);
    }

}
