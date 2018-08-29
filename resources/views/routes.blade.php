<!DOCTYPE html>
<html>
<head>
    <title>Routes</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

    <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            display: table;
            font-weight: 100;
        }

        div {
            margin-left: 100px;
            margin-top: 50px;
        }


    </style>
</head>
<body>
<div class="select_info">
    <p><a href="{{url('export')}}">Export</a></p>
    <br>
    <p><a href="{{url('select')}}">Marketer</a></p>
    <p><a href="{{url('labels')}}">Labels</a></p>
    <p><a href="{{url('migrations')}}">Migration Diff</a></p>
    <br>
    <p><a href="{{url('redis_throttle')}}">Throttle</a></p>
    <br>
    <p><a href="{{url('slow_mysql')}}">Slow Mysql</a></p>
    <p><a href="{{url('slow_rpc')}}">Slow Rpc</a></p>
    <br>
    <p><a href="{{url('newSql')}}">New Sql</a></p>
    <p><a href="{{url('sql')}}">Old Sql</a></p>
</div>
</body>
</html>