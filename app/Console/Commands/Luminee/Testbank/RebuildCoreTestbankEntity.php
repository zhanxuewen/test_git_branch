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
        $testbank_ids = [505724,505750,505754,505766,505768,505036,505072,505162,505605,505613,505623,505638,505640,505642,505648,505657,505666,505671,505678,505680,505682,505686,505701,505716,505719,505721,505723,505749,505753,505765,505767];
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
