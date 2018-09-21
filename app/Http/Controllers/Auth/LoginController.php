<?php

namespace App\Http\Controllers\Auth;

use Input;
use App\Http\Controllers\Controller;
use Session;

class LoginController extends Controller
{
    const USER_FILE = 'username.cnf';
    
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
        $user = Input::get('username');
        if (!in_array($user, $this->getUsers())) {
            Session::flash('message', 'User Not Exist!');
        } else {
            Session::put(['login_user' => $user]);
        }
        return redirect()->route('homepage');
    }
    
    public function postRegister()
    {
        $user = Input::get('username');
        if (!in_array($user, $this->getUsers())) {
            $this->appendUserFile($user);
        } else {
            Session::flash('message', 'Username Has Exist!');
            return redirect()->back();
        }
        return redirect()->route('homepage');
    }
    
    public function logout()
    {
        Session::forget('login_user');
        return redirect()->route('login');
    }
    
    protected function getUsers()
    {
        $users = $this->getFile(self::USER_FILE);
        return array_filter(explode("\n", $users));
    }
    
    protected function appendUserFile($user)
    {
        $this->appendContent(self::USER_FILE, $user);
    }
    
}
