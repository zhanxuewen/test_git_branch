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
        $pdo = $this->getPdo('online');
        $quit_ids = $this->getArray($pdo->query($this->buildSql('quit_vanclass', $student_id)));
        $exist_ids = $this->getArray($pdo->query($this->buildSql('exist_vanclass', $student_id)));
        foreach ($quit_ids as $key => $id) {
            if (in_array($id, $exist_ids)) unset($quit_ids[$key]);
        }
        $student = $this->getArray($pdo->query($this->buildSql('find_student', $student_id)));
        $vanclass = $this->getRecord($pdo->query($this->buildSql('list_vanclass', $quit_ids)));
        return view('select.student', compact('vanclass', 'student', 'student_id'));
    }

    public function yellow_account(Request $request)
    {
        $pdo = $this->getPdo('online');
        $accounts = $this->getRecord($pdo->query($this->buildSql('search_yellow_account', $request)));
        $channels = $this->getRecord($pdo->query($this->buildSql('list_channels', $request)));
        $compact = array_merge_recursive(compact('accounts','channels'),$request->all());
        return view('select.yellow', $compact);
    }

    protected function find_student($student_id)
    {
        return "SELECT nickname, phone FROM user_account INNER JOIN user ON user.id = user_account.user_id WHERE user_account.id =" . $student_id;
    }

    protected function quit_vanclass($student_id)
    {
        return "SELECT DISTINCT vanclass_id FROM vanclass_student_homework WHERE student_id = " . $student_id . " AND deleted_at IS NOT NULL";
    }

    protected function exist_vanclass($student_id)
    {
        return "SELECT DISTINCT vanclass_id FROM vanclass_student_homework WHERE student_id = " . $student_id . " AND deleted_at IS NULL";
    }

    protected function list_vanclass($ids)
    {
        return "SELECT vanclass.id, vanclass.`name`, vanclass.student_count, teacher_id, nickname, user_account.school_id FROM vanclass INNER JOIN vanclass_teacher ON vanclass_teacher.vanclass_id = vanclass.id INNER JOIN user_account ON user_account.id = vanclass_teacher.teacher_id WHERE vanclass.id IN (" . implode(',', $ids) . ")";
    }

    protected function search_yellow_account(Request $request)
    {
        $account = $request->filled('value') ? $request->get('field') . ' = ' . $request->get('value') : '';
        $channel = $request->get('channel_id') == 0 ? '' : 'system_channel.id = ' . $request->get('channel_id');
        $where = ($account == '' AND $channel == '') ? '' : ' WHERE ' . $this->implodeWhere([$account, $channel]);
        return "SELECT INSERT (phone, 4, 4, '****') as phone, user_account.id, nickname, user_type_id, `name`, user_paid_channel.created_at FROM user_paid_channel INNER JOIN system_channel ON system_channel.id = user_paid_channel.channel_id INNER JOIN user_account ON user_account.id = user_paid_channel.account_id INNER JOIN `user` ON `user`.id = user_account.user_id $where LIMIT 50";
    }

    protected function list_channels(Request $request)
    {
        return "SELECT * FROM system_channel";
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

    protected function implodeWhere($fields, $and = 'AND')
    {
        $out = '';
        foreach ($fields as $k => $field) {
            if ($field != '') $out .= ($out == '' ? '' : " $and ") . $field;
        }
        return $out;
    }

}
