@extends('frame.body')
@section('title','Welcome')

@section('section')
    <h3>Attention</h3>
    <ol>
        <li>除测试人员外，其他人请不用使用导出功能;</li>
        <li><b>测试人员只允许使用带颜色的导出选项.</b></li>
    </ol>
    <div class="col-sm-4">
        <form action="{{url('auth/edit')}}" method="post">
            {!! csrf_field() !!}
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="old_password" placeholder="Old Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="new_password" placeholder="New Password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" name="new_password_check" placeholder="Retype new password">
                <span class="glyphicon glyphicon-log-in form-control-feedback"></span>
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-primary btn-block btn-flat">Submit</button>
            </div>
        </form>
    </div>
@endsection