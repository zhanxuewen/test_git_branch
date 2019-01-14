<?php

namespace App\Http\Controllers\Export;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SchoolController extends Controller
{
    protected $field_phone;

    protected $titles
        = [
            'sum' => '总数',
            'days' => '天数',
            'time' => '时间',
            'name' => '名称',
            'count' => '数量',
            'phone' => '手机',
            '_phone' => '手机',
            'region' => '区域',
            'm_name' => '备注名',
            'pay_fee' => '费用',
            'balance' => '余额',
            'user_type' => '身份',
            'nickname' => '昵称',
            'contract' => '合作档',
            'school_id' => '学校ID',
            '_school_id' => '学校ID',
            'c_p_sign' => '签到人数',
            't_nickname' => '教师昵称',
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

    public function export(Request $request)
    {
        $field = ["INSERT (phone, 4, 4, '****') as _phone", "phone"];
        $query = $request->get('query');
        $expire = $request->get('expire', 0);
        $compare = $request->get('compare', 'no');
        $db_change = $request->get('database', 0) == 0 ? null : 'wordpk';
        $request->filled('school_id') ? $params['school_id'] = $request->get('school_id', null) : null;
        $request->filled('vanclass_id') ? $params['vanclass_id'] = $request->get('vanclass_id', null) : null;
        $request->filled('teacher_id') ? $params['teacher_id'] = $request->get('teacher_id', null) : null;
        $request->filled('marketer_id') ? $params['marketer_id'] = $request->get('marketer_id', null) : null;
        $request->filled('start') ? $params['start'] = $request->get('start', null) : null;
        $request->filled('end') ? $params['end'] = $request->get('end', null) . ' 23:59:59' : null;
        $this->field_phone = $field[$request->get('field_phone')];
        isset($params) or die('没有参数');
        $pdo = $this->getPdo('online', $db_change);
        $rows = $pdo->query($this->buildSql($query, $params));
        $name = $query . '_' . $this->handleTableName($params, $pdo);
        return $this->exportExcel($name, $this->getRecord($rows, $expire, $compare));
    }

    protected function handleTableName($params, $pdo)
    {
        foreach ($params as $key => &$param) {
            $param = $this->transParams($pdo, $key, $param);
            $param = mb_substr($param, 0, 20);
        }
        return implode('_', $params);
    }

    protected function transParams(\PDO $pdo, $key, $value)
    {
        if ($key == 'school_id') return $pdo->query("SELECT `name` FROM school WHERE id = $value")->fetchColumn();
        return $value;
    }

    protected function handleIds($ids)
    {
        $array = array_unique(explode(',', $ids));
        return implode(',', $array);
    }

    protected function school_order($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT `order`.student_id, nickname, $this->field_phone, commodity_name, pay_fee, `order`.created_at, GROUP_CONCAT(DISTINCT vanclass.`name`) as name FROM school_member INNER JOIN `order` ON `order`.student_id = school_member.account_id INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school_member.school_id = " . $params['school_id'] . " AND school_member.account_type_id = 5 AND pay_status LIKE '%success' " . $this->getTime($params, '`order`.created_at') . " GROUP BY `order`.id";
    }

    protected function school_offline($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id as student_id, GROUP_CONCAT(DISTINCT vanclass.`name`) as name, nickname, $this->field_phone, days, pay_fee, order_offline.created_at, refunded_at FROM order_offline INNER JOIN user_account ON user_account.id = order_offline.student_id INNER JOIN `user` ON `user`.id = user_account.user_id LEFT JOIN vanclass_student ON vanclass_student.student_id = order_offline.student_id LEFT JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id WHERE order_offline.school_id = " . $params['school_id'] . " " . $this->getTime($params, '`order_offline`.created_at') . " GROUP BY order_offline.id";
    }

    protected function no_pay_student($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        return "SELECT user_account.id as student_id, nickname, $this->field_phone FROM user_account INNER JOIN `user` ON `user`.id = user_account.user_id WHERE user_account.id IN (SELECT DISTINCT school_member.account_id FROM school_member LEFT JOIN `order` ON `order`.student_id = school_member.account_id LEFT JOIN order_offline ON order_offline.student_id = school_member.account_id WHERE school_member.school_id = " . $params['school_id'] . " AND school_member.account_type_id = 5 AND `order`.id IS NULL AND order_offline.id IS NULL)";
    }

    protected function marketer_school($params)
    {
        !isset($params['marketer_id']) ? die('没有 市场专员ID') : null;
        return "SELECT nickname, $this->field_phone, school.`name` FROM school_member INNER JOIN school ON school.id = school_member.school_id INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE school.marketer_id = " . $params['marketer_id'] . " AND school_member.account_type_id = 4 AND school_member.is_active = 1 AND school.is_active = 1 ORDER BY school.id";
    }

    protected function marketer_order_sum($params)
    {
        !isset($params['marketer_id']) ? die('没有 市场专员ID') : null;
        return "SELECT id as _school_id, GROUP_CONCAT(DISTINCT `name`) as `name`, count(DISTINCT student_id) AS count, sum(pay_fee) AS sum FROM ((SELECT school.id, school.`name`, `order`.student_id, `order`.pay_fee FROM school INNER JOIN `order` ON `order`.school_id = school.id WHERE school.marketer_id = " . $params['marketer_id'] . " AND school.is_active = 1 AND `order`.pay_status LIKE '%success') UNION ALL (SELECT school.id, school.`name`, order_offline.student_id, order_offline.pay_fee FROM school INNER JOIN order_offline ON order_offline.school_id = school.id WHERE school.marketer_id = " . $params['marketer_id'] . " AND school.is_active = 1)) AS record GROUP BY id";
    }

    protected function school_student($params)
    {
        !isset($params['school_id']) ? die('没有 学校ID') : null;
        $vanclass = isset($params['vanclass_id']) ? " AND vanclass_student.vanclass_id = " . $params['vanclass_id'] : "";
        return "SELECT sua.id as student_id, sua.nickname, $this->field_phone, GROUP_CONCAT(DISTINCT tua.nickname) AS t_nickname , GROUP_CONCAT(DISTINCT vanclass.`name`) as name, GROUP_CONCAT(DISTINCT vanclass_student.mark_name) as m_name, sua.created_at FROM school_member INNER JOIN vanclass_student ON vanclass_student.student_id = school_member.account_id INNER JOIN vanclass_teacher ON vanclass_teacher.vanclass_id = vanclass_student.vanclass_id INNER JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id INNER JOIN user_account AS sua ON sua.id = school_member.account_id INNER JOIN user_account AS tua ON tua.id = vanclass_teacher.teacher_id INNER JOIN `user` ON `user`.id = sua.user_id WHERE school_member.school_id = " . $params['school_id'] . $vanclass . " AND school_member.account_type_id = 5 " . $this->getTime($params, 'user_account.created_at') . " GROUP BY student_id";
    }

    protected function teacher_student($params)
    {
        !isset($params['teacher_id']) ? die('没有 教师ID') : null;
        return "SELECT user_account.id as student_id, nickname, $this->field_phone FROM vanclass_student INNER JOIN vanclass_teacher ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id INNER JOIN user_account ON user_account.id = vanclass_student.student_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE vanclass_teacher.teacher_id = " . $params['teacher_id'] . " AND vanclass_student.is_active = 1 GROUP BY vanclass_student.student_id";
    }

    protected function word_pk_activity($params)
    {
        return "SELECT user_account.school_id, sch.`name`, count(DISTINCT arena.defender_id) AS c_p_arena, count(DISTINCT arena_record.attacker_id) AS c_p_attack, count(DISTINCT sign_in.account_id) AS c_p_sign FROM user_account INNER JOIN b_vanthink_online.school AS sch ON sch.id = user_account.school_id LEFT JOIN arena ON user_account.id = arena.defender_id " . $this->getTime($params, 'arena.created_at') . " LEFT JOIN arena_record ON user_account.id = arena_record.attacker_id " . $this->getTime($params, 'arena_record.created_at') . " LEFT JOIN user_sign_in_record AS sign_in ON user_account.id = sign_in.account_id " . $this->getTime($params, 'sign_in.sign_in_at') . " GROUP BY user_account.school_id";
    }

    protected function principal_last_login($params)
    {
        return "SELECT school_member.school_id, school.`name`, nickname, $this->field_phone, REPLACE (REPLACE (account_type_id, 6, '校长'), 7, '学校校管') as user_type, last_login_time as time FROM school_member INNER JOIN user_account ON user_account.id = school_member.account_id INNER JOIN school ON school.id = school_member.school_id INNER JOIN `user` ON `user`.id = user_account.user_id WHERE	school_member.account_type_id IN (6, 7) " . $this->getTime($params, 'last_login_time') . " ORDER BY school_member.school_id";
    }

    protected function contract_balance_fee($params)
    {
        return "SELECT school.id as _school_id, school.`name`, attr.value as region, nickname, pop.`value` as contract, school_popularize_data.`value` as balance FROM school_popularize_data INNER JOIN school ON school.id = school_popularize_data.school_id INNER JOIN user_account ON school.marketer_id = user_account.id LEFT JOIN school_popularize_data AS pop ON pop.school_id = school.id AND pop.`key` = 'contract_class' LEFT JOIN school_attribute as attr ON attr.school_id = school.id AND attr.`key` = 'region' WHERE school_popularize_data.`key` = 'balance_fee'";
    }

    protected function getTime($params, $column)
    {
        $time = isset($params['start']) ? "AND " . $column . " >= '" . $params['start'] . "' " : "";
        $time .= isset($params['end']) ? "AND " . $column . " <= '" . $params['end'] . "' " : "";
        return $time;
    }

    protected function getRecord($rows, $expire = 0, $compare = 'no')
    {
        $record = [];
        $token = $this->getManageToken();
        foreach ($rows as $i => $row) {
            if ($i == 0) $record[] = $this->getTitle($row, $expire);
            $data = [];
            foreach ($row as $key => $item) {
                if (!is_numeric($key) && !in_array($key, ['student_id', 'school_id'])) $data[] = $item;
            }
            if ($expire == 1) {
                $data[] = $exp = $this->appendExpire($row, $token);
                if ($compare !== 'no' && !Carbon::now()->$compare($exp)) continue;
            }
            $record[] = $data;
        }
        return $record;
    }

    protected function appendExpire($row, $token)
    {
        $res = $this->request_post($row['student_id'], $token);
        return $res->expired_time;
    }

    protected function getTitle($row, $expire = 0)
    {
        $data = [];
        foreach ($row as $key => $value) {
            if (!is_numeric($key) && !in_array($key, ['student_id', 'school_id'])) {
                $data[] = isset($this->titles[$key]) ? $this->titles[$key] : $key;
            }
        }
        if ($expire == 1) $data[] = '有效期';
        return $data;
    }

    protected function request_post($id, $token)
    {
        $postUrl = 'http://api.manage.wxzxzj.com/api/user/get/expiredTime?token=' . $token;
        $curlPost = 'student_id=' . $id;
        $data = $this->curlPost($postUrl, $curlPost);
        return $data;
    }

}
