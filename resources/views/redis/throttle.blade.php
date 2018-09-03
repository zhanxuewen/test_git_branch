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
    <div style="margin-left: 50px"><span>接口: {{count($keys)}} 用户: {{count($_tokens)}}</span><span style="background-color: #75e9a4">[Total: {{ count($list)}}]</span></div>

    <table border="1" style="margin-left: 100px; margin-top: 20px">
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
                <td style="background-color: #f5d281">{{$item['uri']}}</td>
                <td>{{$item['token']}}</td>
                <td>{{$item['count']}}</td>
                @if(isset($accounts[$item['token']]))
                    {!! \App\Helper\Helper::displayAccount($accounts[$item['token']]) !!}
                @endif
            </tr>
        @endforeach
    </table>
</div>
</body>
</html>