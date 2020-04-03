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
    protected $signature = 'rebuild:core_testbank:entity {testbank_ids} {--with_extra} {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'rebuild core testbank entity';

    protected $core_pdo;

    protected $index;

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
        $testbank_ids = [406339,966628,966629,966630,966631,966976,966977,966981,966982,967143,967144,967145,967146,645844,54171];
        $extra = $this->option('with_extra');
//        $index = $this->ask('Which index do you want to change? [ 1 | 1,2 | all ]');
        $index = 'all';
        $this->index = $index == 'all' ? 'all' : explode(',', $index);
        DB::setPdo($this->getConnPdo('core', $conn));
        foreach ($testbank_ids as $testbank_id) {
            $this->handleFunc($testbank_id, $extra);
        }
    }

    public function handleFunc($testbank_id, $extra)
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
        $ids = DB::table('user_quoted_testbank')->where('origin_id', $testbank_id)->where('origin_type', 'testbank')->whereNull('deleted_at')->pluck('id')->toArray();
        $this->line('Total ids : ' . count($ids));
        if ($extra && isset($items['extra']))
            $this->handleExtra($items['extra'], $ids);
        $this->handleItem($items, $ids);
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
            if ($this->index != 'all' && !in_array($k, $this->index))
                continue;
            $count = DB::table('user_quoted_testbank_entity')->whereIn('quoted_testbank_id', $ids)
                ->where($this->field, 'like', '%index":' . $k . '%')->whereNull('deleted_at')->update([$this->field => $item]);
            $this->info('Item ' . $key . ' : ' . $count);
        }
    }

}
