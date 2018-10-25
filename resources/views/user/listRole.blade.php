@extends('frame.body')
@section('title','Role List')

@section('section')
    <div class="col-sm-8">
        <a class="btn btn-default" href="{{url('user/createRole')}}">Create Role</a>
        <table class="table table-bordered table-hover">
            <caption>Roles</caption>
            <tr>
                <th>角色</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            @foreach($roles as $role)
                <tr>
                    <td>{{$role->label}}</td>
                    <td>{!! $role->is_active==0 ? 'Disable' : 'Enable' !!}</td>
                    <td><a href="{{url('user/editRole/'.$role->id)}}">编辑</a> |
                        <a href="{{url('user/editRolePower/'.$role->id)}}">查看权限</a></td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection