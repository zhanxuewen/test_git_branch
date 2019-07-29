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
            <label for="type">类型</label>
            <select name="type" id="type" class="form-control">
                <option value="all">全部</option>
                @foreach($types as $_type)
                    <option value="{{$_type}}" @if($_type == $type) selected @endif>{{$_type}}</option>
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
                <th>Section</th>
                <th>类型</th>
                <th>内容</th>
                <th>时间</th>
            </tr>
            @foreach($logs as $log)
                <tr>
                    <td>{{$log['account']['username']}}</td>
                    <td>{{$log['section']}}</td>
                    <td>{{$log['log_type']}}</td>
                    <td>{{$log['content']}}</td>
                    <td>{{$log['created_at']}}</td>
                </tr>
            @endforeach
        </table>
        <nav aria-label="Page navigation">{!! $logs->render() !!}</nav>
    </div>
@endsection