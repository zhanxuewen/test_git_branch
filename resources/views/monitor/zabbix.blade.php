@extends('frame.body')
@section('title','Monitor Zabbix')

@section('section')
    <div class="col-sm-12">
        <div class="btn-group" role="group">
            @foreach([0.05, 0.1, 0.3, 0.6, 1.2, 1, 3, 7] as $_day)
                <a class="btn btn-default @if($_day == $day) btn-primary active @endif"
                   href="{!! URL::current().'?group='.$group.'&day='.$_day !!}">
                    @if(!strstr($_day, '.')) {{$_day}} Day @else {{$_day * 10}} Hour @endif</a>
            @endforeach
        </div>
        <div class="btn-group" role="group">
            @foreach(['mysql_cpu', 'mysql_operation', 'web_cpu', 'web_nginx_conn'] as $_group)
                <a class="btn btn-default @if($_group == $group) btn-primary active @endif"
                   href="{!! URL::current().'?day='.$day.'&group='.$_group !!}">{{ucwords(str_replace('_',' ',$_group))}}</a>
            @endforeach
        </div>
        <hr>
        @foreach($data as $item)
            <img src="{{$item}}" alt=""><br><br>
        @endforeach
    </div>
@endsection