<?php

namespace App\Http\Controllers\Auth;

use Auth;
use Input;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    public function index()
    {
        return view('frame.homepage');
    }
    
    public function getLogin()
    {
        return view('auth.login');
    }
    
    public function getRegister()
    {
        return view('auth.register');
    }
    
    public function postLogin()
    {
        if (!Auth::attempt(['username' => Input::get('username'), 'password' => Input::get('password')])) {
            return redirect()->back()->with('message', 'Username or Password is wrong!');
        }
        return redirect()->route('homepage');
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
    
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
    
}
