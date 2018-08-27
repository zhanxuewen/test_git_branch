<!DOCTYPE html>
<html>
<head>
    <title>Redis Throttle</title>

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
            height: 99%;
            font-weight: 100;
            word-break: break-all;
        }

        ul li {
            list-style: none;
            border: #888888 1px solid;
            margin: 1px;
        }

        form {
            margin: 10px 20px;
            padding: 5px 20px;
        }

    </style>
</head>
<body>
<div>
    <form action="{!! url('redis_throttle') !!}" method="get">
        <label for="date">日期:</label>
        <input type="text" name="date" value="{{$date}}" id="date">
        <input type="submit">
    </form>
    <hr>
    <ul style="margin: 0 20px">
        <span>接口: {!! count($keys) !!}</span> <span>用户: {!! count($_tokens) !!}</span> <span style="background-color: #75e9a4">[Count: {!! count($list) !!}]</span>
        @foreach($list as $item)
            <li>
                <div>
                    <span style="background-color: #FFBBBB">{{$item['method']}}|{{$item['uri']}}</span>
                    <b>{{$item['count']}}</b>
                    <br><span>{{$item['token']}}</span>
                </div>
            </li>
        @endforeach
    </ul>
</div>
</body>
</html>