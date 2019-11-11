<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class FixLearningItems extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:learning:items {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $core_pdo;
    protected $learn_pdo;

    protected $flag;

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
        $conn = $this->argument('conn');
        $this->core_pdo = $this->getConnPdo('core', $conn);
        $this->learn_pdo = $this->getConnPdo('learning', $conn);
        $this->flag = 'question';

        $core_ids = [772595];
        $this->fix($core_ids);

    }

    protected function fix($core_ids)
    {
        $c_t_s = DB::setPdo($this->core_pdo)->table('testbank')->whereIn('id', $core_ids)->selectRaw('id, item_ids')->get();
        $this->c_entities = DB::table('testbank_entity')->whereIn('testbank_id', $core_ids)->whereNull('testbank_extra_value')->whereNull('deleted_at')->get()->keyBy('id')->toArray();
        $l_t_ids = DB::setPdo($this->learn_pdo)->table('testbank')->selectRaw('id, core_related_id, item_ids')->whereIn('core_related_id', $core_ids)->get()->keyBy('core_related_id');
        $this->l_entities = DB::table('testbank_entity')->join('testbank', 'testbank.id', '=', 'testbank_entity.testbank_id')->selectRaw('testbank_entity.*')->whereIn('core_related_id', $core_ids)->whereNull('testbank_extra_value')->whereNull('testbank_entity.deleted_at')->get()->groupBy('testbank_id')->toArray();
        $this->output->progressStart(count($c_t_s));
        foreach ($c_t_s as $c_t) {
            $c_id = $c_t->id;
            $c_items = $c_t->item_ids;
            $l_t_id = $l_t_ids[$c_id]->id;
            $l_t_items = $l_t_ids[$c_id]->item_ids;
            $l_entities = $this->l_entities[$l_t_id];
            $right = $this->getEntities($c_items, $l_entities);
            if ($right != $l_t_items) {
                DB::table('testbank')->where('id', $l_t_id)->update(['item_ids' => $right]);
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

    protected function getEntities($c_items, $l_entities)
    {
        $array = [];
        foreach ($l_entities as $l_entity) {
            $item = $l_entity->testbank_item_value;
            $json = json_decode($item, true);
            $array[$json[$this->flag]] = $l_entity->id;
        }
        $right = [];
        foreach (explode(',', $c_items) as $id) {
            $item = $this->c_entities[$id]->testbank_item_value;
            $json = json_decode($item, true);
            $right[] = $array[$json[$this->flag]];
        }
        return implode(',', $right);
    }

}
