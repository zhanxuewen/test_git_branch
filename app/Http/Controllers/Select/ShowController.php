<?php

namespace App\Http\Controllers\Select;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    /**
     * List Marketer
     */
    public function marketer()
    {
        $pdo = $this->getPdo('online');
        $marketers = $this->getRecord($pdo->query($this->list_marketer(2)));
        return view('select.marketer', compact('marketers'));
    }

    /**
     * List Label Tree
     * @param Request $request
     * @return mixed
     */
    public function labels(Request $request)
    {
        $type_id = $request->get('type_id', 1);
        $pdo = $this->getPdo('online');
        $types = $this->getRecord($pdo->query("SELECT * FROM label_type"));
        $_labels = $pdo->query($this->list_labels($type_id));
        $labels = [];
        foreach ($_labels as $label) {
            $labels[$label['parent_id']][] = $label;
        }
        return view('select.label', compact('labels', 'types', 'type_id'));
    }

    /**
     * List Feedback
     */
    public function feedback()
    {
        $feedback_s = DB::setPdo($this->getPdo('online'))->table('aftersale_feedback')
            ->join('user_account', 'user_account.id', '=', 'aftersale_feedback.account_id')
            ->selectRaw('aftersale_feedback.*, nickname, user_type_id')
            ->orderBy('created_at', 'desc')->paginate($this->getPerPage());
        return view('select.feedback', compact('feedback_s'));
    }

    /**
     * Show Abnormal Data
     * @param Request $request
     * @return mixed
     */
    public function abnormal(Request $request)
    {
        $type = $request->get('type', 'account');
        $conn = $request->get('conn', 'online');
        $function = 'abnormal_' . $type;
        $collect = $this->$function($conn);
        return view('select.abnormal', compact('collect', 'type', 'conn'));
    }

    protected function list_marketer($role_id)
    {
        return "SELECT user_account.id, nickname, phone FROM system_account_role INNER JOIN user_account ON user_account.id = system_account_role.account_id INNER JOIN user ON user.id = user_account.user_id WHERE role_id = " . $role_id;
    }

    protected function list_labels($type_id)
    {
        return "SELECT * FROM label WHERE label_type_id = " . $type_id . " AND deleted_at IS NULL ORDER BY power DESC, id ASC";
    }

    protected function abnormal_account($conn)
    {
        $pdo = $this->getPdo($conn);
        $select = 'a.id AS a_id, a.nickname AS a_nick, a.user_id, b.id AS b_id, b.nickname AS b_nick';
        $teacher = DB::setPdo($pdo)->table(DB::raw('user_account AS a'))->join(DB::raw('user_account AS b'), 'a.user_id', '=', 'b.user_id')
            ->selectRaw($select)->where('a.user_type_id', 3)->where('b.user_type_id', 4)->get()->toArray();
        $school = DB::setPdo($pdo)->table(DB::raw('user_account AS a'))->join(DB::raw('user_account AS b'), 'a.user_id', '=', 'b.user_id')
            ->selectRaw($select)->where('a.user_type_id', 6)->where('b.user_type_id', 7)->get()->toArray();
        return ['teacher' => $teacher, 'school' => $school,
            'multi' => [
                'free_teacher' => $this->getMultiAccount($pdo, 3),
                'school_teacher' => $this->getMultiAccount($pdo, 4),
                'student' => $this->getMultiAccount($pdo, 5),
                'principal' => $this->getMultiAccount($pdo, 6),
                'manager' => $this->getMultiAccount($pdo, 7),
            ]
        ];
    }

    protected function abnormal_member($conn)
    {
        $pdo = $this->getPdo($conn);
        $members = DB::setPdo($pdo)->table('school_member')->selectRaw('account_id, school_id, group_concat(DISTINCT account_type_id) as type_id, count(*) as coo')
            ->groupBy(['account_id', 'school_id'])->orderBy('coo', 'desc')->having('coo', '>', 1)->get()->toArray();
        $student = DB::setPdo($pdo)->table('vanclass_student')->selectRaw('student_id, vanclass_id, count(*) as coo')
            ->groupBy(['student_id', 'vanclass_id'])->orderBy('coo', 'desc')->having('coo', '>', 1)->get()->toArray();
        return ['member' => $members, 'student' => $student];
    }

    protected function getMultiAccount($pdo, $type_id)
    {
        return DB::setPdo($pdo)->table('user_account')->join('user', 'user.id', '=', 'user_account.user_id')->selectRaw('count(*) AS coo, phone')
            ->where('user_type_id', $type_id)->groupBy('user_id')->havingRaw('coo > 1')->get()->toArray();
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
