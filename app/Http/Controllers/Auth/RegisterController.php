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
        $guest = $this->builder->setModel('role')->where('code', 'guest')->first();
        $user  = $this->builder->setModel('account')->create(Input::all());
        $this->builder->setModel('accountRole')->create(['account_id' => $user->id, 'role_id' => $guest->id]);
        return redirect()->route('login');
    }
    
}
