<!DOCTYPE html>
<html>
<head>
    @include('frame.head')
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
        <b>Jolyne</b>
    </div>
    <!-- /.login-logo -->
    <div class="login-box-body">
        <p class="login-box-msg">
            @if(session('message'))
                <b class="bg-red">{{session('message')}}</b>
            @else
                Sign in to start
            @endif
        </p>

        <form action="{{url('auth/login')}}" method="post">
            {!! csrf_field() !!}
            <div class="form-group has-feedback">
                <input type="text" name="username" class="form-control" placeholder="Username">
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" name="password" class="form-control" placeholder="Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="remember_me"> Remember me
                </label>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    <a href="{{route('register')}}" class="btn btn-info btn-block btn-flat">Register as a new member</a>
                </div>
                <div class="col-xs-4 pull-right">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Sign In</button>
                </div>
            </div>
        </form>

    </div>
    <!-- /.login-box-body -->
</div>
<!-- /.login-box -->

@include('frame.script')

</body>
</html>
