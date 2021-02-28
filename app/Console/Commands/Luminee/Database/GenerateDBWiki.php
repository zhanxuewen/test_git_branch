<?php

namespace App\Console\Commands\Luminee\Database;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class GenerateDBWiki extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:db:wiki {project}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $count = 0;

    protected $projects = [
        'core', 'learning', 'kids'
    ];

    protected $modules = [
        'logs' => 'log',
        'hot' => 'testbank',
        'schedules' => 'schedule'
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
        $project_id = $this->checkProject($project);
        $local_pdo = DB::getPdo();
        list($tables, $columns) = $this->getTables($project);
        list($modules, $tables) = $this->getModules($tables);
        $columns = $this->getColumns($columns);

        DB::setPdo($local_pdo);
        $modules = $this->generate($modules, 'module', $project_id);
        $this->info('Generating [tables]...');
        foreach ($modules as $module => $m_id) {
            $tables[$module] = $this->generate($tables[$module], 'table', $m_id);
        }
        $this->info('Generating [columns]...');
        $this->output->progressStart(count($tables));
        foreach ($tables as $module => $_tables) {
            foreach ($_tables as $table => $t_id) {
                $this->generateColumn($columns[$table], $t_id);
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
    }

    protected function checkProject($project)
    {
        $row = DB::table('database_groups')->where('code', $project)
            ->where('type', 'project')->whereNull('deleted_at')->first();
        if (!empty($row)) return $row->id;
        $create = ['code' => $project, 'type' => 'project', 'parent_id' => 0];
        return DB::table('database_groups')->insertGetId($create);
    }

    protected function getTables($project)
    {
        $pdo = $this->getConnPdo($project, 'dev');
        $db = $this->getConnDB($project, 'dev');
        \DB::setPdo($pdo);
        $tables = DB::table('information_schema.tables')->where('table_schema', $db)->pluck('table_name');
        $columns = DB::table('information_schema.columns')->where('table_schema', $db)
            ->selectRaw('table_name, column_name, column_type, ordinal_position')->get();
        return [$tables, $columns];
    }

    protected function getModules($tables)
    {
        $modules = $_tables = [];
        foreach ($tables as $table) {
            $module = explode('_', $table)[0];
            if (array_key_exists($module, $this->modules))
                $module = $this->modules[$module];
            if (in_array($module, $this->ignore)) continue;
            if (!in_array($module, $modules)) $modules[] = $module;
            $_tables[$module][] = $table;
        }
        return [$modules, $_tables];
    }

    protected function getColumns($columns)
    {
        $_columns = [];
        foreach ($columns as $column) {
            $_columns[$column->table_name][$column->column_name] =
                [$column->column_type, $column->ordinal_position];
        }
        return $_columns;
    }

    protected function generate($array, $type, $p_id)
    {
        $tmp = DB::table('database_groups')->where('type', $type)
            ->where('parent_id', $p_id)->whereNull('deleted_at')->get(['id', 'code']);
        $map = [];
        foreach ($tmp as $item) {
            if (!in_array($item->code, $array)) {
                $this->softDelete('database_groups', $item->id);
            } else {
                $map[$item->code] = $item->id;
            }
        }
        foreach ($array as $item) {
            if (!isset($map[$item])) {
                $create = ['code' => $item, 'type' => $type, 'parent_id' => $p_id];
                $id = DB::table('database_groups')->insertGetId($create);
                $map[$item] = $id;
            }
        }
        return $map;
    }

    protected function generateColumn($array, $g_id)
    {
        $tmp = DB::table('database_columns')->where('group_id', $g_id)
            ->whereNull('deleted_at')->get(['id', 'column', 'type', 'order']);
        $map = [];
        foreach ($tmp as $item) {
            if (!isset($array[$item->column])) {
                $this->softDelete('database_columns', $item->id);
            } else {
                $map[$item->column] = [$item->id, $item->type, $item->order];
            }
        }
        foreach ($array as $column => $items) {
            if (!isset($map[$column])) {
                $create = ['column' => $column, 'group_id' => $g_id, 'type' => $items[0],
                    'order' => $items[1]];
                DB::table('database_columns')->insert($create);
            } else {
                $this->checkColumn($map[$column], $items);
            }
        }
        return $map;
    }

    protected function checkColumn($origin, $new)
    {
        list($id, $_type, $_order) = $origin;
        list($type, $order) = $new;
        if ($_type != $type || $_order != $order)
            DB::table('database_columns')->where('id', $id)->update(['type' => $type, 'order' => $order]);
    }

    protected function softDelete($table, $id)
    {
        return DB::table($table)->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

}
