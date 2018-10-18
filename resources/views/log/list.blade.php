@extends('frame.body')
@section('title','Log List')

@section('section')
    <div class="col-sm-8">
        <table class="table table-bordered table-hover">
            <caption>Logs</caption>
            <tr>
                <th>用户</th>
                <th>内容</th>
                <th>时间</th>
            </tr>
            @foreach($logs as $log)
                <tr>
                    <td>{{$log['account']['username']}}</td>
                    <td>{{$log['content']}}</td>
                    <td>{{$log['created_at']}}</td>
                </tr>
            @endforeach
        </table>
        <nav aria-label="Page navigation">{!! $logs->render() !!}</nav>
    </div>
@endsection