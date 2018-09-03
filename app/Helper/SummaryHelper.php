<?php

namespace App\Helper;

class SummaryHelper
{
    public function getManagers()
    {
        $managers  = \DB::setPdo(app('online_pdo'))->table('user_account')->selectRaw('id, nickname')->where('user_type_id', 1)->get();
        $marketers = [];
        foreach ($managers as $manager) {
            $marketers[$manager->id] = $manager->nickname;
        }
        return $marketers;
    }
    
    public function setPrices()
    {
        return [
            1 => ['F' => 31, 'C' => 15, 'B' => 10, 'A' => 10],
            2 => ['F' => 92, 'C' => 41, 'B' => 27.5, 'A' => 27.5],
            3 => ['F' => 183, 'C' => 79, 'B' => 52.5, 'A' => 52.5],
            4 => ['F' => 365, 'C' => 150, 'B' => 99, 'A' => 99]
        ];
    }
    
    public function getContract()
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_popularize_data')->selectRaw('school_id, value')->where('key', 'contract_class')->get();
        return $this->queryToArray($list, 'value');
    }
    
    public function getContractDate()
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_popularize_data')->selectRaw('school_id, updated_at')->where('key', 'is_partner_school')->where('value', 1)->get();
        return $this->queryToArray($list, 'updated_at');
    }
    
    public function getRegions()
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_attribute')->selectRaw('school_id, value')->where('key', 'region')->get();
        return $this->queryToArray($list, 'value');
    }
    
    public function getParts()
    {
        $league = \DB::setPdo(app('online_pdo'))->table('school_league')->selectRaw("DISTINCT school_member.school_id, (CASE leader_school_id WHEN 1579 THEN 'X' WHEN 3043 THEN 'L' END) AS title")->join('school_member', 'school_league.principal_id', '=', 'school_member.account_id')->join('school', 'school.id', '=', 'school_member.school_id')->whereIn('school_league.leader_school_id', [1579])->where('school.is_active', 1)->where('school.created_at', '>', '2018-01-01')->get();
        $parts  = [];
        foreach ($league as $item) {
            if (in_array($item->school_id, [2147, 2518])) continue;
            $parts[$item->school_id] = $item->title;
        }
        return $parts;
    }
    
    protected function queryToArray($query, $alias)
    {
        $tmp = [];
        foreach ($query as $data) {
            $tmp[$data->school_id] = $data->$alias;
        }
        return $tmp;
    }
    
    public function getSchoolTeacher()
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_member')->selectRaw('school_id, count(account_id) as count')->where('account_type_id', 4)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolNewTeacher($between)
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_member')->selectRaw('school_id, count(account_id) as count')->whereBetween('joined_time', $between)->where('account_type_id', 4)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolActTeacher($between)
    {
        $list = \DB::setPdo(app('online_pdo'))->table('statistic_teacher_activity')->join('school_member', 'school_member.account_id', '=', 'statistic_teacher_activity.teacher_id')->selectRaw('school_member.school_id, count(distinct statistic_teacher_activity.teacher_id) as count')->whereBetween('statistic_teacher_activity.created_date', $between)->where('statistic_teacher_activity.has_login', 1)->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 4)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolStudent()
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_member')->selectRaw('school_id, count(account_id) as count')->where('account_type_id', 5)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolNewStudent($between)
    {
        $list = \DB::setPdo(app('online_pdo'))->table('school_member')->selectRaw('school_id, count(school_member.account_id) AS count')->join('user_account_attribute', 'user_account_attribute.account_id', '=', 'school_member.account_id')->where('user_account_attribute.key', 'default_school')->whereRaw('user_account_attribute.value = school_member.school_id')->whereBetween('school_member.joined_time', $between)->where('school_member.account_type_id', 5)->groupBy('school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolActStudent($between)
    {
        $list = \DB::setPdo(app('online_pdo'))->table('statistic_student_activity')->join('school_member', 'school_member.account_id', '=', 'statistic_student_activity.student_id')->selectRaw('school_member.school_id, count(distinct statistic_student_activity.student_id) as count')->whereBetween('statistic_student_activity.created_date', $between)->where(function ($query) {
            $query->where('statistic_student_activity.has_login', 1)
                ->orWhere('statistic_student_activity.has_spread_record', 1);
        })->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolStar($between)
    {
        $list = \DB::setPdo(app('online_pdo'))->table('statistic_student_data')->join('school_member', 'school_member.account_id', '=', 'statistic_student_data.student_id')->selectRaw('school_member.school_id, sum(statistic_student_data.star) as count')->whereBetween('statistic_student_data.created_date', $between)->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'count');
    }
    
    public function getSchoolScore($between)
    {
        $list = \DB::setPdo(app('online_pdo'))->table('statistic_student_data')->join('school_member', 'school_member.account_id', '=', 'statistic_student_data.student_id')->selectRaw('school_member.school_id, sum(statistic_student_data.score) as count')->whereBetween('statistic_student_data.created_date', $between)->where('school_member.joined_time', '<=', $between['end'])->where('school_member.account_type_id', 5)->groupBy('school_member.school_id')->get();
        return $this->queryToArray($list, 'count');
    }
}