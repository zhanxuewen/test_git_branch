@extends('frame.body')
@section('title','Throttle')

@section('section')
    <div class="col-sm-8">
        @include('monitor.throttle.head')

        <table class="table table-bordered table-hover">
            <caption>接口: {{$count[0]}} 用户: {{$count[1]}} [Total: {{ count($list)}}]</caption>
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