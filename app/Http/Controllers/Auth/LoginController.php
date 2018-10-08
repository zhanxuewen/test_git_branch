<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Input;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function getLogin()
    {
        return view('auth.login');
    }
    
    public function postLogin()
    {
        if (!Auth::attempt(['username' => Input::get('username'), 'password' => Input::get('password')])) {
            return redirect()->back()->with('message', 'Username or Password is wrong!');
        }
        return redirect()->route('homepage');
    }
    
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
    
}
