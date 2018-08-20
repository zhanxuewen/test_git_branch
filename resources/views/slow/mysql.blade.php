<!DOCTYPE html>
<html>
<head>
    <title>Slow MySQL</title>

    <link href="https://fonts.googleapis.com/css?family=Consolas:100" rel="stylesheet" type="text/css">

    <style>
        html, body {
            height: 100%;
            font-family: Consolas;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            font-weight: 100;
        }

        ul li {
            list-style: none;
            border: #888888 1px solid;
            margin: 1px;
        }

        ul.option li {
            display: inline-block;
            border: none;
        }

        span.label {
            margin-right: 5px;
        }

    </style>
</head>
<body>
<div>
    <ul class="option">
        @foreach([1,3,7] as $day)
            <li @if($day == $_day) style="background-color: #ef93eb" @endif><a href="{!! url('/slow_mysql').'?day='.$day.'&sec='.$_sec !!}">{{$day}}
                    day</a></li>
        @endforeach
    </ul>
    <span style="margin-left: 20px">[ 时间阀值:{{$_sec}}s]
        {!! \Carbon\Carbon::now()->subDays($_day) !!} - {!! \Carbon\Carbon::now() !!}</span>
    <hr>
    <ul style="margin: 0 20px">
        <b>时间:</b>
        @foreach($times as $key=>$v)
            <li>
                <div>
                    <span class="label" style="background-color: {!! $v >= $_sec ?'#FF3333':'#FFBBBB' !!}">{{$v}}s</span>
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
</body>
</html>