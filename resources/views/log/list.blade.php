@extends('frame.body')
@section('title','Log List')

@section('section')
    <div class="col-sm-8">
        <table class="table table-bordered table-hover">
            <caption>Logs</caption>
            <tr>
                <th>用户</th>
                <th>文件</th>
                <th>时间</th>
            </tr>
            @foreach($logs as $log)
                <tr>
                    <td>{{$log['user']}}</td>
                    <td>{{$log['file']}}</td>
                    <td>{{$log['time']}}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection