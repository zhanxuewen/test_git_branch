<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function getTableList(Request $request)
    {
        $project = $request->get('project', 'core');
        $conn = 'dev';
        $pdo = $this->getConnPdo($project, $conn);
        $db = $this->getConnDB($project, $conn);
        $tables = $this->getRecord($pdo->query($this->query_ListTables($db)));
        $tables = $this->groupTables($tables);
        $groups = $this->builder->setModel('dbGroup')->where('is_available', 1)->get();
        $groups = $this->getGroups($groups);
        return view('database.table_list', compact('tables', 'groups'));
    }

    public function getTableInfo(Request $request, $module_name)
    {
        $project = $request->get('project', 'core');
        $conn = 'dev';
        $pdo = $this->getConnPdo($project, $conn);
        $db = $this->getConnDB($project, $conn);
        $columns = $this->getRecord($pdo->query($this->query_TableColumns($db, $module_name)));
        list($columns, $names) = $this->getColumns($columns);
        $cols = $this->builder->setModel('column')->with('group')->whereHas('group', function ($query) {
            $query->where('is_available', 1);
        })->whereIn('column', $names)->where('is_available', 1)->get();
        $cols = $this->getColumnsInfo($cols);
        return view('database.table_info', compact('project', 'module_name', 'columns', 'cols'));
    }

    public function getColumnInfo(Request $request)
    {
        if ($request->get('method') == 'put_column') {
            return $this->createNewColumn($request);
        }
        if ($request->get('method') == 'put_group') {
            return $this->createNewGroup($request);
        }
        $_groups = $this->builder->setModel('dbGroup')->get();
        $groups = [];
        foreach ($_groups as $group) {
            $groups[$group->parent_id][] = $group;
        }
        $_columns = $this->builder->setModel('column')->get();
        $columns = [];
        foreach ($_columns as $column) {
            $columns[$column->group_id][] = $column;
        }
        return view('database.column_info', compact('groups', 'columns'));
    }

    protected function query_ListTables($database)
    {
        return "SELECT table_name FROM information_schema.columns WHERE table_schema='$database' GROUP BY table_name ORDER BY table_name";
    }

    protected function groupTables($tables)
    {
        $modules = [];
        foreach ($tables as $table) {
            $tab = $table['table_name'];
            list($module) = explode('_', $tab);
            $modules[$module][] = $tab;
        }
        return $modules;
    }

    protected function getGroups($groups)
    {
        $rows = [];
        foreach ($groups as $group) {
            $rows[$group->code] = ['name' => $group->name, 'parent_id' => $group->parent_id];
        }
        return $rows;
    }

    protected function query_TableColumns($database, $tab_prefix)
    {
        $raw = 'table_name, column_name, column_default, is_nullable, column_type';
        return "SELECT {$raw} FROM information_schema.columns WHERE table_schema = '{$database}' AND table_name LIKE '{$tab_prefix}%' ORDER BY ordinal_position";
    }

    protected function getColumns($columns)
    {
        $tables = [];
        $names = [];
        foreach ($columns as $column) {
            $tables[$column['table_name']][] = $column;
            $names[] = $column['column_name'];
        }
        return [$tables, array_unique($names)];
    }

    protected function getColumnsInfo($rows)
    {
        $columns = [];
        foreach ($rows as $row) {
            $columns[$row->column][$row->group->code] = [
                'info' => $row->info,
                'code' => $row->group->code,
                'group' => $row->group->name,
            ];
        }
        return $columns;
    }

    protected function createNewColumn(Request $request)
    {
        if (empty($_column = $request->get('column'))) dd('Please Set Column');
        if (empty($info = $request->get('info'))) dd('Please Set Info');
        if (empty($_group_id = $request->get('group_id'))) dd('Please Set Group Id');
        $data = ['group_id' => $_group_id, 'column' => $_column, 'info' => $info, 'is_available' => $request->get('is_available')];
        $this->builder->setModel('column')->create($data);
        return redirect('database/get/columnInfo');
    }

    protected function createNewGroup(Request $request)
    {
        if (empty($code = $request->get('code'))) dd('Please Set Code');
        if (empty($name = $request->get('name'))) dd('Please Set Name');
        if (empty($parent_id = $request->get('parent_id'))) dd('Please Set Parent Id');
        $data = ['code' => $code, 'name' => $name, 'parent_id' => $parent_id, 'is_available' => $request->get('is_available')];
        $this->builder->setModel('dbGroup')->create($data);
        return redirect('database/get/columnInfo');
    }

    protected function list_index($params)
    {
        return "show index from " . $params['table_name'];
    }

    protected function getRecord($rows)
    {
        $record = [];
        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($row as $key => $item) {
                !is_numeric($key) ? $data[$key] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }

}
