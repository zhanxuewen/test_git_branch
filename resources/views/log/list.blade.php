@extends('frame.body')
@section('title','Log List')

@section('section')
    <form action="{{URL::current()}}" method="GET" class="form-inline">
        <div class="form-group">
            <label for="user">用户</label>
            <select name="user" id="user" class="form-control">
                <option value="0">全部</option>
                @foreach($users as $_user)
                    <option value="{{$_user->id}}"
                            @if($_user->id == $user) selected @endif>{{$_user->username}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="scope">Scope</label>
            <select name="scope" id="scope" class="form-control">
                <option value="">全部</option>
                @foreach($scopes as $_scope)
                    <option value="{{$_scope->id}}"
                            @if($_scope->id == $scope) selected @endif
                            @if($_scope->is_leaf == 0) disabled @endif>{{$_scope->name}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="action">Action</label>
            <select name="action" id="action" class="form-control">
                <option value="">全部</option>
                @foreach($actions as $_action)
                    <option value="{{$_action->id}}"
                            @if($_action->id == $action) selected @endif
                            @if($_action->is_leaf == 0) disabled @endif>{{$_action->name}}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-default">Search</button>
    </form>
    <div class="col-sm-12">
        <table class="table table-bordered table-hover">
            <caption>Logs</caption>
            <tr>
                <th>用户</th>
                <th>Scope</th>
                <th>操作</th>
                <th>内容</th>
                <th>时间</th>
            </tr>
            @foreach($logs as $log)
                <tr>
                    <td>{{$users[$log->account_id]->username}}</td>
                    <td>{{$scopes[$log->scope_id]->name}}</td>
                    <td>{{$actions[$log->action_id]->name}}</td>
                    <td>{{$log->content}}</td>
                    <td>{{$log->created_at}}</td>
                </tr>
            @endforeach
        </table>
        <nav aria-label="Page navigation">{!! $logs->render() !!}</nav>
    </div>
@endsection