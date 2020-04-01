<?php

namespace App\Console\Commands\Luminee\Database;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class GenerateColumns extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:columns {project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $local_pdo;

    protected $project_id;

    protected $count = 0;

    protected $projects = [
        'core', 'learning', 'kids'
    ];

    protected $modules = [
        'hot' => 'testbank',
        'logs' => 'log'
    ];

    protected $ignore = ['migrations', 'seeders', 'service', 'sql'];

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
        $project = $this->argument('project');
        $this->generateProject();
        $this->project_id = DB::table('database_groups')->where('code', $project)->where('type', 'project')->first()->id;
        list($tables, $columns) = $this->getTables($project);

        DB::setPdo($this->local_pdo);
        $this->generateModule($tables);
        $this->generateTable($tables);
        $this->generateColumn($columns);
    }

    protected function generateProject()
    {
        $codes = DB::table('database_groups')->where('type', 'project')->pluck('code')->toArray();
        $create = [];
        foreach ($this->projects as $project) {
            if (!in_array($project, $codes)) {
                $create[] = [
                    'code' => $project,
                    'type' => 'project',
                    'parent_id' => 0,
                    'info' => ''
                ];
            }
        }
        DB::table('database_groups')->insert($create);
        $this->line('Project Init Done. [' . count($create) . ']');
    }

    protected function getTables($project)
    {
        $pdo = $this->getConnPdo($project, 'dev');
        $db = $this->getConnDB($project, 'dev');
        \DB::setPdo($pdo);
        $tables = DB::table('information_schema.tables')->where('table_schema', $db)->pluck('table_name');
        $columns = DB::table('information_schema.columns')->where('table_schema', $db)
            ->selectRaw('table_name, group_concat(column_name) as _column')->groupBy('table_name')->get();
        return [$tables, $columns];
    }

    protected function generateModule($tables)
    {
        $modules = DB::table('database_groups')->where('type', 'module')->where('parent_id', $this->project_id)->pluck('code')->toArray();
        $create = $tmp = [];
        foreach ($tables as $table) {
            $module = explode('_', $table)[0];
            if (array_key_exists($module, $this->modules))
                $module = $this->modules[$module];
            if (in_array($module, $this->ignore)) continue;
            if (!in_array($module, $modules) && !in_array($module, $tmp)) {
                $tmp[] = $module;
                $create[] = [
                    'code' => $module,
                    'type' => 'module',
                    'parent_id' => $this->project_id,
                    'info' => ''
                ];
            }
        }
        if (!empty($create))
            DB::table('database_groups')->insert($create);
        $this->line('Module Init Done. [' . count($create) . ']');
    }

    protected function generateTable($tables)
    {
        $modules = DB::table('database_groups')->where('type', 'module')->where('parent_id', $this->project_id)->get()->keyBy('code')->toArray();
        $tmp = $create = [];
        foreach ($tables as $table) {
            $module = explode('_', $table)[0];
            if (array_key_exists($module, $this->modules))
                $module = $this->modules[$module];
            if (in_array($module, $this->ignore)) continue;
            $module_id = $modules[$module]->id;
            if (!isset($tmp[$module])) {
                $tmp[$module] = DB::table('database_groups')->where('type', 'table')->where('parent_id', $module_id)->pluck('code')->toArray();
            }
            if (!isset($tmp[$module]) || !in_array($table, $tmp[$module])) {
                $create[] = [
                    'code' => $table,
                    'type' => 'table',
                    'parent_id' => $module_id,
                    'info' => ''
                ];
            }
        }
        if (!empty($create))
            DB::table('database_groups')->insert($create);
        $this->line('Table Init Done. [' . count($create) . ']');
    }

    protected function generateColumn($columns)
    {
        $modules = DB::table('database_groups')->where('type', 'module')->where('parent_id', $this->project_id)->get()->keyBy('code')->toArray();
        $tmp = $tmp_m = [];
        foreach ($columns as $table) {
            $t_name = $table->table_name;
            $module = explode('_', $t_name)[0];
            if (array_key_exists($module, $this->modules))
                $module = $this->modules[$module];
            if (in_array($module, $this->ignore)) continue;
            $module_id = $modules[$module]->id;
            if (!isset($tmp[$module])) {
                $tmp[$module] = $this->searchColumns($module_id);
                $this->info('Now at ' . $module);
            }
            $rows = explode(',', $table->_column);
//            dd($rows);
            if (!isset($tmp_m[$module]))
                $tmp_m[$module] = DB::table('database_groups')->where('type', 'table')->where('parent_id', $module_id)->get()->keyBy('code');
            $table_id = $tmp_m[$module][$t_name]->id;
            if (!isset($tmp[$module][$t_name])) {
                $this->createColumns($rows, $table_id);
                continue;
            }
        }
        $this->line('Column Init Done. [' . $this->count . ']');
    }

    protected function searchColumns($module_id)
    {
        return DB::table('database_columns')->join('database_groups', 'database_columns.group_id', '=', 'database_groups.id')
            ->selectRaw('code, group_concat(database_columns.column) as columns')->whereNull('database_groups.deleted_at')
            ->where('database_columns.deleted_at')->where('type', 'table')->where('parent_id', $module_id)->groupBy('code')->get()->keyBy('code');
    }

    protected function createColumns($columns, $table_id)
    {
        $create = [];
        foreach ($columns as $column) {
            $create[] = [
                'group_id' => $table_id,
                'column' => $column,
                'info' => ''
            ];
        }
        DB::table('database_columns')->insert($create);
        $this->count++;
    }

}
