<?php

namespace App\Console\Schedules;

use Illuminate\Mail\Message;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Maatwebsite\Excel\Classes\LaravelExcelWorksheet;

class BaseSchedule
{
    private function queryToArray($query, $key, $alias)
    {
        $tmp = [];
        foreach ($query as $data) {
            $tmp[$data->$key] = $data->$alias;
        }
        return $tmp;
    }
    
    protected function getManagers()
    {
        $list = \DB::table('user_account')->selectRaw('id, nickname')->where('user_type_id', 1)->get();
        return $this->queryToArray($list, 'id', 'nickname');
    }
    
    protected function setPrices()
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
        $list = \DB::table('school_popularize_data')->selectRaw('school_id, value')->where('key', 'contract_class')->get();
        return $this->queryToArray($list, 'school_id', 'value');
    }
    
    public function getRegions()
    {
        $list = \DB::table('school_attribute')->selectRaw('school_id, value')->where('key', 'region')->get();
        return $this->queryToArray($list, 'school_id', 'value');
    }
    
    public function getParts()
    {
        $league = \DB::table('school_league')->selectRaw("DISTINCT school_member.school_id, (CASE leader_school_id WHEN 1579 THEN 'X' WHEN 3043 THEN 'L' END) AS title")->join('school_member', 'school_league.principal_id', '=', 'school_member.account_id')->join('school', 'school.id', '=', 'school_member.school_id')->whereIn('school_league.leader_school_id', [1579])->where('school.is_active', 1)->where('school.created_at', '>', '2018-01-01')->get();
        $parts  = [];
        foreach ($league as $item) {
            if (in_array($item->school_id, [2147, 2518])) continue;
            $parts[$item->school_id] = $item->title;
        }
        return $parts;
    }
    
    protected function store($filename, $path, $data)
    {
        \Excel::create($filename, function (LaravelExcelWriter $Excel) use ($data) {
            $Excel->sheet('table', function (LaravelExcelWorksheet $sheet) use ($data) {
                $sheet->rows($data);
            });
        })->store('xls', $path);
        return $path.'/'.$filename.'.xls';
    }
    
    protected function email($to, $blade, $data, $subject, $attach)
    {
        \Mail::send($blade, $data, function (Message $message) use ($to, $subject, $attach) {
            $message->to($to)->subject($subject);
            $message->attach($attach);
        });
    }
}