@extends('frame.body')
@section('title','Welcome')

@section('section')
    <h3>Attention</h3>
    @if(Auth::user()->role[0]->code == 'guest')
        <blockquote>
            <p>Please contact [<b>LuminEe</b>] to change your role.</p>
        </blockquote>
    @endif
    <div class="col-sm-4">
        <p>You Can Reset Your Password Blow:</p>
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