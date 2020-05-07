<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class RebuildCoreTestbankEntity extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:core_testbank:entity {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'rebuild core testbank entity';

    protected $field = 'testbank_item_value';

    protected $level = [1,2];

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
        $testbank_ids = [18513,18302,18350,18398,18429,18659,18610];
        DB::setPdo($this->getConnPdo('core', $conn));
        foreach ($testbank_ids as $testbank_id) {
            $this->comment('[[At]] '.$testbank_id);
            $this->handleFunc($testbank_id);
        }
    }

    public function handleFunc($testbank_id)
    {
        $entities = DB::table('testbank_entity')->where('testbank_id', $testbank_id)->whereNull('deleted_at')->get();
        $items = [];
        $key = '';
        $_key = $this->field;
        foreach ($entities as $entity) {
            if (empty($entity->testbank_item_value)) {
                $items['extra'] = $entity->testbank_extra_value;
            } else {
                $json = json_decode($entity->$_key, true);
                if ($key == 'index_' . $json['index']) {
                    $this->error('Index Has Exist!');
                    exit();
                }
                $items[$key = 'index_' . $json['index']] = $entity->$_key;
            }
        }
        $ids = [$testbank_id];
        $level = $this->level;
        for ($i = 1; $i <= $this->loops; $i++) {
            $this->comment("At Level $i");
            $ids = $this->queryHandleQuoted($items, $ids, $i, $level);
            if (is_null($ids)){
                $this->line('***** Empty [break]****');
                break;
            }
        }
    }

    protected function queryHandleQuoted($items, $_ids, $now_l, $level)
    {
        $type = $now_l > 1 ? 'quotedTestbank' : 'testbank';
        $ids = DB::table('user_quoted_testbank')->whereIn('origin_id', $_ids)->where('origin_type', $type)->whereNull('deleted_at')->pluck('id')->toArray();
        if (empty($ids)) return null;
        $this->line('Total ids : ' . count($ids));
        if (in_array($now_l, $level)) {
            if (isset($items['extra']))
                $this->handleExtra($items['extra'], $ids);
            $this->handleItem($items, $ids);
        }
        return $ids;
    }

    protected function handleExtra($extra, $ids)
    {
        $count = DB::table('user_quoted_testbank_entity')->whereIn('quoted_testbank_id', $ids)
            ->whereNull('testbank_item_value')->whereNull('deleted_at')->update(['testbank_extra_value' => $extra]);
        $this->info('Extra : ' . $count);
    }

    protected function handleItem($items, $ids)
    {
        foreach ($items as $key => $item) {
            if ($key == 'extra')
                continue;
            $k = str_replace('index_', '', $key);
            $count = DB::table('user_quoted_testbank_entity')->whereIn('quoted_testbank_id', $ids)
                ->where($this->field, 'like', '%index":' . $k . '%')->whereNull('deleted_at')->update([$this->field => $item]);
            $this->info('Item ' . $key . ' : ' . $count);
        }
    }

}
