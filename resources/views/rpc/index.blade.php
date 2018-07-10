<!DOCTYPE html>
<html>
<head>
    <title>Rpc Slow</title>

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
            display: table;
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
<div style="width: 40%">
    <ul class="option">
        @foreach([1,3] as $day)
            <li @if($day == $_day) style="background-color: #ef93eb" @endif><a href="{!! url('/rpc_slow').'?day='.$day.'&count='.$_count.'&sec='.$_sec !!}">{{$day}} day</a></li>
        @endforeach
    </ul>
    <span style="margin-left: 20px">[ 数量阀值:{{$_count}} 时间阀值:{{$_sec}} ]
        {!! \Carbon\Carbon::now()->subDays($_day) !!} - {!! \Carbon\Carbon::now() !!}</span>
    <hr>
    <ul>
        <b>次数:</b>
        @foreach($res as $k=>$v)
            <li>
                <div>
                    <span class="label"
                          style="background-color: {!! $v >= $_day * $_count ?'#FF3333':'#FFBBBB' !!}">{{$v}}</span><span>{{$k}}</span>
                </div>
            </li>
        @endforeach
    </ul>
    <hr>
    <ul>
        <b>时间:</b>
        @foreach($time as $k=>$v)
            <li>
                <div>
                    <span class="label"
                          style="background-color: {!! $v >= $_sec ?'#FF3333':'#FFBBBB' !!}">{{$v}}s</span><span>{{$k}}</span>
                </div>
            </li>
        @endforeach
    </ul>
</div>
</body>
</html>