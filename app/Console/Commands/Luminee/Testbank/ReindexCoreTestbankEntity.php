<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class ReindexCoreTestbankEntity extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reindex:core_testbank:entity {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reindex core testbank entity';

    protected $field = 'testbank_item_value';

    protected $level = [0, 1, 2];

    protected $loops = 2;

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
        DB::setPdo($this->getConnPdo('core', $conn));
        foreach ($testbank_ids as $testbank_id) {
            $this->comment('[[At]] ' . $testbank_id);
            $this->handleFunc($testbank_id);
        }
    }

    public function handleFunc($testbank_id)
    {
        $ids = [$testbank_id];
        $level = $this->level;
        for ($i = 0; $i <= $this->loops; $i++) {
            $this->comment("At Level $i");
            $ids = $this->queryTestbank($ids, $i, $level);
            if (is_null($ids)) {
                $this->line('***** Empty [break]****');
                break;
            }
        }
    }

    protected function queryTestbank($_ids, $now_l, $level)
    {
        if ($now_l == 0) {
            $ids = DB::table('testbank')->whereIn('id', $_ids)->whereNull('deleted_at')->pluck('id')->toArray();
        } else {
            $type = $now_l > 1 ? 'quotedTestbank' : 'testbank';
            $ids = DB::table('user_quoted_testbank')->whereIn('origin_id', $_ids)
                ->where('origin_type', $type)->whereNull('deleted_at')->pluck('id')->toArray();
        }
        if (empty($ids)) return null;
        $this->line('Total ids : ' . count($ids));
        if (in_array($now_l, $level)) {
            $this->handleItemIndex($now_l, $ids);
            $this->handleItemIndex($now_l, $ids);
        }
        return $ids;

    }

    protected function handleItemIndex($now_l, $ids)
    {
        $table = $now_l == 0 ? 'testbank_entity' : 'user_quoted_testbank_entity';
        $key = $now_l == 0 ? 'testbank_id' : 'quoted_testbank_id';
        $items = DB::table($table)->whereIn($key, $ids)->selectRaw("id, $key, testbank_extra_value, testbank_item_value")->get();
        $apd = $upd = $tnk = [];
        foreach ($items as $item) {
            if (empty($item->testbank_item_value)) continue;
            $json = json_decode($item->testbank_item_value);
            $t_id = $item->$key;
            isset($tnk[$t_id]) ? $tnk[$t_id] += 1 : $tnk[$t_id] = 0;
            $now_k = $tnk[$t_id];
            $_id_ = $item->id;
            !isset($json->index) ?
                $apd['k' . $now_k][] = $_id_ :
                ($json->index == $now_k ? null : $upd['k' . $now_k][] = $_id_);
        }
        if (!empty($apd))
            $this->appendIndex($apd, $table);
        if (!empty($upd))
            $this->updateIndex($upd, $table);
    }

    protected function appendIndex($apd, $table)
    {
        foreach ($apd as $key => $ids) {
            $k = str_replace('k', '', $key);
            $search = '}';
            $replace = ',"index":' . $k . '}';
            $update = DB::raw("REPLACE(`testbank_item_value`, '$search',  '$replace')");
            $count = DB::table($table)->whereIn('id', $ids)->update(['testbank_item_value' => $update]);
            $this->info('Append Item ' . $k . ' : ' . $count);
        }
    }

    protected function updateIndex($upd, $table)
    {
        foreach ($upd as $key => $ids) {
            $k = str_replace('k', '', $key);
            $search = 'index":0';
            $replace = 'index":' . $k;
            $update = DB::raw("REPLACE(`testbank_item_value`, '$search',  '$replace')");
            $count = DB::table($table)->whereIn('id', $ids)->update(['testbank_item_value' => $update]);
            $this->info('Update Item ' . $k . ' : ' . $count);
        }
    }

}
