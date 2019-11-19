<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class MonitorApi extends BaseApi
{
    public function getTableRows(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $project = $request->get('project', 'core');
        $tables = $this->builder->setModel('tableIncrement')->selectRaw('`table`, rows')->where('project', $project)->where('created_date', $date)->get();
        $rows = [];
        foreach ($tables as $table) {
            $rows[$table['table']] = $table['rows'];
        }
        return $this->success($rows);

    }

}