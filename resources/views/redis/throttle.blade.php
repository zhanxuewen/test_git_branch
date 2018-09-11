@extends('frame.body')
@section('title','Throttle')

@section('section')
    <div class="col-sm-8">
        <form class="form-inline" action="{!! url('redis_throttle') !!}" method="get">
            <div class="form-group">
                <label for="date">日期:</label>
                <input class="form-control" type="text" name="date" value="{{$date}}" id="date">
            </div>
            <button type="submit" class="btn btn-primary btn-flat">Submit</button>
        </form>
        <br>
        <div>
            <a class="btn btn-default" href="{!! url('redis_throttle')."?date={$date}&op=subDay" !!}"><< 前一天</a>
            <a class="btn btn-default" href="{!! url('redis_throttle')."?date=".\Carbon\Carbon::today()->toDateString() !!}">今天</a>
            <a class="btn btn-default" href="{!! url('redis_throttle')."?date={$date}&op=addDay" !!}">后一天 >></a>
        </div>
        <hr>
        <table class="table table-bordered table-hover">
            <caption>接口: {{count($keys)}} 用户: {{count($_tokens)}} [Total: {{ count($list)}}]</caption>
            <tr>
                <th>方法</th>
                <th>Uri</th>
                <th>标识</th>
                <th>次数</th>
                <th>昵称</th>
                <th>身份</th>
                <th>学校</th>
            </tr>
            @foreach($list as $item)
                <tr>
                    <td>{{$item['method']}}</td>
                    <td class="bg-gray">{{$item['uri']}}</td>
                    <td>{{$item['token']}}</td>
                    <td @if($item['count'] > 9) class="bg-orange" @endif>{{$item['count']}}</td>
                    @if(isset($accounts[$item['token']]))
                        {!! \App\Helper\BladeHelper::displayAccount($accounts[$item['token']]) !!}
                    @endif
                </tr>
            @endforeach
        </table>
    </div>
@endsection