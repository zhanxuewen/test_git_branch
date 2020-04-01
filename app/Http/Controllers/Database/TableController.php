<?php

namespace App\Http\Controllers\Database;

use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TableController extends Controller
{
    protected $groupTypes = [
        'default' => '默认',
        'project' => '项目',
        'module' => '模块',
        'table' => '表'
    ];

    /**
     * @param Request $request
     * @return mixed
     */
    public function getDBWiki(Request $request)
    {
        $project_id = $request->get('project_id', null);
        $module_id = $request->get('module_id', null);
        $table_id = $request->get('table_id', null);
        $projects = $this->setModel('dbGroup')->where('type', 'project')->get()->keyBy('id');
        $modules = $tables = $columns = [];
        if (!is_null($project_id))
            $modules = $this->setModel('dbGroup')->where('type', 'module')->where('parent_id', $project_id)->get()->keyBy('id');
        if (!is_null($module_id))
            $tables = $this->setModel('dbGroup')->where('type', 'table')->where('parent_id', $module_id)->get()->keyBy('id');
        if (!is_null($table_id))
            $columns = $this->setModel('column')->where('group_id', $table_id)->get();
        return view('database.DBWiki', compact('projects', 'modules', 'tables', 'columns', 'project_id', 'module_id', 'table_id'));
    }

    public function editDBWiki(Request $request)
    {
        $item_id = $request->get('item_id');
        $item_type = $request->get('item_type');
        $project_id = $request->get('project_id');
        $module_id = $request->get('module_id');
        $table_id = $request->get('table_id');
        $info = $request->get('info');
        $table = $item_type == 'column' ? 'column' : 'dbGroup';
        $this->setModel($table)->where('id', $item_id)->update(['info' => $info]);
        $url = 'database/DBWiki';
        if (!empty($project_id)) $url .= '?project_id=' . $project_id;
        if (!empty($module_id)) $url .= '&module_id=' . $module_id;
        if (!empty($table_id)) $url .= '&table_id=' . $table_id;
        return redirect($url);
    }


    public function getTableInfo(Request $request, $module_name)
    {
        $project = $request->get('project', 'core');
        $conn = 'dev';
        $pdo = $this->getConnPdo($project, $conn);
        $db = $this->getConnDB($project, $conn);
        $columns = $this->getRecord($pdo->query($this->query_TableColumns($db, $module_name)));
        list($columns, $names) = $this->getColumns($columns);
        $cols = $this->setModel('column')->with('group')->whereHas('group', function ($query) {
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
        $_groups = $this->setModel('dbGroup')->get();
        $groups = [];
        foreach ($_groups as $group) {
            $groups[$group->parent_id][] = $group;
        }
        $_columns = $this->setModel('column')->get();
        $columns = [];
        foreach ($_columns as $column) {
            $columns[$column->group_id][] = $column;
        }
        $types = $this->groupTypes;
        return view('database.column_info', compact('groups', 'columns', 'types'));
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
            $rows[$group->type][$group->code] = ['name' => $group->name, 'parent_id' => $group->parent_id];
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
        $this->setModel('column')->create($data);
        return redirect('database/get/columnInfo');
    }

    protected function createNewGroup(Request $request)
    {
        if (empty($code = $request->get('code'))) dd('Please Set Code');
        if (empty($name = $request->get('name'))) dd('Please Set Name');
        if (empty($parent_id = $request->get('parent_id'))) dd('Please Set Parent Id');
        $data = ['code' => $code, 'name' => $name, 'parent_id' => $parent_id, 'is_available' => $request->get('is_available')];
        $this->setModel('dbGroup')->create($data);
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
