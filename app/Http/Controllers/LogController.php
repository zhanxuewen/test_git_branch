<?php

namespace App\Http\Controllers;

class LogController extends Controller
{
    public function logs()
    {
        $logs = $this->builder->setModel('log')->with('account')->orderBy('id', 'desc')->paginate($this->getPerPage());
        return view('log.list', compact('logs'));
    }
}
