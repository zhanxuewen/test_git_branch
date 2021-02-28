<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class RebuildLearningAssessmentEntityOrder extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:learning_assessment_entity:order {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $learn_pdo;

    protected $wrong_ids = [];
    protected $wrong;

    protected $w_entities = [];

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
        $this->learn_pdo = $this->getConnPdo('learning',$conn);

        $ids = [772595];


        $chunk = array_chunk($ids, 100);
        foreach ($chunk as $_ids) {
            $this->getWrong($_ids);
            if (!empty($this->wrong)) {
                $this->rebuild();
            }
        }


    }

    protected function getWrong($ids)
    {
        $this->wrong_ids = [];
        $this->wrong = [];
        $r_testbank_s = DB::setPdo($this->learn_pdo)->table('testbank')->selectRaw('id, item_ids')->whereIn('core_related_id', $ids)->get();
        $this->output->progressStart(count($r_testbank_s));
        foreach ($r_testbank_s as $r_testbank) {
            $this->wrong_ids[] = $r_testbank->id;
            $this->wrong[] = ['id' => $r_testbank->id, 'item' => $r_testbank->item_ids];
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

    protected function rebuild()
    {
        $this->output->progressStart(count($this->wrong));
        $wrong_core_ids = [];
        $update = [];
        foreach ($this->wrong as $wrong) {
            $r_id = $wrong['id'];
            $r_ids = $wrong['item'];
            $questions = DB::table('assessment_question')->where('content', 'like', '{"id":' . $r_id . ',%')->selectRaw('id, item_ids')->get();
            foreach ($questions as $question) {
                $w_id = $question->id;
                $this->w_entities = DB::table('assessment_question_entity')->where('question_id', $w_id)->whereNull('deleted_at')->get()->keyBy('id')->toArray();
                $wrong_item_ids = $question->item_ids;
                $right = $this->getEntities($r_ids, $wrong_item_ids);
                if ($right != $wrong_item_ids) {
                    $update[] = ['id' => $w_id, 'item_ids' => $right];
                    $wrong_core_ids[] = $r_id;
                }
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        if (!empty($update)) {
            $this->multiUpdate($update);
        }
        if (!empty($wrong_core_ids)) {
            $this->info(implode(',', $wrong_core_ids));
        }
    }

    protected function getEntities($r_ids, $w_ids)
    {
        $wrong = [];
        foreach (explode(',', $w_ids) as $id) {
            $item = $this->w_entities[$id]->item_value;
            $json = json_decode($item, true);
            $wrong[$json['id']] = $id;
        }
        $right = [];
        foreach (explode(',', $r_ids) as $id) {
            $right[] = $wrong[$id];
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
        $query = "UPDATE assessment_question SET item_ids = (CASE id" . $when . " END) WHERE id IN (" . $ids . ")";
        \DB::select($query);
    }

}
