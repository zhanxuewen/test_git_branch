<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function getTableList(Request $request)
    {
        $conn = $request->get('conn', 'dev');
        $pdo = $this->getPdo($conn);
        $sql = $this->list_tables($this->getDbName($conn));
        $tables = $this->getRecord($pdo->query($sql));
        return view('database.table_list', compact('tables'));
    }

    public function getTableInfo(Request $request, $table_name)
    {
        $conn = $request->get('conn', 'dev');
        $pdo = $this->getPdo($conn);
        $params = ['database' => $this->getDbName($conn), 'table_name' => $table_name];
        $table = $this->getRecord($pdo->query($this->table_info($params)))[0];
        $columns = $this->getRecord($pdo->query($this->list_columns($params)));
        $index_s = $this->getRecord($pdo->query($this->list_index($params)));
        return view('database.table_info', compact('table', 'columns', 'index_s'));
    }

    protected function list_tables($database)
    {
        return "SELECT GROUP_CONCAT(column_name) as columns, table_name FROM information_schema.columns WHERE table_schema='$database' GROUP BY table_name ORDER BY table_name";
    }

    protected function table_info($params)
    {
        return "SELECT table_name, engine, table_rows, auto_increment FROM information_schema.tables WHERE table_schema = '" . $params['database'] . "' AND table_name = '" . $params['table_name'] . "'";
    }

    protected function list_columns($params)
    {
        return "SELECT column_name, column_default, is_nullable, data_type, column_type FROM information_schema.columns WHERE table_schema = '" . $params['database'] . "' AND table_name = '" . $params['table_name'] . "' ORDER BY ordinal_position";
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
