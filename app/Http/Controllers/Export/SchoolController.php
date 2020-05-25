<?php

namespace App\Http\Controllers\Export;

use Carbon\Carbon;
use DB;
use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SchoolController extends Controller
{
    protected $phone;

    protected $name;

    protected $titles = [
        'account' => ['昵称', '手机号', '班级名称', '备注名'],
        'order' => ['卡类型', '费用', '订单日期'],
        'offline' => ['天数', '费用', '订单日期', '退款日期'],
        'offline_refund' => ['退回天数', '退回费用', '订单日期', '退款日期'],
    ];

    protected $title;

    protected $accounts = [];

    protected $teachers = [];

    protected $expires = [];

    protected $rows = [];

    protected $ids = [];

    protected $options = [];

    public function school()
    {
        return view('export.school');
    }

    public function postExport(Request $request)
    {
        $this->options['expire'] = $request->get('expire', 0) ? true : false;
        $this->options['teacher'] = $request->get('teacher', 0) ? true : false;
        $this->options['register'] = $request->get('register', 0) ? true : false;
        $request->filled('school_id') ? $school_id = $request->get('school_id') : die('没有 学校ID');
        $params['start'] = $request->get('start', null);
        $params['end'] = $request->filled('end') ? $request->get('end', null) . ' 23:59:59' : null;
        $this->phone = $request->get('field_phone') ? "phone as _phone" : "INSERT (phone, 4, 4, '****') as _phone";
        $this->name = $request->get('field_phone') ? "nickname as _nickname" : "concat(left(nickname,(CHAR_LENGTH(nickname)-1)),'*') as _nickname";
        DB::setPdo($this->getConnPdo('core', 'online'));
        $record = $this->query($request->get('query'), $school_id, $params);
        $name = $request->get('query') . '_' . $school_id;
        return $this->exportExcel($name, $record, 'export_school');
    }

    protected function query($query, $school_id, $params)
    {
        switch ($query) {
            case 'school_order':
                return $this->school_order($school_id, $this->getTime($params, '`order`.created_at'));
            case 'school_offline':
                return $this->school_offline($school_id, $this->getTime($params, '`order_offline`.created_at'));
            case 'school_offline_refund':
                return $this->school_offline_refund($school_id, $this->getTime($params, '`order_offline_refund`.created_at'));
            case 'school_student':
                return $this->school_students($school_id, $this->getTime($params, 'joined_time'));
            default:
                die('参数错误');
        }
    }

    protected function getTime($params, $column)
    {
        $time = isset($params['start']) ? "AND " . $column . " >= '" . $params['start'] . "' " : "";
        $time .= isset($params['end']) ? "AND " . $column . " <= '" . $params['end'] . "' " : "";
        return $time;
    }

    protected function school_order($school_id, $time)
    {
        $this->rows = DB::select("SELECT `order`.student_id, commodity_name, pay_fee, `order`.created_at FROM school_member INNER JOIN `order` ON `order`.student_id = school_member.account_id WHERE school_member.school_id = $school_id AND school_member.account_type_id = 5 AND pay_status LIKE '%success' $time GROUP BY `order`.id");
        $this->buildIds();
        $this->getAccount();
        $this->title = array_merge($this->titles['account'], $this->titles['order']);
        if ($this->options['expire']) {
            $this->getExpired();
        }
        if ($this->options['teacher']) {
            $this->getTeacher();
        }
        return $this->buildRecord(function ($row) {
            return [$row->commodity_name, $row->pay_fee, $row->created_at];
        });
    }

    protected function school_offline($school_id, $time)
    {
        $this->rows = DB::select("SELECT order_offline.student_id, days, pay_fee, order_offline.created_at, refunded_at FROM order_offline WHERE order_offline.school_id = $school_id $time GROUP BY order_offline.id");
        $this->buildIds();
        $this->getAccount();
        $this->title = array_merge($this->titles['account'], $this->titles['offline']);
        if ($this->options['expire']) {
            $this->getExpired();
        }
        if ($this->options['teacher']) {
            $this->getTeacher();
        }
        return $this->buildRecord(function ($row) {
            return [$row->days, $row->pay_fee, $row->created_at, $row->refunded_at];
        });
    }

    protected function school_offline_refund($school_id, $time)
    {
        $this->rows = DB::select("SELECT order_offline.student_id, refund_days, refund_fee, order_offline.created_at, refunded_at FROM order_offline INNER JOIN order_offline_refund ON order_offline.id = order_offline_refund.offline_id WHERE order_offline.school_id = $school_id $time GROUP BY order_offline.id");
        $this->buildIds();
        $this->getAccount();
        $this->title = array_merge($this->titles['account'], $this->titles['offline_refund']);
        if ($this->options['expire']) {
            $this->getExpired();
        }
        if ($this->options['teacher']) {
            $this->getTeacher();
        }
        return $this->buildRecord(function ($row) {
            return [$row->refund_days, $row->refund_fee, $row->created_at, $row->refunded_at];
        });
    }

    protected function school_students($school_id, $time)
    {
        $this->rows = DB::select("SELECT account_id as student_id FROM school_member WHERE school_id = $school_id AND account_type_id = 5 AND is_active = 1 $time");
        $this->buildIds();
        $this->getAccount();
        $this->title = $this->titles['account'];
        if ($this->options['register']) {
            $this->title[] = '注册时间';
        }
        if ($this->options['expire']) {
            $this->getExpired();
        }
        if ($this->options['teacher']) {
            $this->getTeacher();
        }
        return $this->buildRecord(function ($row) {
            return [];
        });
    }

    protected function buildIds()
    {
        foreach ($this->rows as $row) {
            $this->ids[] = $row->student_id;
        }
    }

    protected function buildRecord(Closure $closure)
    {
        $record = [$this->title];
        $now = Carbon::now();
        foreach ($this->rows as $row) {
            $account = $this->accounts[$row->student_id];
            $data = array_merge([$account->_nickname, $account->_phone, $account->vanclass_name, $account->markname], $closure($row));
            if ($this->options['register'])
                $data[] = $account->created_at;
            if ($this->options['expire']) {
                $exp = $this->expires[$row->student_id]->expired_at;
                $data[] = $exp;
                $data[] = $now->lte($exp) ? '是' : '否';
            }
            if ($this->options['teacher'])
                isset($this->teachers[$row->student_id]) ? $data[] = $this->teachers[$row->student_id]->teacher_name : null;
            $record[] = $data;
        }
        return $record;
    }

    protected function getAccount()
    {
        $sql = "SELECT user_account.id, $this->name, $this->phone, user_account.created_at, GROUP_CONCAT( DISTINCT vanclass.`name` ) AS vanclass_name, GROUP_CONCAT( DISTINCT vanclass_student.`mark_name` ) as markname FROM user_account INNER JOIN user ON user.id = user_account.user_id LEFT JOIN vanclass_student ON vanclass_student.student_id = user_account.id AND vanclass_student.is_active = 1 LEFT JOIN vanclass ON vanclass.id = vanclass_student.vanclass_id WHERE user_account.id IN (" . implode(',', $this->ids) . ") GROUP BY user_account.id";
        foreach (DB::select($sql) as $row) {
            $this->accounts[$row->id] = $row;
        }
    }

    protected function getTeacher()
    {
        $this->title[] = '老师';
        $sql = "SELECT vanclass_student.student_id, GROUP_CONCAT( DISTINCT user_account.`nickname` ) AS teacher_name FROM vanclass_student LEFT JOIN vanclass_teacher ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id LEFT JOIN user_account ON vanclass_teacher.teacher_id = user_account.id WHERE vanclass_student.student_id IN (" . implode(',', $this->ids) . ") AND vanclass_student.is_active = 1 GROUP BY vanclass_student.student_id";
        foreach (DB::select($sql) as $row) {
            $this->teachers[$row->student_id] = $row;
        }
    }

    protected function getExpired()
    {
        $this->title[] = '有效期';
        $this->title[] = '是否是提分版';
        $sql = "SELECT student_id, expired_at FROM payment_student_status WHERE student_id IN (" . implode(',', $this->ids) . ") AND paid_type = 'improve_card'";
        foreach (DB::select($sql) as $row) {
            $this->expires[$row->student_id] = $row;
        }
    }

}
