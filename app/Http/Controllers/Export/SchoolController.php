<?php

namespace App\Http\Controllers\Export;

use Input;
use App\Http\Controllers\Controller;

class SchoolController extends Controller
{
    protected $field_phone;
    
    protected $titles
        = [
            'sum' => '总数',
            'days' => '天数',
            'time' => '时间',
            'count' => '数量',
            'phone' => '手机',
            '_phone' => '手机',
            'pay_fee' => '费用',
            'user_type' => '身份',
            'nickname' => '昵称',
            'school_id' => '学校ID',
            'c_p_sign' => '签到人数',
            'c_p_arena' => '摆擂人数',
            'c_p_attack' => '攻擂人数',
            'student_id' => '学生ID',
            'created_at' => '创建时间',
            'refunded_at' => '退款时间',
            'vanclass_id' => '班级ID',
            'commodity_name' => '卡类型',
        ];
    
    public function school()
    {
        return view('export.school');
    }
    
    public function export()
    {
        $field     = ["INSERT (phone, 4, 4, '****') as _phone", "phone"];
        $query     = Input::get('query');
        $expire    = Input::get('expire', 0);
        $db_change = Input::get('database', 0) == 0 ? false : true;
        Input::has('school_id') ? $params['school_id'] = Input::get('school_id', null) : null;
        Input::has('teacher_id') ? $params['teacher_id'] = Input::get('teacher_id', null) : null;
        Input::has('marketer_id') ? $params['marketer_id'] = Input::get('marketer_id', null) : null;
        Input::has('start') ? $params['start'] = Input::get('start', null) : null;
        Input::has('end') ? $params['end'] = Input::get('end', null).' 23:59:59' : null;
        $this->field_phone = $field[Input::get('field_phone')];
        isset($params) or die('没有参数');
        $pdo  = $this->getPdo('online', $db_change);
        $rows = $pdo->query($this->buildSql($query, $params));
        $name = $query.'_'.$this->handleTableName($params);
        $this->exportExcel($name, $this->getRecord($rows, $expire));
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
        return "SELECT `order`.student_id, nickname, $this->field_phone, commodity_name, pay_fee, order.created_at, vanclass.`name` FROM school_member INNER JOIN `order` ON `order`.student_id = school_member.account_id INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5 AND pay_status LIKE '%success' ".$this->getTime($params, '`order`.created_at')." GROUP BY `order`.id";
    }
    
    protected function school_offline($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id as student_id, vanclass.`name`, nickname, $this->field_phone, days, pay_fee, order_offline.created_at, refunded_at FROM order_offline INNER JOIN user_account ON user_account.id = order_offline.student_id INNER JOIN `user` ON `user`.id = user_account.user_id LEFT JOIN vanclass_student ON vanclass_student.student_id = order_offline.student_id LEFT JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id WHERE order_offline.school_id = ".$params['school_id']." ".$this->getTime($params, '`order_offline`.created_at')." GROUP BY order_offline.id";
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
        return "SELECT user_account.id as student_id, nickname, $this->field_phone, GROUP_CONCAT(vanclass.`name`) as name, user_account.created_at FROM school_member INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = ".$params['school_id']." AND school_member.account_type_id = 5 ".$this->getTime($params, 'user_account.created_at')." GROUP BY student_id";
    }
    
    protected function teacher_student($params)
    {
        !isset($params['teacher_id']) ? die('没有 教师ID') : null;
        return "SELECT user_account.id, nickname, $this->field_phone FROM vanclass_student INNER JOIN vanclass_teacher ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id INNER JOIN user_account ON user_account.id = vanclass_student.student_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE vanclass_teacher.teacher_id = ".$params['teacher_id']." AND vanclass_student.is_active = 1 GROUP BY vanclass_student.student_id";
    }
    
    protected function word_pk_activity($params)
    {
        return "SELECT user_account.school_id, sch.`name`, count(DISTINCT arena.defender_id) AS c_p_arena, count(DISTINCT arena_record.attacker_id) AS c_p_attack, count(DISTINCT sign_in.account_id) AS c_p_sign FROM user_account INNER JOIN b_vanthink_online.school AS sch ON sch.id = user_account.school_id LEFT JOIN arena ON user_account.id = arena.defender_id ".$this->getTime($params, 'arena.created_at')." LEFT JOIN arena_record ON user_account.id = arena_record.attacker_id ".$this->getTime($params, 'arena_record.created_at')." LEFT JOIN user_sign_in_record AS sign_in ON user_account.id = sign_in.account_id ".$this->getTime($params, 'sign_in.sign_in_at')." GROUP BY user_account.school_id";
    }
    
    protected function principal_last_login($params)
    {
        return "SELECT school_member.school_id, school.`name`, nickname, $this->field_phone, REPLACE (REPLACE (account_type_id, 6, '校长'), 7, '学校校管') as user_type, last_login_time as time FROM school_member INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN school ON school.id = school_member.school_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE	school_member.account_type_id IN (6, 7) ".$this->getTime($params, 'last_login_time')." ORDER BY school_member.school_id";
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
            if (!is_numeric($key)) {
                $data[] = isset($this->titles[$key]) ? $this->titles[$key] : $key;
            }
        }
        if ($expire == 1) $data[] = '有效期';
        return $data;
    }
    
    protected function request_post($id)
    {
        $postUrl  = 'http://api.manage.wxzxzj.com/api/user/get/expiredTime?token='.$this->getManageToken();
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