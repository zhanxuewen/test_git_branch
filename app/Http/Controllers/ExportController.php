<?php

namespace App\Http\Controllers;

use Input;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    protected $field_phone;
    
    public function index()
    {
        return view('export.index');
    }
    
    public function export()
    {
        $field = ["INSERT (phone, 4, 4, '****') as _phone", "phone"];
        $query = Input::get('query');
        Input::has('school_id') ? $params['school_id'] = Input::get('school_id', null) : null;
        Input::has('student_id') ? $params['student_id'] = Input::get('student_id', null) : null;
        Input::has('teacher_id') ? $params['teacher_id'] = Input::get('teacher_id', null) : null;
        Input::has('marketer_id') ? $params['marketer_id'] = Input::get('marketer_id', null) : null;
        $this->field_phone = $field[Input::get('field_phone')];
        isset($params) or die('没有参数');
        $pdo  = $this->getPdo();
        $rows = $pdo->query($this->buildSql($query, $params));
        $name = $query.'_'.implode('_', $params);
        $this->exportExcel($name, $this->getRecord($rows));
    }
    
    protected function getPdo()
    {
        $env = include_once base_path().'/.env.array';
        $db = $env['mysql'];
        return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'], $db['username'], $db['password']);
    }
    
    protected function buildSql($query, $params)
    {
        return $this->$query($params);
    }
    
    protected function school_order($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT nickname, $this->field_phone, pay_fee, vanclass.`name` FROM school_member INNER JOIN `order` ON `order`.student_id = school_member.account_id INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5 AND pay_status LIKE '%success' GROUP BY `order`.id";
    }
    
    protected function school_offline($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id, nickname, $this->field_phone, days, pay_fee FROM order_offline INNER JOIN user_account ON user_account.id = order_offline.student_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE order_offline.school_id = ".$params['school_id'];
    }
    
    protected function marketer_school($params)
    {
        !isset($params['marketer_id']) ? die('没有 市场专员ID') : null;
        return "SELECT nickname, $this->field_phone, school.`name` FROM school_member INNER JOIN school ON school.id = school_member.school_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school.marketer_id = ".$params['marketer_id']." AND school_member.account_type_id = 4 AND school_member.is_active = 1 AND school.is_active = 1 ORDER BY school.id";
    }
    
    protected function school_student($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id, nickname, $this->field_phone FROM school_member INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5";
    }
    
    protected function teacher_student($params)
    {
        !isset($params['teacher_id']) ? die('没有 教师ID') : null;
        return "SELECT user_account.id, nickname, $this->field_phone FROM vanclass_student INNER JOIN vanclass_teacher ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id INNER JOIN user_account ON user_account.id = vanclass_student.student_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE vanclass_teacher.teacher_id = ".$params['teacher_id']." AND vanclass_student.is_active = 1 GROUP BY vanclass_student.student_id";
    }
    
    protected function student_fluency($params)
    {
        !isset($params['student_id']) ? die('没有 学生ID') : null;
        return "SELECT vocabulary, fluency_level, last_finish_at FROM word_student_fluency INNER JOIN wordbank ON wordbank.id = word_student_fluency.wordbank_id WHERE student_id = ".$params['student_id']." AND word_student_fluency.fluency_level > 0 ORDER BY	last_finish_at DESC";
    }
    
    protected function fluency_record($params)
    {
        !isset($params['student_id']) ? die('没有 学生ID') : null;
        return "SELECT vocabulary, word_student_fluency_record.fluency_level, word_student_fluency_record.created_at FROM word_student_fluency INNER JOIN wordbank ON wordbank.id = word_student_fluency.wordbank_id INNER JOIN word_student_fluency_record ON word_student_fluency_record.student_fluency_id = word_student_fluency.id WHERE student_id = ".$params['student_id']." AND word_student_fluency.fluency_level > 0 ORDER BY word_student_fluency_record.created_at DESC";
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
    
    protected function exportExcel($name, $record)
    {
        Excel::create($name, function ($Excel) use ($record) {
            $Excel->sheet('table', function ($sheet) use ($record) {
                $sheet->rows($record);
            });
        })->export('xls');
    }
    
    protected function _exportExcel($name, $data)
    {
        header("Content-type:application/csv; charset=UTF-8");
        header("Content-Disposition:attachment;filename=".$name.".csv");
        $export = '';
        foreach ($data as $row) {
            $export .= implode(',', $row);
            $export .= "\r";
        }
        $export = iconv('UTF-8', "GB2312//IGNORE", $export);
        exit($export);
    }
    
}
