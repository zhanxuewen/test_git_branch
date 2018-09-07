<?php

namespace App\Http\Controllers;

use Input;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    const USER_FILE = 'username.cnf';
    
    public function index()
    {
        return view('frame.homepage');
    }
    
    public function register()
    {
        $user = Input::get('username');
        if (!in_array($user, $this->getUsers())) {
            $this->appendUserFile($user);
        } else {
            Session::flash('message', 'Username Has Exist!');
        }
        return redirect()->route('homepage');
    }
    
    public function login()
    {
        $user = Input::get('username');
        if (!in_array($user, $this->getUsers())) {
            Session::flash('message', 'User Not Exist!');
        } else {
            Session::put(['login_user' => $user]);
        }
        return redirect()->route('homepage');
    }
    
    public function logout()
    {
        Session::forget('login_user');
        return redirect()->route('homepage');
    }
    
    protected function getUsers()
    {
        $users = $this->getFile(self::USER_FILE);
        return explode("\n", $users);
    }
    
    protected function appendUserFile($user)
    {
        $this->appendContent(self::USER_FILE, $user);
    }
    
}
