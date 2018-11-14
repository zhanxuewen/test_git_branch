<?php

namespace App\Http\Controllers\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    public function quit_student(Request $request)
    {
        $student_id = $request->get('student_id', null);
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
