@extends('frame.body')
@section('title','Throttle')

@section('section')
    <form action="{!! url('redis_throttle') !!}" method="get">
        <label for="date">日期:</label>
        <input type="text" name="date" value="{{$date}}" id="date">
        <input type="submit">
    </form>
    <div style="margin-left: 50px">
        <a href="{!! url('redis_throttle')."?date={$date}&op=subDay" !!}"><< 前一天</a>
        <a href="{!! url('redis_throttle')."?date=".\Carbon\Carbon::today()->toDateString() !!}">今天</a>
        <a href="{!! url('redis_throttle')."?date={$date}&op=addDay" !!}">后一天 >></a>
    </div>
    <hr>
    <div style="margin-left: 50px"><span>接口: {{count($keys)}} 用户: {{count($_tokens)}}</span> <span style="background-color: #75e9a4">[Total: {{ count($list)}}]</span>
    </div>

    <table border="1" style="margin-left: 100px; margin-top: 20px">
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
                <td style="background-color: #f5d281">{{$item['uri']}}</td>
                <td>{{$item['token']}}</td>
                <td @if($item['count'] > 9) style="background-color: #f5785a" @endif>{{$item['count']}}</td>
                @if(isset($accounts[$item['token']]))
                    {!! \App\Helper\BladeHelper::displayAccount($accounts[$item['token']]) !!}
                @endif
            </tr>
        @endforeach
    </table>
@endsection