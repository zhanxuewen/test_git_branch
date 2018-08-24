<?php

namespace App\Http\Controllers;

use Input;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    protected $field_phone;
    
    protected $titles
        = [
            'id' => 'ID',
            '_id' => 'ID',
            'sum' => '总数',
            'name' => '名称',
            'days' => '天数',
            'count' => '数量',
            '_name' => '名称',
            'phone' => '手机',
            'labels' => '标签',
            '_phone' => '手机',
            'pay_fee' => '费用',
            'nickname' => '昵称',
            'mark_name' => '备注名',
            'student_id' => '学生ID',
            'created_at' => '创建时间',
            'vocabulary' => '单词',
            'translation' => '解释',
            'joined_time' => '加入时间',
            'vanclass_id' => '班级ID',
            'vanclass_name' => '班级名',
            'fluency_level' => '熟练度',
            'last_finish_at' => '最后完成'
        ];
    
    public function index()
    {
        return view('export.index');
    }
    
    public function export()
    {
        $field  = ["INSERT (phone, 4, 4, '****') as _phone", "phone"];
        $query  = Input::get('query');
        $expire = Input::get('expire', 0);
        Input::has('school_id') ? $params['school_id'] = Input::get('school_id', null) : null;
        Input::has('label_id') ? $params['label_id'] = Input::get('label_id', null) : null;
        Input::has('label_ids') ? $params['label_ids'] = $this->handleIds(Input::get('label_ids', null)) : null;
        Input::has('student_id') ? $params['student_id'] = Input::get('student_id', null) : null;
        Input::has('teacher_id') ? $params['teacher_id'] = Input::get('teacher_id', null) : null;
        Input::has('marketer_id') ? $params['marketer_id'] = Input::get('marketer_id', null) : null;
        Input::has('start') ? $params['start'] = Input::get('start', null) : null;
        Input::has('end') ? $params['end'] = Input::get('end', null).' 23:59:59' : null;
        $this->field_phone = $field[Input::get('field_phone')];
        isset($params) or die('没有参数');
        $pdo  = $this->getPdo();
        $rows = $pdo->query($this->buildSql($query, $params));
        $name = $query.'_'.$this->handleTableName($params);
        $this->exportExcel($name, $this->getRecord($rows, $expire));
    }
    
    protected function getPdo()
    {
        $env = include_once base_path().'/.env.array';
        $db  = $env['mysql'];
        return new \PDO("mysql:host=".$db['host'].";dbname=".$db['database'], $db['username'], $db['password']);
    }
    
    protected function buildSql($query, $params)
    {
        return $this->$query($params);
    }
    
    protected function handleTableName($params)
    {
        foreach ($params as &$param) {
            $param = substr($param, 0, 20);
        }
        return implode('_', $params);
    }
    
    protected function handleIds($ids)
    {
        $array = array_unique(explode(',', $ids));
        return implode(',', $array);
    }
    
    protected function school_order($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT nickname, $this->field_phone, pay_fee, vanclass.`name` FROM school_member INNER JOIN `order` ON `order`.student_id = school_member.account_id INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5 AND pay_status LIKE '%success' ".$this->getTime($params, '`order`.created_at')." GROUP BY `order`.id";
    }
    
    protected function school_offline($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id as student_id, vanclass.`name`, nickname, $this->field_phone, days, pay_fee FROM order_offline INNER JOIN user_account ON user_account.id = order_offline.student_id INNER JOIN `user` ON `user`.id = user_account.user_id LEFT JOIN vanclass_student ON vanclass_student.student_id = order_offline.student_id LEFT JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id WHERE order_offline.school_id = ".$params['school_id']." GROUP BY order_offline.id";
    }
    
    protected function no_pay_student($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id, nickname, $this->field_phone FROM user_account INNER JOIN `user` ON `user`.id = user_account.user_id WHERE user_account.id IN (SELECT DISTINCT school_member.account_id FROM school_member LEFT JOIN `order` ON `order`.student_id = school_member.account_id LEFT JOIN order_offline ON order_offline.student_id = school_member.account_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5 AND `order`.id IS NULL AND order_offline.id IS NULL)";
    }
    
    protected function marketer_school($params)
    {
        !isset($params['marketer_id']) ? die('没有 市场专员ID') : null;
        return "SELECT nickname, $this->field_phone, school.`name` FROM school_member INNER JOIN school ON school.id = school_member.school_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school.marketer_id = ".$params['marketer_id']." AND school_member.account_type_id = 4 AND school_member.is_active = 1 AND school.is_active = 1 ORDER BY school.id";
    }
    
    protected function marketer_order_sum($params)
    {
        !isset($params['marketer_id']) ? die('没有 市场专员ID') : null;
        return "SELECT id, `name`, count(DISTINCT student_id) AS count, sum(pay_fee) AS sum FROM ((SELECT school.id, school.`name`, `order`.student_id, `order`.pay_fee FROM school INNER JOIN `order` ON `order`.school_id = school.id WHERE school.marketer_id = ".$params['marketer_id']." AND school.is_active = 1 AND `order`.pay_status LIKE '%success') UNION (SELECT school.id, school.`name`, order_offline.student_id, order_offline.pay_fee FROM school INNER JOIN order_offline ON order_offline.school_id = school.id WHERE school.marketer_id = ".$params['marketer_id']." AND school.is_active = 1)) AS record GROUP BY	id";
    }
    
    protected function school_student($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id as student_id, nickname, $this->field_phone, GROUP_CONCAT(vanclass.`name`) as name, user_account.created_at FROM school_member INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5 GROUP BY student_id";
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
    
    protected function teacher_word_homework($params)
    {
        !isset($params['teacher_id']) ? die('没有 教师ID') : null;
        return "SELECT word_homework.name, word_homework.id, word_homework_student.vanclass_id, vanclass.name as vanclass_name, group_concat(word_homework_student.label_ids) AS labels, word_homework.created_at FROM word_homework_student INNER JOIN word_homework ON word_homework.id = word_homework_student.word_homework_id INNER JOIN vanclass ON vanclass.id = word_homework_student.vanclass_id WHERE word_homework.teacher_id = ".$params['teacher_id']." GROUP BY word_homework_student.vanclass_id, word_homework.id";
    }
    
    protected function student_vanclass_word($params)
    {
        !isset($params['student_id']) ? die('没有 学生ID') : null;
        return "SELECT vanclass.`name`, vanclass.id, mark_name, joined_time, group_concat(word_homework.`name`) AS _name, group_concat(word_homework.id) AS _id FROM vanclass_student INNER JOIN vanclass ON vanclass_student.vanclass_id = vanclass.id INNER JOIN word_homework_student ON word_homework_student.student_id = vanclass_student.student_id INNER JOIN word_homework ON word_homework.id = word_homework_student.word_homework_id WHERE vanclass_student.student_id = ".$params['student_id']." GROUP BY vanclass.id";
    }
    
    protected function get_labels($params)
    {
        !isset($params['label_ids']) ? die('没有 标签ID') : null;
        return "SELECT label.id, concat_ws(' - ', label_5.`name`, label_4.`name`, label_3.`name`, label_2.`name`, label.`name`) AS _name FROM label LEFT JOIN label AS label_2 ON label.parent_id = label_2.id LEFT JOIN label AS label_3 ON label_2.parent_id = label_3.id LEFT JOIN label AS label_4 ON label_3.parent_id = label_4.id LEFT JOIN label AS label_5 ON label_4.parent_id = label_5.id WHERE label.id IN (".$params['label_ids'].") AND label.deleted_at IS NULL";
    }
    
    protected function label_wordbank($params)
    {
        !isset($params['label_id']) ? die('没有 标签ID') : null;
        return "SELECT vocabulary, group_concat(translation separator ';') as translation FROM wordbank_translation_label INNER JOIN wordbank ON wordbank.id = wordbank_translation_label.wordbank_id INNER JOIN wordbank_translation ON wordbank.id = wordbank_translation.wordbank_id WHERE label_id = ".$params['label_id']." GROUP BY wordbank.id";
    }
    
    protected function getTime($params, $column)
    {
        $time = isset($params['start']) ? "AND ".$column." >= '".$params['start']."' " : "";
        $time .= isset($params['end']) ? "AND ".$column." <= '".$params['end']."' " : "";
        return $time;
    }
    
    protected function getRecord($rows, $expire = 0)
    {
        $record = [];
        foreach ($rows as $i => $row) {
            if ($i == 0) $record[] = $this->getTitle($row, $expire);
            $data = [];
            foreach ($row as $key => $item) {
                is_numeric($key) ? $data[] = $item : null;
            }
            if ($expire == 1) $data[] = $this->appendExpire($row);
            $record[] = $data;
        }
        return $record;
    }
    
    protected function appendExpire($row)
    {
        $res = $this->request_post($row['student_id']);
        return $res->expired_time;
    }
    
    protected function getTitle($row, $expire = 0)
    {
        $data = [];
        foreach ($row as $key => $value) {
            if (!is_numeric($key)) $data[] = $this->titles[$key];
        }
        if ($expire == 1) $data[] = '有效期';
        return $data;
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
    
    protected function request_post($id)
    {
        $token    = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjMzNDksImlzcyI6Imh0dHA6Ly9hcGkubWFuYWdlLnd4enh6ai5jb20vYXBpL2F1dGgvbG9naW4iLCJpYXQiOjE1MzQ0ODQ5ODksImV4cCI6MTUzNTY5NDU4OSwibmJmIjoxNTM0NDg0OTg5LCJqdGkiOiJzNWN1eDlFVDg1d0Y4UXU0In0.VXcZCjnuiMvqRvrDjd4va949KRibTC_3jPl8GW-l-ro';
        $postUrl  = 'http://api.manage.wxzxzj.com/api/user/get/expiredTime?token='.$token;
        $curlPost = 'student_id='.$id;
        $curl     = curl_init();  //初始化
        curl_setopt($curl, CURLOPT_URL, $postUrl);  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl, CURLOPT_HEADER, 0);  //设置头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);  //设置post的数据
        $data = curl_exec($curl);//运行curl
        curl_close($curl);
        $data = json_decode($data)->data;
        return $data;
    }
    
}
