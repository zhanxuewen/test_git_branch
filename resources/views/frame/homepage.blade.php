@extends('frame.body')
@section('title','Welcome')

@section('section')
    @if(session('message'))
        <span class="s_error">{{session('message')}}</span>
    @endif
    @if(session('login_user'))
        <span class="s_user">Welcome <b>{{session('login_user')}}</b> !</span> <a href="{{url('logout')}}">Sign Out</a>
    @else
        <h2>Sign In Or Sign Up</h2>
        <form action="{{url('login')}}" method="post">
            {!! csrf_field() !!}
            <label for="username">Username</label>
            <input type="text" name="username" id="username">
            <input type="submit" value="Sign In">
        </form>
        <p><b>Or</b></p>
        <form action="{{url('register')}}" method="post">
            {!! csrf_field() !!}
            <label for="username">Username</label>
            <input type="text" name="username" id="username">
            <input type="submit" value="Sign Up">
        </form>
    @endif
    <h2>Attention</h2>
    <ol>
        <li>除测试人员外，其他人请不用使用导出功能;</li>
        <li><b>测试人员只允许使用带颜色的导出选项.</b></li>
        <li><i>导出功能需要先登录.</i></li>
    </ol>
@endsection