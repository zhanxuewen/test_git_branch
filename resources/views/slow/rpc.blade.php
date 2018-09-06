@extends('frame.body')
@section('title','Slow Rpc')

@section('section')
    <div id="scroll" style="width: 40%">
        <span>[ 数量阀值:{{$_count}} 时间阀值:{{$_sec}} ] {!! \Carbon\Carbon::now()->subDays($_day) !!} - {!! date('Y-m-d H:i:s') !!}</span>
        <ul class="option">
            @foreach([1,3,7] as $day)
                <li @if($day == $_day) class="checked" @endif>
                    <a href="{!! url('/slow_rpc').'?day='.$day.'&count='.$_count.'&sec='.$_sec !!}">{{$day}} day</a></li>
            @endforeach
        </ul>
        <ul class="list">
            <b>次数:</b>
            @foreach($res as $k=>$v)
                <li>
                    <div>
                        <span class="label" @if($v >= $_day * $_count) style="background-color: #FF3333" @endif>{{$v}}</span><span>{{$k}}</span>
                    </div>
                </li>
            @endforeach
        </ul>
        <ul class="list">
            <b>时间:</b>
            @foreach($time as $k=>$v)
                <li>
                    <div>
                        <span class="label" @if($v >= $_sec) style="background-color: #FF3333" @endif>{{$v}}s</span><span>{{$k}}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection