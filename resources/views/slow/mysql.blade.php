@extends('frame.body')
@section('title','Slow Mysql')

@section('section')
    <div class="col-xs-12">
        @foreach([1,3,7] as $day)
            <a class="btn btn-default @if($day == $_day) btn-primary active @endif"
               href="{!! url('/slow_mysql').'?day='.$day.'&sec='.$_sec !!}">{{$day}} day</a>
        @endforeach
        <table class="table table-bordered table-hover">
            <caption>[ 时间阀值:{{$_sec}}s] {!! \Carbon\Carbon::now()->subDays($_day) !!} - {!! date('Y-m-d H:i:s') !!}</caption>
            @foreach($times as $key=>$v)
                <tr>
                    <td>
                        <span class="label @if($v >= $_sec) bg-red @else bg-gray @endif">{{$v}}s</span>
                        <span class="label @if(strstr($sql_s[$key]['user'],'online')) bg-blue @else bg-green @endif">
                        {{$sql_s[$key]['user']}}</span><span>@ {{$sql_s[$key]['host']}}[{{$sql_s[$key]['date']}}]</span><br>
                        <span>{{$sql_s[$key]['sql']}}</span>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection