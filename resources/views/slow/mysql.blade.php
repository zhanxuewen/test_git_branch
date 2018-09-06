@extends('frame.body')
@section('title','Slow Mysql')

@section('section')
    <div id="scroll">
        <span>[ 时间阀值:{{$_sec}}s] {!! \Carbon\Carbon::now()->subDays($_day) !!} - {!! date('Y-m-d H:i:s') !!}</span>
        <ul class="option">
            @foreach([1,3,7] as $day)
                <li @if($day == $_day) class="checked" @endif>
                    <a href="{!! url('/slow_mysql').'?day='.$day.'&sec='.$_sec !!}">{{$day}} day</a></li>
            @endforeach
        </ul>
        <ul class="list">
            <b>时间:</b>
            @foreach($times as $key=>$v)
                <li>
                    <div>
                        <span class="label" @if($v >= $_sec) style="background-color: #FF3333"@endif>{{$v}}s</span>
                        <span>{{$sql_s[$key]['sql']}}</span>
                        <span style="background-color: {!! strstr($sql_s[$key]['user'],'online') ?'#75c3f1':'#AFF89D' !!}">
                        {{$sql_s[$key]['user']}}</span>
                        <span> @ {{$sql_s[$key]['host']}}</span>
                        <span>[{{$sql_s[$key]['date']}}]</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection