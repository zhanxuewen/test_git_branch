@extends('frame.body')
@section('title','Show Queries')

@section('section')
    <div class="col-xs-12">
        <form action="{{URL::current()}}" class="form-inline" method="GET">
            <label for="account_id">Account Id</label>
            <select name="account_id" id="account_id" class="form-control">
                <option value="0">All</option>
                @foreach($accounts as $account)
                    <option value="{{$account->account_id}}"
                            @if($account->account_id == $account_id) selected @endif>
                        {{$account->account->nickname}}</option>
                @endforeach
            </select>
            <label for="order_by">Order By</label>
            <select name="order_by" id="order_by" class="form-control">
                <option value="created_at">Created At (Desc)</option>
                <option value="time">Time (Desc)</option>
            </select>
            <button class="btn btn-default" type="submit">Show</button>
        </form>
        {{--        <div class="btn-group" role="group">--}}
        {{--            @foreach($days as $day=> $label)--}}
        {{--                <a class="btn btn-default @if($_day == $day) btn-primary active @endif"--}}
        {{--                   href="{{URL::current().'?day='.$day}}">{{$label}}</a>--}}
        {{--            @endforeach--}}
        {{--        </div>--}}
        {{--        <div class="pull-right">--}}
        {{--            <form class="form-inline" action="{{url('query/empty')}}" method="get">--}}
        {{--                <div class="form-group">--}}
        {{--                    <label for="auth">Auth</label>--}}
        {{--                    <select class="form-control" name="auth" id="auth">--}}
        {{--                        <option value="0">请选择</option>--}}
        {{--                        @foreach($auth_s as $auth)--}}
        {{--                            <option value="{{$auth}}">{{$auth}}</option>--}}
        {{--                        @endforeach--}}
        {{--                    </select>--}}
        {{--                </div>--}}
        {{--                <input class="btn btn-primary" type="submit" value="清空">--}}
        {{--            </form>--}}
        {{--        </div>--}}
        {{--       --}}
        <br><br>
        <table class="table table-bordered table-hover">
            <tr>
                <th>Account</th>
                <th>Project</th>
                <th>Conn</th>
                <th>Type</th>
                <th>Query</th>
                <th>Time</th>
                <th>Created At</th>
            </tr>
            @foreach($queries as $query)
                <tr>
                    <td>{{$accounts[$query->account_id]->account->nickname}}</td>
                    <td>{{$query->project}}</td>
                    <td>{{$query->connection}}</td>
                    <td>{{$query->type}}</td>
                    <td>{{$query->query}}</td>
                    <td>{{round($query->time, 3)}}s</td>
                    <td>{{$query->created_at}}</td>
                </tr>
            @endforeach
        </table>
    </div>

@endsection
