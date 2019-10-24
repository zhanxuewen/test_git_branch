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
        $this->handleLabel();
        $this->handleDelete('core_label', 'label', true);
        $this->handleScope();
        $this->handleDelete('core_label_scope_map', 'label_scope_map');
        $max_learn = DB::setPdo($this->learn_pdo)->table('wordbank_translation_label')->max('id');
        $this->handleInsert('wordbank_translation_label', 'id, translation_id, wordbank_id, label_id, created_at, updated_at', null, $max_learn);
        for ($i = 0; $i <= $max_learn; $i += 10000) {
            $this->info($i);
            $this->handleDelete('wordbank_translation_label', null, false, [$i, $i + 10000]);
        }
//        $this->handleInsert('label_type', 'id, name, level', 'core_label_type');
//        $this->handleInsert('label_scope', 'id, code, name', 'core_label_scope');
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

    protected function handleDelete($table, $table2 = null, $deleted_at = false, $between = null)
    {
        $_time = date('Y-m-d H:i:s');
        $query_core = DB::setPdo($this->core_pdo)->table(is_null($table2) ? $table : $table2);
        if ($deleted_at) $query_core->whereNull('deleted_at');
        if (!is_null($between)) $query_core->whereBetween('id', $between);
        $core = $query_core->pluck('id')->toArray();

        $query_learn = DB::setPdo($this->learn_pdo)->table($table);
        if ($deleted_at) $query_learn->whereNull('deleted_at');
        if (!is_null($between)) $query_learn->whereBetween('id', $between);
        $rows = $query_learn->pluck('id');
        $this->output->progressStart(count($rows));
        $chunk = array_chunk($rows->toArray(), 1000);
        foreach ($chunk as $_rows) {
            $data = [];
            foreach ($_rows as $row) {
                if (!in_array($row, $core)) $data[] = $row;
                $this->output->progressAdvance();
            }
            if (!empty($data)) {
                $this->info(count($data));
                if ($deleted_at) {
                    DB::table($table)->whereIn('id', $data)->update(['deleted_at' => $_time]);
                } else {
                    DB::table($table)->whereIn('id', $data)->delete();
                }
            }
        }
        $this->output->progressFinish();
    }

}
