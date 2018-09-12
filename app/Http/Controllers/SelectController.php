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
    
    /**
     * Search Quit Student
     */
    public function quit_student()
    {
        $student_id = Input::get('student_id', null);
        if (is_null($student_id)) return view('select.student', compact('student_id'));
        $pdo       = $this->getPdo('online');
        $quit_ids  = $this->getArray($pdo->query($this->buildSql('quit_vanclass', $student_id)));
        $exist_ids = $this->getArray($pdo->query($this->buildSql('exist_vanclass', $student_id)));
        foreach ($quit_ids as $key => $id) {
            if (in_array($id, $exist_ids)) unset($quit_ids[$key]);
        }
        $student  = $this->getArray($pdo->query($this->buildSql('find_student', $student_id)));
        $vanclass = $this->getRecord($pdo->query($this->buildSql('list_vanclass', $quit_ids)));
        return view('select.student', compact('vanclass', 'student', 'student_id'));
    }
    
    protected function list_marketer($role_id)
    {
        return "SELECT user_account.id, nickname, phone FROM system_account_role INNER JOIN user_account ON user_account.id = system_account_role.account_id INNER JOIN user ON user.id = user_account.user_id WHERE role_id = ".$role_id;
    }
    
    protected function list_labels($type_id)
    {
        return "SELECT * FROM label WHERE label_type_id = ".$type_id." AND deleted_at IS NULL ORDER BY power DESC";
    }
    
    protected function find_student($student_id)
    {
        return "SELECT nickname, phone FROM user_account INNER JOIN user ON user.id = user_account.user_id WHERE user_account.id =".$student_id;
    }
    
    protected function quit_vanclass($student_id)
    {
        return "SELECT DISTINCT vanclass_id FROM vanclass_student_homework WHERE student_id = ".$student_id." AND deleted_at IS NOT NULL";
    }
    
    protected function exist_vanclass($student_id)
    {
        return "SELECT DISTINCT vanclass_id FROM vanclass_student_homework WHERE student_id = ".$student_id." AND deleted_at IS NULL";
    }
    
    protected function list_vanclass($ids)
    {
        return "SELECT vanclass.id, vanclass.`name`, vanclass.student_count, teacher_id, nickname, user_account.school_id FROM vanclass INNER JOIN vanclass_teacher ON vanclass_teacher.vanclass_id = vanclass.id INNER JOIN user_account ON user_account.id = vanclass_teacher.teacher_id WHERE vanclass.id IN (".implode(',', $ids).")";
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
    
    protected function getArray($rows)
    {
        $record = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $item) {
                !is_numeric($key) ? $record[] = $item : null;
            }
        }
        return $record;
    }
    
}
