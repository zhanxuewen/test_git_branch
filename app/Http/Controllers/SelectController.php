<?php

namespace App\Http\Controllers;

class SelectController extends Controller
{
    /**
     * List Marketer
     */
    public function marketer()
    {
        $pdo     = $this->getPdo('online');
        $queries = ['list_marketer' => '市场专员'];
        $rows    = [];
        foreach ($queries as $query => $label) {
            $rows[$label] = $this->getRecord($pdo->query($this->buildSql($query, 2)));
        }
        return view('select.marketer', compact('rows'));
    }
    
    /**
     * List Label Tree
     */
    public function labels()
    {
        $pdo     = $this->getPdo('online');
        $_labels = $pdo->query($this->buildSql('list_labels', 1));
        $labels  = [];
        foreach ($_labels as $label) {
            $labels[$label['parent_id']][] = $label;
        }
        return view('select.label', compact('labels'));
    }
    
    protected function list_marketer($role_id)
    {
        return "SELECT nickname, user_account.id FROM system_account_role INNER JOIN user_account ON user_account.id = system_account_role.account_id WHERE role_id = ".$role_id;
    }
    
    protected function list_labels($type_id)
    {
        return "SELECT * FROM label WHERE label_type_id = ".$type_id." AND deleted_at IS NULL ORDER BY power DESC";
    }
    
    protected function getRecord($rows)
    {
        $record = [];
        foreach ($rows as $row) {
            $data = [];
            foreach ($row as $key => $item) {
                is_numeric($key) ? $data[] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }
    
}
