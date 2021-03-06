@extends('frame.body')
@section('title','Slow Mysql')

@section('section')
    <div class="col-xs-12">
        @foreach([1,3,7] as $day)
            <a class="btn btn-default @if($day == $_day) btn-primary active @endif"
               href="{!! url('/slow_mysql').'?day='.$day.'&sec='.$_sec !!}">{{$day}} day</a>
        @endforeach
        <a class="btn btn-warning pull-right" id="toggle-hide">Show / Hide</a>
        <table class="table table-bordered table-hover">
            <caption>[ 时间阀值:{{$_sec}}s] <b>{!! \Carbon\Carbon::now()->subDays($_day) !!}
                    - {!! date('Y-m-d H:i:s') !!}</b> [<u>已更新为北京时间</u>]</caption>
            @foreach($times as $key=>$v)
                <tr>
                    <td @if(strstr($sql_s[$key]['date'],' 00:') && strstr($sql_s[$key]['user'],'manage')) class="need-hide" @endif>
                        <span class="label @if($v >= $_sec) bg-red @else bg-gray @endif">{{$v}}s</span>
                        <span class="label @if(strstr($sql_s[$key]['user'],'manage'))
                                bg-green @elseif(strstr($sql_s[$key]['host'],'10.30.176.166')) bg-orange @else bg-blue @endif">
                        {{$sql_s[$key]['user']}}</span><span> @ {{$sql_s[$key]['host']}}[{{$sql_s[$key]['date']}}]</span>
                        <br>
                        <span>{!! $sql_s[$key]['h_name'] !!} {{$sql_s[$key]['sql']}}</span>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <hr>
    <div class="col-sm-12">
        <a class="btn btn-primary" href="#">Back to Top</a>
    </div>
@endsection

@section('script')
    <script>
        $("#toggle-hide").click(function () {
            $(".need-hide").toggle();
        });
    </script>
@endsection