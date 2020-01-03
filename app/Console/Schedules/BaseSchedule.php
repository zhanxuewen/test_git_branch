<?php

namespace App\Console\Schedules;

use App\Foundation\Excel;
use Carbon\Carbon;
use Illuminate\Mail\Message;
use App\Foundation\PdoBuilder;

class BaseSchedule
{
    use PdoBuilder, Excel;

    private function queryToArray($query, $key, $alias)
    {
        $tmp = [];
        foreach ($query as $data) {
            $tmp[$data->$key] = $data->$alias;
        }
        return $tmp;
    }

    protected function decodeName($name)
    {
        return html_entity_decode(str_replace('&#039;', '\'', $name));
    }

    protected function getManagers()
    {
        $list = \DB::table('user_account')->selectRaw('id, nickname')->where('user_type_id', 1)->get();
        return $this->queryToArray($list, 'id', 'nickname');
    }

    protected function setPrices()
    {
        return [
            1 => ['F' => 31, 'E' => '', 'D' => '', 'C' => 15, 'B' => 10, 'A' => 10],
            2 => ['F' => 92, 'E' => '', 'D' => '', 'C' => 41, 'B' => 27.5, 'A' => 27.5],
            3 => ['F' => 183, 'E' => '', 'D' => '', 'C' => 79, 'B' => 52.5, 'A' => 52.5],
            4 => ['F' => 365, 'E' => '', 'D' => '', 'C' => 150, 'B' => 99, 'A' => 99]
        ];
    }

    protected function dPrices()
    {
        $d_prices = \DB::table('school_popularize_data')->selectRaw('school_id, `key`, `value`')->where('key', 'like', '%min_price_at_D')->get();
        $data = [];
        foreach ($d_prices as $price) {
            switch ($price->key) {
                case 'month_min_price_at_D' :
                    $data[$price->school_id][1] = $price->value;
                    break;
                case 'quarter_min_price_at_D' :
                    $data[$price->school_id][2] = $price->value;
                    break;
                case 'half_min_price_at_D' :
                    $data[$price->school_id][3] = $price->value;
                    break;
                case 'year_min_price_at_D' :
                    $data[$price->school_id][4] = $price->value;
                    break;
            }
        }
        return $data;
    }

    public function getContract()
    {
        $list = \DB::table('school_popularize_data')->selectRaw('school_id, value')->where('key', 'contract_class')->get();
        return $this->queryToArray($list, 'school_id', 'value');
    }

    public function getContractDate()
    {
        $list = \DB::table('school_popularize_data')->selectRaw('school_id, updated_at')->where('key', 'is_partner_school')->where('value', 1)->get();
        return $this->queryToArray($list, 'school_id', 'updated_at');
    }

    public function getPrincipal()
    {
        $list = \DB::table('school_member')->selectRaw('school_member.school_id, nickname, phone')->join('user_account', 'user_account.id', '=', 'school_member.account_id')->join('user', 'user.id', '=', 'user_account.user_id')->where('account_type_id', 6)->get();
        $data = [];
        foreach ($list as $item) {
            $data[$item->school_id] = [$item->nickname, $item->phone];
        }
        return $data;
    }

    public function getVipCount($end)
    {
        $list = \DB::table('statistic_school_record')->selectRaw('school_id, vip_student, try_student')->where('date_type', $end)->get();
        $data = [];
        foreach ($list as $item) {
            $data[$item->school_id] = [$item->vip_student, $item->try_student];
        }
        return $data;
    }

    public function getRegions()
    {
        $list = \DB::table('school_attribute')->selectRaw('school_id, value')->where('key', 'region')->get();
        return $this->queryToArray($list, 'school_id', 'value');
    }

    public function getParts()
    {
        $league = \DB::table('school_league')->selectRaw("DISTINCT school_member.school_id, (CASE leader_school_id WHEN 1579 THEN 'X' WHEN 3043 THEN 'L' END) AS title")->join('school_member', 'school_league.principal_id', '=', 'school_member.account_id')->join('school', 'school.id', '=', 'school_member.school_id')->whereIn('school_league.leader_school_id', [1579])->where('school.is_active', 1)->where('school.created_at', '>', '2018-01-01')->get();
        $parts = [];
        foreach ($league as $item) {
            if (in_array($item->school_id, [2147, 2518])) continue;
            $parts[$item->school_id] = $item->title;
        }
        return $parts;
    }

    protected function getSubject($day)
    {
        if (is_array($day)) {
            $start = Carbon::parse($day['start']);
            $end = Carbon::parse($day['end']);
            $diff = $start->diffInDays($end);
            if ($diff > 8) {
                return ['每月', $start->format('Y-m')];
            } elseif ($diff < 8 && $diff > 2) {
                return ['每周', $start->format('Y-m-d') . '_' . $end->format('Y-m-d')];
            } else {
                return ['每日', $start->format('Y-m-d') . '_' . $end->format('Y-m-d')];
            }
        } else {
            $date = $day == '' ? Carbon::yesterday() : Carbon::parse($day);
            return ['每日', $date->format('Y-m-d')];
        }
    }

    protected function email($to, $blade, $data, $subject, $attach)
    {
        \Mail::send($blade, $data, function (Message $message) use ($to, $subject, $attach) {
            if (is_array($to)) list($to, $cc) = $to;
            $message->to($to);
            if (isset($cc)) $message->cc($cc);
            $message->subject($subject);
            $message->attach($attach);
        });
    }

    /**
     * Summary Functions
     */

    public function getPopExpire()
    {
        $list = \DB::table('school_popularize_data')->selectRaw('school_id, value')->where('key', 'improve_card_expired_at')->get();
        return $this->queryToArray($list, 'school_id', 'value');
    }

    public function getSchoolTeacher()
    {
        $list = \DB::table('school_member')->selectRaw('school_id, count(account_id) as count')->where('account_type_id', 4)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolNewTeacher($between)
    {
        $list = \DB::table('school_member')->selectRaw('school_id, count(account_id) as count')->whereBetween('joined_time', $between)->where('account_type_id', 4)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolActTeacher($between)
    {
        $list = \DB::table('statistic_teacher_activity')->join('school_member', 'school_member.account_id', '=', 'statistic_teacher_activity.teacher_id')->selectRaw('school_member.school_id, count(distinct statistic_teacher_activity.teacher_id) as count')->whereBetween('statistic_teacher_activity.created_date', $between)->where('statistic_teacher_activity.has_login', 1)->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 4)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolStudent()
    {
        $list = \DB::table('school_member')->selectRaw('school_id, count(account_id) as count')->where('account_type_id', 5)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolNewStudent($between)
    {
        $list = \DB::table('school_member')->selectRaw('school_id, count(school_member.account_id) AS count')->join('user_account_attribute', 'user_account_attribute.account_id', '=', 'school_member.account_id')->where('user_account_attribute.key', 'default_school')->whereRaw('user_account_attribute.value = school_member.school_id')->whereBetween('school_member.joined_time', $between)->where('school_member.account_type_id', 5)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolActStudent($between)
    {
        $list = \DB::table('statistic_student_activity')->join('school_member', 'school_member.account_id', '=', 'statistic_student_activity.student_id')->selectRaw('school_member.school_id, count(distinct statistic_student_activity.student_id) as count')->whereBetween('statistic_student_activity.created_date', $between)->where(function ($query) {
            $query->where('statistic_student_activity.has_login', 1)
                ->orWhere('statistic_student_activity.has_spread_record', 1);
        })->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolTrail($end)
    {
        $list = \DB::table('school_member')->join('statistic_student_expire', 'school_member.account_id', '=', 'statistic_student_expire.student_id')->selectRaw('school_member.school_id, count(distinct statistic_student_expire.student_id) as count')->where('free_end_at', '>', $end)->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolEffect($end)
    {
        $list = \DB::table('school_member')->join('statistic_student_expire', 'school_member.account_id', '=', 'statistic_student_expire.student_id')->selectRaw('school_member.school_id, count(distinct statistic_student_expire.student_id) as count')->where('expired_at', '>', $end)->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolStar($between)
    {
        $list = \DB::table('statistic_student_data')->join('school_member', 'school_member.account_id', '=', 'statistic_student_data.student_id')->selectRaw('school_member.school_id, sum(statistic_student_data.star) as count')->whereBetween('statistic_student_data.created_date', $between)->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }

    public function getSchoolScore($between)
    {
        $list = \DB::table('statistic_student_data')->join('school_member', 'school_member.account_id', '=', 'statistic_student_data.student_id')->selectRaw('school_member.school_id, sum(statistic_student_data.score) as count')->whereBetween('statistic_student_data.created_date', $between)->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'school_id', 'count');
    }
}