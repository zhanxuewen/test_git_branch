@extends('frame.body')
@section('title','Account List')

@section('section')
    <div class="col-sm-8">
        <table class="table table-bordered table-hover">
            <caption>Accounts</caption>
            <tr>
                <th>用户</th>
                <th>角色</th>
                <th>时间</th>
                <th>操作</th>
            </tr>
            @foreach($accounts as $account)
                <tr>
                    <td>{{$account->username}}</td>
                    <td>{!! isset($account->role[0]->label)? $account->role[0]->label : '' !!}</td>
                    <td>{{$account->created_at}}</td>
                    <td><a class="btn btn-default" href="{{url('user/editAccount/'.$account->id)}}">编辑</a>
                        <a class="btn btn-danger" href="{{url('user/resetPassword/'.$account->id)}}">重置密码</a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection