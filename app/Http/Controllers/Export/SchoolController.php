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
        'order' => ['卡类型', '费用', '计数', '订单日期', '退款日期'],
        'offline' => ['天数', '费用', '计数', '订单日期', '退款日期'],
//        'offline_refund' => ['退回天数', '退回费用', '订单日期', '退款日期'],
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
        DB::setPdo($this->getConnPdo('core', 'online4'));
        $record = $this->query($request->get('query'), $school_id, $params);
        $name = $request->get('query') . '_' . $school_id;
        return $this->exportExcel($name, $record, 'export_school');
    }

    protected function query($query, $school_id, $params)
    {
        switch ($query) {
            case 'school_order':
                return $this->school_order($school_id, $this->getTime($params, '`order`.created_at'), $this->getTime($params, '`order_refund`.success_at'));
            case 'school_offline':
                return $this->school_offline($school_id, $this->getTime($params, '`order_offline`.created_at'), $this->getTime($params, '`order_offline_refund`.success_at'));
//            case 'school_offline_refund':
//                return $this->school_offline_refund($school_id, );
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

    protected function school_order($school_id, $time, $time2)
    {
        $in = DB::select("SELECT student_id, commodity_name, pay_fee as _fee, 1 as coo, created_at, refunded_at as _refund FROM `order` WHERE school_id = $school_id AND finished_at is not null $time GROUP BY id");
        $ref = DB::select("SELECT `order`.student_id, commodity_name, -refund_fee as _fee, -1 as coo, `order`.created_at, order_refund.success_at as _refund FROM order_refund INNER JOIN `order` ON `order`.out_trade_no = order_refund.out_trade_no WHERE `order`.school_id = $school_id AND finished_at is not null $time2 GROUP BY `order`.id");
        $this->rows = array_merge($in, $ref);
        $this->title = array_merge($this->titles['account'], $this->titles['order']);
        $this->buildIds()->getAccount()->buildAdditions();
        return $this->buildRecord(function ($row) {
            return [$row->commodity_name, $row->_fee, $row->coo, $row->created_at, $row->_refund];
        });
    }

    protected function school_offline($school_id, $time, $time2)
    {
        $in = DB::select("SELECT student_id, days as _days, pay_fee as _fee, 1 as coo, created_at, refunded_at as _refund FROM order_offline WHERE school_id = $school_id $time GROUP BY id");
        $ref = DB::select("SELECT order_offline.student_id, refund_days as _days, -refund_fee as _fee, -1 as coo, order_offline.created_at, order_offline_refund.success_at as _refund FROM order_offline INNER JOIN order_offline_refund ON order_offline.id = order_offline_refund.offline_id WHERE order_offline.school_id = $school_id $time2 GROUP BY order_offline.id");
        $this->rows = array_merge($in, $ref);
        $this->title = array_merge($this->titles['account'], $this->titles['offline']);
        $this->buildIds()->getAccount()->buildAdditions();
        return $this->buildRecord(function ($row) {
            return [$row->_days, $row->_fee, $row->coo, $row->created_at, $row->_refund];
        });
    }

//    protected function school_offline_refund($school_id, $time)
//    {
//        $this->rows = DB::select("SELECT order_offline.student_id, refund_days, refund_fee, order_offline.created_at, refunded_at FROM order_offline INNER JOIN order_offline_refund ON order_offline.id = order_offline_refund.offline_id WHERE order_offline.school_id = $school_id $time GROUP BY order_offline.id");
//        dd($this->rows);
//        $this->buildIds();
//        $this->getAccount();
//        $this->title = array_merge($this->titles['account'], $this->titles['offline_refund']);
//        if ($this->options['expire']) {
//            $this->getExpired();
//        }
//        if ($this->options['teacher']) {
//            $this->getTeacher();
//        }
//        return $this->buildRecord(function ($row) {
//            return [$row->refund_days, $row->refund_fee, $row->created_at, $row->refunded_at];
//        });
//    }

    protected function school_students($school_id, $time)
    {
        $this->rows = DB::select("SELECT account_id as student_id FROM school_member WHERE school_id = $school_id AND account_type_id = 5 AND is_active = 1 $time");
        $this->title = $this->titles['account'];
        $this->buildIds()->getAccount()->buildAdditions();
        return $this->buildRecord(function ($row) {
            return [];
        });
    }

    protected function buildIds()
    {
        foreach ($this->rows as $row) {
            if (!in_array($row->student_id, $this->ids))
                $this->ids[] = $row->student_id;
        }
        return $this;
    }

    protected function buildAdditions()
    {
        if ($this->options['register']) {
            $this->title[] = '注册时间';
        }
        if ($this->options['expire']) {
            $this->getExpired();
        }
        if ($this->options['teacher']) {
            $this->getTeacher();
        }
        return $this;
    }

    protected function buildRecord(Closure $closure)
    {
        $record = [$this->title];
        $now = Carbon::now();
        foreach ($this->rows as $row) {
            $s_id = $row->student_id;
            $account = $this->accounts[$s_id];
            $data = array_merge([$account->_nickname, $account->_phone, $account->vanclass_name, $account->markname], $closure($row));
            if ($this->options['register'])
                $data[] = $account->created_at;
            if ($this->options['expire']) {
                $exp = $this->expires[$s_id]->expired_at;
                $data[] = $exp;
                $data[] = $now->lte($exp) ? '是' : '否';
            }
            if ($this->options['teacher'])
                $data[] = isset($this->teachers[$s_id]) ? $this->teachers[$s_id]->teacher_name : '';
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
        return $this;
    }

    protected function getTeacher()
    {
        $this->title[] = '老师';
        $sql = "SELECT vanclass_student.student_id, GROUP_CONCAT( DISTINCT user_account.`nickname` ) AS teacher_name FROM vanclass_student LEFT JOIN vanclass_teacher ON vanclass_student.vanclass_id = vanclass_teacher.vanclass_id LEFT JOIN user_account ON vanclass_teacher.teacher_id = user_account.id WHERE vanclass_student.student_id IN (" . implode(',', $this->ids) . ") AND vanclass_student.is_active = 1 GROUP BY vanclass_student.student_id";
        foreach (DB::select($sql) as $row) {
            $this->teachers[$row->student_id] = $row;
        }
        return $this;
    }

    protected function getExpired()
    {
        $this->title[] = '有效期';
        $this->title[] = '是否是提分版';
        $sql = "SELECT student_id, expired_at FROM payment_student_status WHERE student_id IN (" . implode(',', $this->ids) . ") AND paid_type = 'improve_card'";
        foreach (DB::select($sql) as $row) {
            $this->expires[$row->student_id] = $row;
        }
        return $this;
    }

}
