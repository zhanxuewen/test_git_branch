<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class RebuildLearningEntityOrder extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:learning_entity:order {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $core_pdo;
    protected $learn_pdo;

    protected $wrong_ids = [];
    protected $wrong;

    protected $c_entities = [];
    protected $l_entities = [];

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
        $connections = [
            'online' => ['core' => 'online', 'learning' => 'online_learning'],
            'dev' => ['core' => 'dev', 'learning' => 'dev_learning']
        ];
        $conn = $this->argument('conn');
        $this->core_pdo = $this->getPdo($connections[$conn]['core']);
        $this->learn_pdo = $this->getPdo($connections[$conn]['learning']);

        for ($i = 0; $i <= 1; $i++) {
            $ids = [500 * $i + 1, 500 * ($i + 1)];

            $this->getWrong($ids);
            if (!empty($this->wrong_ids)) {
                $this->rebuild();
            }
        }

    }

    protected function getWrong($ids)
    {
        $this->wrong_ids = [];
        $this->wrong = [];
        $core_ids = DB::setPdo($this->learn_pdo)->table('testbank')->whereBetween('id', $ids)->pluck('core_related_id')->toArray();
        $testbank_s = DB::setPdo($this->core_pdo)->table('testbank')->selectRaw('id, item_ids')->whereIn('id', $core_ids)->get();
        $this->output->progressStart(count($testbank_s));
        foreach ($testbank_s as $item) {
            $i_ids = $item->item_ids;
            $array = explode(',', $i_ids);
            sort($array);
            if ($i_ids != implode(',', $array)) {
                $this->wrong_ids[] = $item->id;
                $this->wrong[] = ['id' => $item->id, 'item' => $i_ids];
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

    protected function rebuild()
    {
        $this->c_entities = DB::table('testbank_entity')->whereIn('testbank_id', $this->wrong_ids)->whereNull('testbank_extra_value')->whereNull('deleted_at')->get()->keyBy('id')->toArray();
        $l_testbank_s = DB::setPdo($this->learn_pdo)->table('testbank')->selectRaw('id, core_related_id, item_ids')->whereIn('core_related_id', $this->wrong_ids)->get()->keyBy('core_related_id');
        $this->l_entities = DB::table('testbank_entity')->join('testbank', 'testbank.id', '=', 'testbank_entity.testbank_id')->selectRaw('testbank_entity.*')->whereIn('core_related_id', $this->wrong_ids)->whereNull('testbank_extra_value')->whereNull('testbank_entity.deleted_at')->get()->keyBy('id')->toArray();
        $this->output->progressStart(count($this->wrong));
        $wrong_core_ids = [];
        $update = [];
        foreach ($this->wrong as $wrong) {
            $c_id = $wrong['id'];
            $c_ids = $wrong['item'];
            $l_test = $l_testbank_s[$c_id];
            $wrong_item_ids = $l_test->item_ids;
            $right = $this->getEntities($c_ids, $wrong_item_ids);
            if ($right != $wrong_item_ids) {
                $update[] = ['id' => $l_test->id, 'item_ids' => $right];
                $wrong_core_ids[] = $c_id;
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        if (!empty($update)) {
            $this->multiUpdate($update);
        }
        if (!empty($wrong_core_ids)) {
            dd(implode(',', $wrong_core_ids));
        }
    }

    protected function getEntities($c_ids, $l_ids)
    {
        $wrong = [];
        foreach (explode(',', $l_ids) as $id) {
            $item = $this->l_entities[$id]->testbank_item_value;
            $json = json_decode($item, true);
            if (!isset($json['sentence'])) {
                $wrong[$json['answer']] = $id;
            } else {
                $wrong[$json['sentence']] = $id;
            }
        }
        $right = [];
        foreach (explode(',', $c_ids) as $id) {
            $item = $this->c_entities[$id]->testbank_item_value;
            $json = json_decode($item, true);
            if (!isset($json['sentence'])) {
                $right[] = $wrong[$json['answer']];
            } else {
                $right[] = $wrong[$json['sentence']];
            }
        }
        return implode(',', $right);
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
        $query = "UPDATE testbank SET item_ids = (CASE id" . $when . " END) WHERE id IN (" . $ids . ")";
        \DB::select($query);
    }

}
