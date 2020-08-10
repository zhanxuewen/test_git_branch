<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DiffController extends Controller
{
    public function diff(Request $request)
    {
        $project = $request->get('project', 'core');
        $type = $request->get('type', 'migration');
        $types = ['migration', 'seeder'];
        $projects = $this->getConnProjects();
        if (!in_array($project, $projects)) dd('error pro');
        $rows = [];
        foreach ($this->getConnections($project) as $conn) {
            $pdo = $this->getConnPdo($project, $conn);
            if (is_null($pdo)) continue;
            $rows[$conn] = \DB::setPdo($pdo)->table($type . 's')->pluck($type)->toArray();
        }
        return view('database.diff', compact('rows', 'projects', 'project', 'types', 'type'));
    }

    public function table_correct(Request $request)
    {
        $project = $request->get('project', 'core');
        $conn_s = $this->getConnections($project);
        $conn = $request->get('conn', 'test');
        if (!in_array($conn, $conn_s)) $conn = 'test';
        $dev_columns = $this->getColumns($project, 'dev');
        $online_columns = $this->getColumns($project, $conn);
        $diff = [];
        $this->diffColumn($diff, $dev_columns, $online_columns, '+');
        $this->diffColumn($diff, $online_columns, $dev_columns, '-');
        ksort($diff);
        return view('database.table_correct', compact('project', 'conn', 'conn_s', 'diff'));
    }

    protected function diffColumn(&$diff, $stan, $check, $symbol)
    {
        foreach ($stan as $table => $columns) {
            if (!isset($check[$table])) {
                $diff[$table] = $symbol;
                continue;
            }
            foreach ($columns as $column => $type) {
                if (!isset($check[$table][$column])) {
                    $diff[$table][$column] = $symbol;
                    continue;
                }
                if ($symbol === '-') continue;
                $_diff = $this->diffColumnType($type, $check[$table][$column]);
                if (!empty($_diff)) $diff[$table][$column] = $_diff;
            }
        }
    }

    protected function diffColumnType($stan, $check)
    {
        $diff = [];
        foreach ($stan as $k => $v) {
            if ($v != $check[$k]) $diff[$k] = [$v, $check[$k]];
        }
        return $diff;
    }

    protected function getColumns($project, $conn)
    {
        \DB::setPdo($this->getConnPdo($project, $conn));
        $data = \DB::table('information_schema.columns')
            ->selectRaw('table_name, column_name, column_type, is_nullable, column_key')
            ->where('table_schema', $this->getConnDB($project, $conn))->get();
        $columns = [];
        foreach ($data as $column) {
            $columns[$column->table_name][$column->column_name] = [
                'column_type' => $column->column_type,
                'is_nullable' => $column->is_nullable,
                'column_key' => $column->column_key
            ];
        }
        return $columns;
    }

}
