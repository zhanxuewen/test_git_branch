<!DOCTYPE html>
<html>
<head>
    @include('frame.head')
</head>
<body class="hold-transition register-page">
<div class="register-box">
    <div class="register-logo">
        <b>Jolyne</b>
    </div>
    <div class="register-box-body">
        <p class="login-box-msg">
            @if(session('message'))
                <b class="bg-red">{{session('message')}}</b>
            @else
                Input Captcha To Reset Your Password
            @endif
        </p>

        <form action="{{url('auth/forget/password')}}" method="post">
            {!! csrf_field() !!}
            <div class="form-group has-feedback">
                <input type="text" class="form-control" name="username" placeholder="Username" value="{{$username}}"
                       required="required" readonly>
                <span class="glyphicon glyphicon-user form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="text" class="form-control" name="captcha" placeholder="Captcha" required="required">
                <span class="glyphicon glyphicon-bell form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="password" placeholder="New Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-8">
                    <a href="{{route('login')}}" class="btn btn-info btn-block btn-flat">Back to Login</a>
                </div>
                <!-- /.col -->
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Reset</button>
                </div>
                <!-- /.col -->
            </div>
        </form>
    </div>
    <!-- /.form-box -->
</div>
<!-- /.register-box -->

@include('frame.script')

</body>
</html>
