<?php

namespace App\Console\Commands\Luminee\Testbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class UpdateLearningEntity extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:learning:entity {conn=dev}';

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
        $conn = $this->argument('conn');
        $this->core_pdo = $this->getConnPdo('core', $conn);
        $this->learn_pdo = $this->getConnPdo('learning', $conn);

        $ids = [];
        $this->getOrigin($ids);

    }

    protected function getOrigin($ids)
    {
        $flag = 'audio_url';
        $replace = 'image_url';
        $invalid_t = [];
        $invalid_q = [];

        $core_e_s = DB::setPdo($this->core_pdo)->table('testbank_entity')->whereIn('testbank_id', $ids)->whereNull('deleted_at')->whereNull('testbank_extra_value')->get()->groupBy('testbank_id');
        $learn_t_ids = DB::setPdo($this->learn_pdo)->table('testbank')->whereIn('core_related_id', $ids)->selectRaw('id, core_related_id')->get()->toArray();
        $this->output->progressStart(count($ids));
        DB::setPdo($this->learn_pdo);
        foreach ($learn_t_ids as $learn_t_id) {
            $testbank_id = $learn_t_id->id;
            $learn_e_s = DB::table('testbank_entity')->where('testbank_id', $testbank_id)->whereNull('testbank_extra_value')->whereNull('deleted_at')->get();
            $array = [];
            foreach ($core_e_s[$learn_t_id->core_related_id] as $core_e) {
                $value = $core_e->testbank_item_value;
                $item = json_decode($value, true);
                $array[$item[$flag]] = $value;
            }
            foreach ($learn_e_s as $learn_e) {
                $value = $learn_e->testbank_item_value;
                $item = json_decode($value, true);
                $key = $item[$flag];
                if (isset($array[$key])) {
                    $new = $array[$key];
                    DB::table('testbank_entity')->where('id', $learn_e->id)->update(['testbank_item_value' => $new]);
                } else {
                    $invalid_t[] = $learn_e->id;
                }
            }


            $questions = DB::table('assessment_question')->where('content', 'like', '{"id":' . $testbank_id . ',%')->pluck('id')->toArray();
            foreach ($questions as $question_id) {
                $q_entities = DB::table('assessment_question_entity')->where('question_id', $question_id)->whereNull('deleted_at')->get();
                foreach ($q_entities as $entity) {
                    $value = $entity->item_value;
                    $item = json_decode($value, true);
                    $key = $item[$flag];
                    if (isset($array[$key])) {
                        $o_value = json_decode($array[$key], true);
                        $right = $o_value[$replace];
                        $item[$replace] = $right;
                        $new = json_encode($item);
                        DB::table('assessment_question_entity')->where('id', $entity->id)->update(['item_value' => $new]);
                    } else {
                        $invalid_q[] = $entity->id;
                    }
                }
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        dd($invalid_t, $invalid_q);
    }
}
