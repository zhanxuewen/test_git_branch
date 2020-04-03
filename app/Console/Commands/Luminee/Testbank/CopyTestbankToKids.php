<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class CopyTestbankToKids extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copy:testbank_to:kids {school_id} {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $core_pdo;
    protected $kids_pdo;

    protected $now;

    protected $games = [26, 19, 20, 4, 23, 18, 22, 6, 9];
    protected $map = [];

    protected $school_id;

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
        $school_id = $this->argument('school_id');
        $this->core_pdo = $this->getConnPdo('core', $conn);
        $this->kids_pdo = $this->getConnPdo('kids', $conn);

        $kids_school = DB::setPdo($this->kids_pdo)->table('school')->where('core_school_id', $school_id)->first();
        if (empty($kids_school)) {
            $this->error('Can not find related school!');
            exit();
        }
        $this->school_id = $kids_school->id;
        $this->handleSchoolTestbank($school_id);
        $this->handleSchoolBill($school_id);

    }

    protected function handleSchoolBill($school_id)
    {
        $quotes = DB::setPdo($this->core_pdo)->table('school_testbank')->where('school_id', $school_id)->where('type', 'bill')->pluck('item_id')->toArray();
        $exclude = DB::setPdo($this->kids_pdo)->table('testbank_collection')->whereIn('core_related_id', $quotes)->pluck('core_related_id')->toArray();
        $quote_ids = array_diff($quotes, $exclude);
        $bank_ids = DB::setPdo($this->core_pdo)->table('testbank_collection')->whereIn('id', $quote_ids)->pluck('item_ids')->toArray();
        $ids = array_unique(explode(',', str_replace('c', '', implode(',', $bank_ids))));
        $this->map = $this->getQuotedMap($ids);
        $this->output->progressStart(count($quote_ids));
        foreach (array_chunk($quote_ids, 30) as $item_ids) {
            $this->now = date('Y-m-d H:i:s');
            $this->handleBill($item_ids);
            $this->output->progressAdvance(30);
        }
        $this->output->progressFinish();
    }

    protected function getQuotedMap($ids)
    {
        $rows = DB::setPdo($this->kids_pdo)->table('testbank')->whereIn('core_related_id', $ids)->get(['id', 'core_related_id', 'game_id']);
        $map = [];
        foreach ($rows as $row) {
            $map[$row->core_related_id] = [$row->id, $row->game_id];
        }
        return $map;
    }

    protected function handleBill($item_ids)
    {
        $items = DB::setPdo($this->core_pdo)->table('testbank_collection')->whereIn('id', $item_ids)->whereNull('deleted_at')->get();
        $create = $this->buildBill($items, $this->school_id);
        DB::setPdo($this->kids_pdo)->table('testbank_collection')->insert($create);
    }

    protected function buildBill($items, $school_id)
    {
        $create = [];
        foreach ($items as $item) {
            list($ids, $tmp) = $this->getItemIds($item->item_ids);
            $create[] = [
                'name' => $item->name,
                'core_related_id' => $item->id,
                'school_id' => $school_id,
                'keyword' => $item->keyword,
                'description' => $item->description,
                'item_ids' => $ids,
                'tmp_item_ids' => $tmp,
                'type' => $item->type,
                'account_id' => $item->account_id,
                'favorite_num' => $item->favorite_num,
                'is_recommend' => $item->is_recommend,
                'recommend_at' => $item->recommend_at,
                'created_at' => $this->now,
                'updated_at' => $this->now
            ];
        }
        return $create;
    }

    protected function getItemIds($item_ids)
    {
        $ids = $tmp = [];
        foreach (explode(',', str_replace('c', '', $item_ids)) as $related) {
            list($id, $game_id) = $this->map[$related];
            $tmp[] = $id;
            if (in_array($game_id, $this->games))
                $ids[] = $id;
        }
        return [implode(',', $ids), implode(',', $tmp)];
    }

    /**
     * Handle School Testbank
     *
     * @param $school_id
     */
    protected function handleSchoolTestbank($school_id)
    {
        $quotes = DB::setPdo($this->core_pdo)->table('school_testbank')->where('school_id', $school_id)->where('type', 'quotedTestbank')->pluck('item_id')->toArray();
        $exclude = DB::setPdo($this->kids_pdo)->table('testbank')->whereIn('core_related_id', $quotes)->pluck('core_related_id')->toArray();
        $quote_ids = array_diff($quotes, $exclude);
        $this->output->progressStart(count($quote_ids));
        foreach (array_chunk($quote_ids, 30) as $item_ids) {
            $this->now = date('Y-m-d H:i:s');
            $this->handleTestbank($item_ids);
            $this->output->progressAdvance(30);
        }
        $this->output->progressFinish();
    }

    protected function handleTestbank($item_ids)
    {
        $items = DB::setPdo($this->core_pdo)->table('user_quoted_testbank')->whereIn('id', $item_ids)->whereNull('deleted_at')->get();
        $create = $this->buildTestbank($items, $this->school_id);
        DB::setPdo($this->kids_pdo)->table('testbank')->insert($create);
        $rows = DB::setPdo($this->kids_pdo)->table('testbank')->whereIn('core_related_id', $item_ids)->get(['id', 'core_related_id']);
        $map = $this->getTestbankMapAndIds($rows);
        $this->handleEntity($item_ids, $map['relates']);
        $entity_map = $this->getEntityMap($map['ids']);
        $update = $this->buildItemIds($items, $entity_map);
        $this->multiUpdate($update);
    }

    protected function getTestbankMapAndIds($rows)
    {
        $relates = $ids = [];
        foreach ($rows as $row) {
            $relates[$row->core_related_id] = $row->id;
            $ids[] = $row->id;
        }
        return ['ids' => $ids, 'relates' => $relates];
    }

    protected function buildTestbank($items, $school_id)
    {
        $create = [];
        foreach ($items as $item) {
            $create[] = [
                'name' => $item->name,
                'same_name_index' => $item->same_name_index,
                'core_related_id' => $item->id,
                'school_id' => $school_id,
                'keyword' => $item->keyword,
                'description' => $item->description,
                'game_id' => $item->game_id,
                'game_type_id' => $item->game_type_id,
                'mode' => $item->mode,
                'game_mode_id' => $item->game_mode_id,
                'item_count' => $item->item_count,
                'item_ids' => '',
                'favorite_num' => $item->favorite_num,
                'is_recommend' => 0,
                'account_id' => 0,
                'created_at' => $this->now,
                'updated_at' => $this->now
            ];
        }
        return $create;
    }

    protected function buildItemIds($items, $relates)
    {
        $update = [];
        foreach ($items as $item) {
            $ids = [];
            foreach (explode(',', $item->item_ids) as $id) {
                $ids[] = $relates[$id];
            }
            $update[] = [
                'core_related_id' => $item->id,
                'item_ids' => implode(',', $ids)
            ];
        }
        return $update;
    }

    protected function handleEntity($item_ids, $relates)
    {
        $entities = DB::setPdo($this->core_pdo)->table('user_quoted_testbank_entity')->whereIn('quoted_testbank_id', $item_ids)->whereNull('deleted_at')->get();
        $create = $this->buildEntity($entities, $relates);
        DB::setPdo($this->kids_pdo)->table('testbank_entity')->insert($create);
    }

    protected function buildEntity($entities, $relates)
    {
        $create = [];
        foreach ($entities as $entity) {
            $create[] = [
                'testbank_id' => $relates[$entity->quoted_testbank_id],
                'core_related_id' => $entity->id,
                'testbank_extra_value' => $entity->testbank_extra_value,
                'testbank_item_value' => $entity->testbank_item_value,
                'created_at' => $this->now,
                'updated_at' => $this->now
            ];
        }
        return $create;
    }

    protected function getEntityMap($testbank_ids)
    {
        $rows = DB::setPdo($this->kids_pdo)->table('testbank_entity')->whereIn('testbank_id', $testbank_ids)->get(['id', 'core_related_id']);
        $ids = [];
        foreach ($rows as $row) {
            $ids[$row->core_related_id] = $row->id;
        }
        return $ids;
    }

    protected function multiUpdate($data)
    {
        $ids = $when = '';
        foreach ($data as $column) {
            $id = $column['core_related_id'];
            $ids .= $id . ',';
            $when .= " WHEN " . $id . " THEN '" . $column['item_ids'] . "'";
        }
        $ids = rtrim($ids, ',');
        $query = "UPDATE testbank SET item_ids = (CASE core_related_id" . $when . " END) WHERE core_related_id IN (" . $ids . ")";
        \DB::setPdo($this->kids_pdo)->select($query);
    }

}
