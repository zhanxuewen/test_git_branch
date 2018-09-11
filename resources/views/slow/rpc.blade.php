@extends('frame.body')
@section('title','Slow Rpc')

@section('section')
    <div class="col-xs-6">
        @foreach([1,3,7] as $day)
            <a class="btn btn-default @if($day == $_day) btn-primary active @endif"
               href="{!! url('/slow_rpc').'?day='.$day.'&count='.$_count.'&sec='.$_sec !!}">{{$day}} day</a>
        @endforeach
        <h4>[ 数量阀值:{{$_count}} 时间阀值:{{$_sec}} ]{!! \Carbon\Carbon::now()->subDays($_day) !!} - {!! date('Y-m-d H:i:s') !!}</h4>
        <table class="table table-bordered table-hover">
            <caption>次数</caption>
            @foreach($res as $k=>$v)
                <tr>
                    <td>
                        <span class="label @if($v >= $_sec) bg-red @else bg-gray @endif">{{$v}}</span>
                        <span>{{$k}}</span>
                    </td>
                </tr>
            @endforeach
        </table>
        <table class="table table-bordered table-hover">
            <caption>时间</caption>
            @foreach($time as $k=>$v)
                <tr>
                    <td>
                        <span class="label @if($v >= $_sec) bg-red @else bg-gray @endif">{{$v}}s</span>
                        <span>{{$k}}</span>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection