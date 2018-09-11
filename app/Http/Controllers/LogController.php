<?php

namespace App\Http\Controllers;

class LogController extends Controller
{
    public function logs()
    {
        $logs = array_filter(explode("\n", $this->getFile('export_record.log')));
        foreach ($logs as &$log) {
            list($tmp, $file) = explode('=>', $log);
            list($user, $time) = explode('@', $tmp);
            $log = ['user' => trim($user), 'file' => trim($file), 'time' => trim($time)];
        }
        return view('log.list', compact('logs'));
    }
}
