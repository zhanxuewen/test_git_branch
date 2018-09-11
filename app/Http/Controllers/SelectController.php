<?php

namespace App\Http\Controllers;

use Input;

class SelectController extends Controller
{
    /**
     * List Marketer
     */
    public function marketer()
    {
        $pdo       = $this->getPdo('online');
        $marketers = $this->getRecord($pdo->query($this->buildSql('list_marketer', 2)));
        return view('select.marketer', compact('marketers'));
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
    
    public function quit_student()
    {
        $student_id = Input::get('student_id', null);
    }
    
    protected function list_marketer($role_id)
    {
        return "SELECT user_account.id, nickname, phone FROM system_account_role INNER JOIN user_account ON user_account.id = system_account_role.account_id INNER JOIN user ON user.id = user_account.user_id WHERE role_id = ".$role_id;
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
                !is_numeric($key) ? $data[$key] = $item : null;
            }
            $record[] = $data;
        }
        return $record;
    }
    
}
