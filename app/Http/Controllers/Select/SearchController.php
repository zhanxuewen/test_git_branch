<?php

namespace App\Http\Controllers\Select;

use App\Foundation\Curl;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    protected $pdo;

    /**
     * List And Search Quit Student
     * @param Request $request
     * @return mixed
     */
    public function quitStudent(Request $request)
    {
        $student_id = $request->get('student_id', null);
        if (is_null($student_id)) return view('select.student', compact('student_id'));
        $pdo = $this->getConnPdo('core', 'online');
        $quit_ids = $this->getArray($pdo->query($this->quit_vanclass($student_id)));
        $exist_ids = $this->getArray($pdo->query($this->exist_vanclass($student_id)));
        foreach ($quit_ids as $key => $id) {
            if (in_array($id, $exist_ids)) unset($quit_ids[$key]);
        }
        $student = $this->getArray($pdo->query($this->find_student($student_id)));
        $vanclass = $this->getRecord($pdo->query($this->list_vanclass($quit_ids)));
        return view('select.student', compact('vanclass', 'student', 'student_id'));
    }

    /**
     * List And Search Partner School
     * @param Request $request
     * @return mixed
     */
    public function partnerSchool(Request $request)
    {
        $this->pdo = $this->getConnPdo('core', 'online');
        $marketers = $this->list_marketers();
        $marketer_id = $request->get('marketer_id', null);
        $is_partner = $request->get('is_partner', null);
        $school_id = $request->get('school_id', null);
        $schools = is_null($school_id) ? $this->list_partner_school($marketer_id, $is_partner) : [];
        $school = !is_null($school_id) ? $this->show_school($school_id) : [];
        $params = ['marketer_id' => $marketer_id, 'is_partner' => $is_partner, 'school_id' => $school_id];
        return view('select.partner', compact('schools', 'school', 'marketers', 'marketer_id', 'school_id', 'is_partner', 'params'));
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

    protected function list_partner_school($marketer_id, $is_partner)
    {
        $schools = DB::setPdo($this->pdo)->table('school')
            ->selectRaw('school.id, school.name, nickname, school_attribute.value AS region, school_popularize_data.value AS class');
        if (!is_null($is_partner)) {
            $schools->join('school_popularize_data AS partner', function ($join) {
                $join->on('partner.school_id', '=', 'school.id')
                    ->where('partner.key', '=', 'is_partner_school');
            }, null, null, 'left');
            $is_partner == 1 ? $schools->where('partner.value', 1) : $schools->where(function ($query) {
                $query->whereNull('partner.id')
                    ->orWhere('partner.value', '=', 0);
            });
        }
        $schools->join('school_popularize_data', function ($join) {
            $join->on('school_popularize_data.school_id', '=', 'school.id')
                ->where('school_popularize_data.key', '=', 'contract_class');
        }, null, null, 'left')
            ->join('school_attribute', function ($join) {
                $join->on('school_attribute.school_id', '=', 'school.id')
                    ->where('school_attribute.key', '=', 'region');
            }, null, null, 'left')
            ->join('user_account', 'user_account.id', '=', 'school.marketer_id');
        if (isset($marketer_id)) $schools->where('school.marketer_id', $marketer_id);
        $schools->where('school.is_active', 1)->orderBy('school.id');
        return $schools->paginate($this->getPerPage());
    }

    protected function show_school($school_id)
    {
        $uri = 'http://api.manage.wxzxzj.com/api/school';
        $token = $this->getManageToken();
        $info = Curl::curlPost($uri . '/get/schoolInfo?token=' . $token, ['school_id' => $school_id]);
        $popular = Curl::curlPost($uri . '/get/popularizelInfo?token=' . $token, ['school_id' => $school_id]);
        $popular = json_decode($popular)->data->Popularizel_info;
        $teachers = DB::setPdo($this->pdo)->table('school_member')
            ->selectRaw('nickname, vanclass.name, count(DISTINCT vanclass_student.student_id) AS coo')
            ->join('vanclass_teacher', 'vanclass_teacher.teacher_id', '=', 'school_member.account_id')
            ->join('user_account', 'user_account.id', '=', 'vanclass_teacher.teacher_id')
            ->join('vanclass', 'vanclass.id', '=', 'vanclass_teacher.vanclass_id')
            ->join('vanclass_student', function ($join) {
                $join->on('vanclass_student.vanclass_id', '=', 'vanclass_teacher.vanclass_id')
                    ->where('vanclass_student.is_active', '=', 1);
            }, null, null, 'left')
            ->where('school_member.school_id', $school_id)->where('school_member.account_type_id', 4)
            ->groupBy(['user_account.id', 'vanclass.id'])->orderByRaw('user_account.id, coo DESC')->get();
        $count = DB::setPdo($this->pdo)->table('school_member')
            ->join('user_type', 'user_type.id', '=', 'school_member.account_type_id')->where('school_id', $school_id)
            ->selectRaw('count(DISTINCT account_id) AS coo, user_type.type_name')->groupBy('user_type.id')->get();
        return ['info' => json_decode($info)->data, 'popular' => $popular, 'teachers' => $teachers, 'count' => $count];
    }

    protected function list_marketers()
    {
        return DB::setPdo($this->pdo)->table('system_account_role')->selectRaw('user_account.id, nickname')
            ->join('user_account', 'user_account.id', '=', 'system_account_role.account_id')->where('role_id', 2)->get();
    }

    protected function list_vanclass($ids)
    {
        return "SELECT vanclass.id, vanclass.`name`, vanclass.student_count, teacher_id, nickname, user_account.school_id FROM vanclass INNER JOIN vanclass_teacher ON vanclass_teacher.vanclass_id = vanclass.id INNER JOIN user_account ON user_account.id = vanclass_teacher.teacher_id WHERE vanclass.id IN (" . implode(',', $ids) . ")";
    }

    protected function list_channels()
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
