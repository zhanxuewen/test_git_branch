<?php

namespace App\Http\Controllers\Database;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MigrationController extends Controller
{
    public function history(Request $request)
    {
        $table = $request->filled('table') ? $this->buildTable($request->get('table')) : [];
        $migrations = \DB::table('database_migrations')->selectRaw('module, table_name')
            ->where('migrate_type', 'create')->orderByRaw('module, table_name')->get()->groupBy('module')->toJson();
        return view('database.migration', compact('migrations', 'table'));
    }

    protected function buildTable($table)
    {
        $first = \DB::table('database_migrations')->where('table_name', $table)->where('migrate_type', 'create')->first();
        if (empty($first)) return [];
        $data = [
            'module' => $first->module,
            'table' => $first->table_name,
            'engine' => $first->engine,
            'migration' => $first->migration_name
        ];
        $sort = collect();
        $json = json_encode(['name' => '', 'type' => 'timestamp', 'default' => '0000-00-00 00:00:00', 'nullable' => 0,
            'change' => 0, 'after' => '-', 'extra' => '-', 'unsigned' => 0, 'comment' => '']);
        $columns = [];
        foreach (json_decode($first->columns) as $k => $column) {
            $columns[$column->name]['create'] = $column;
            $sort[$column->name] = $k;
        }
        if ($first->has_deleted == 1) {
            $deleted = json_decode($json);
            $deleted->name = 'deleted_at';
            $deleted->comment = '软删时间';
            $columns['deleted_at']['create'] = $deleted;
            $sort['deleted_at'] = $sort->max() + 1;
        }
        if ($first->timestamps == 1) {
            $created = json_decode($json);
            $created->name = 'created_at';
            $created->comment = '创建时间';
            $columns['created_at']['create'] = $created;
            $sort['created_at'] = $sort->max() + 1;
            $updated = json_decode($json);
            $updated->name = 'updated_at';
            $updated->comment = '更新时间';
            $columns['updated_at']['create'] = $updated;
            $sort['updated_at'] = $sort->max() + 1;
        }
        $index_s = [];
        if ($first->index != 'null') {
            foreach (json_decode($first->index) as $type => $index) {
                foreach ($index as $field) {
                    $index_s[$first->migration_name]['create'][] = ['field' => $field, 'type' => $type];
                }
            }
        }
        $mig_s = \DB::table('database_migrations')->where('table_name', $table)->where('migrate_type', '<>', 'create')->get();
        foreach ($mig_s as $mig) {
            foreach (json_decode($mig->columns) as $column) {
                $column->mig = $mig->migration_name;
                if ($column->after != '-') {
                    $i = $sort[$column->after];
                    foreach ($sort as $k => $v) {
                        if ($v > $i) $sort[$k] = $v + 1;
                    }
                    $sort[$column->name] = $i + 1;
                    $columns[$column->name]['create'] = $column;
                } else {
                    $columns[$column->name]['update'][] = $column;
                }
            }
            if ($mig->index != 'null') {
                foreach (json_decode($mig->index) as $type => $index) {
                    foreach ($index as $field) {
                        $index_s[$mig->migration_name]['update'][] = ['field' => $field, 'type' => $type];
                    }
                }
            }
        }
        $sort = $sort->sort();
        $data['sort'] = $sort;
        $data['columns'] = $columns;
        $data['index'] = $index_s;
        return $data;
    }
}
