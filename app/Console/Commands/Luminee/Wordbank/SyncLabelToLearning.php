<?php

namespace App\Console\Commands\Luminee\Wordbank;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class SyncLabelToLearning extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:label_to:learning {conn=dev}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'sync label to learning testbank';

    protected $core_pdo;
    protected $learn_pdo;

    protected $coo;
    protected $index = 1;

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
        if (strstr($conn, '-')) {
            list($from, $to) = explode('-', $conn);
        } else {
            $from = $to = $conn;
        }
        $this->core_pdo = $this->getConnPdo('core', $from);
        $this->learn_pdo = $this->getConnPdo('learning', $to);
//        $this->handleLabel();
//        $this->handleScope();
//        $this->handleInsert('wordbank_translation_label', 'id, translation_id, wordbank_id, label_id, created_at, updated_at');
        $this->handleInsert('label_type', 'id, name, level', 'core_label_type');
    }

    protected function handleLabel()
    {
        $labels = DB::setPdo($this->core_pdo)->table('label')->whereNull('deleted_at')->selectRaw('id, name, label_type_id, parent_id, level, power')->get();
        $this->output->progressStart(count($labels));
        $chunk = array_chunk($labels->toArray(), 1000);
        DB::setPdo($this->learn_pdo);
        $learn = DB::table('core_label')->whereNull('deleted_at')->pluck('id')->toArray();
        foreach ($chunk as $_labels) {
            $data = [];
            foreach ($_labels as $label) {
                if (in_array($label->id, $learn)) {
                    $this->output->progressAdvance();
                    continue;
                }
                $data[] = [
                    'id' => $label->id,
                    'name' => $label->name,
                    'code' => '',
                    'is_active' => 1,
                    'label_type_id' => $label->label_type_id,
                    'parent_id' => $label->parent_id,
                    'level' => $label->level,
                    'power' => $label->power
                ];
                $this->output->progressAdvance();
            }
            if (!empty($data)) $this->info(count($data));
            DB::table('core_label')->insert($data);
        }
        $this->output->progressFinish();
    }

    protected function handleScope()
    {
        $scopes = DB::setPdo($this->core_pdo)->table('label_scope_map')->selectRaw('id, label_id, scope_id, display')->get();
        $this->output->progressStart(count($scopes));
        $chunk = array_chunk($scopes->toArray(), 1000);
        DB::setPdo($this->learn_pdo);
        $learn = DB::table('core_label_scope_map')->pluck('id')->toArray();
        foreach ($chunk as $_scopes) {
            $data = [];
            foreach ($_scopes as $scope) {
                if (in_array($scope->id, $learn)) {
                    $this->output->progressAdvance();
                    continue;
                }
                $data[] = [
                    'id' => $scope->id,
                    'label_id' => $scope->label_id,
                    'scope_id' => $scope->scope_id,
                    'display' => $scope->display,
                ];
                $this->output->progressAdvance();
            }
            if (!empty($data)) $this->info(count($data));
            DB::table('core_label_scope_map')->insert($data);
        }
        $this->output->progressFinish();
    }

    protected function handleInsert($table, $raw, $table2 = null, $id = null)
    {
        $id = is_null($id) ? 0 : $id;
        $rows = DB::setPdo($this->core_pdo)->table($table)->where('id', '>', $id)->selectRaw($raw)->get();
        $this->output->progressStart(count($rows));
        $chunk = array_chunk($rows->toArray(), 1000);
        DB::setPdo($this->learn_pdo);
        $learn = DB::table(is_null($table2) ? $table : $table2)->where('id', '>', $id)->pluck('id')->toArray();
        foreach ($chunk as $_rows) {
            $data = [];
            foreach ($_rows as $row) {
                if (in_array($row->id, $learn)) {
                    $this->output->progressAdvance();
                    continue;
                }
                $data[] = json_decode(json_encode($row), true);
                $this->output->progressAdvance();
            }
            if (!empty($data)) $this->info(count($data));
            DB::table(is_null($table2) ? $table : $table2)->insert($data);
        }
        $this->output->progressFinish();
    }

}
