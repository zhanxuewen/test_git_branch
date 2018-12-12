@extends('frame.body')
@section('title','Abnormal')

@section('section')
    <form action="{{url('select/abnormal')}}" class="form-inline" method="GET">
        <div class="form-group">
            <label for="type">Type</label>
            <select class="form-control" name="type" id="type">
                <option value="account" @if($type == 'account') selected @endif>Account</option>
            </select>
        </div>
        <div class="form-group">
            <label for="conn">Connection</label>
            <select class="form-control" name="conn" id="conn">
                <option value="online" @if($conn == 'online') selected @endif>Online</option>
                <option value="dev" @if($conn == 'dev') selected @endif>Dev</option>
            </select>
        </div>
        <input class="btn btn-primary" type="submit" value="查询">
    </form>
    @if($type == 'account')
        @include('select.abnormal.account')
    @endif
@endsection