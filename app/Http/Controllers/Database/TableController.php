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

}
