<?php

namespace App\Http\Controllers\Auth;

use Input;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    public function getRegister()
    {
        return view('auth.register');
    }
    
    public function postRegister()
    {
        $validator = $this->validate(Input::all());
        if ($validator !== true) {
            return redirect()->back()->with('message', array_shift($validator)[0]);
        }
        $this->builder->setModel('account')->create(Input::all());
        return redirect()->route('login');
    }
    
}
