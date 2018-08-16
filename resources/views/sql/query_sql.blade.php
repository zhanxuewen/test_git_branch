<!DOCTYPE html>
<html>
<head>
    <title>Sql Analyze Query Sql</title>

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

        li {
            list-style: none;
        }

        ul > li.checked {
            background-color: #ef93eb;
        }

        ul.param li {
            display: inline-block;
            margin-right: 10px;
        }

        ul.sql > li {
            border: #333 1px solid;
            margin: 1px;
        }

    </style>
</head>
<body>
<div>
    <ul class="sql">
        <span>total : {{count($sql_s)}}</span>
        @foreach($sql_s as $sql)
            <li>
                <div>
                    <span style="background-color: #35d0af">{{$sql->time}}ms</span>
                    <a href="{!! url('/query/id/'.$sql->id) !!}" target="_blank">{!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                </div>
            </li>
        @endforeach
    </ul>
</div>
</body>
</html>