<!DOCTYPE html>
<html>
<head>
    <title>Sql Analyze</title>

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
    <ul class="param">
        @foreach($auth_s as $auth)
            <li @if($_auth==$auth) class="checked" @endif>
                <a href="{!! url('/newSql').'?auth='.$auth.(isset($_type)?'&type='.$_type:'').'&group='.$_group !!}">{{$auth}}</a>
            </li>
        @endforeach
    </ul>
    <ul class="param">
        @foreach($types as $type)
            <li @if($_type==$type) class="checked" @endif>
                <a href="{!! url('/newSql').'?type='.$type.(isset($_auth)?'&auth='.$_auth:'').'&group='.$_group !!}">{{$type}}</a>
            </li>
        @endforeach
    </ul>
    <ul class="param">
        @foreach([0,1] as $group)
            <li @if($_group==$group) class="checked" @endif>
                <a href="{!! url('/newSql').'?group='.$group.(isset($_type)?'&type='.$_type:'').(isset($_auth)?'&auth='.$_auth:'') !!}">group : {{$group}}</a>
            </li>
        @endforeach
    </ul>
</div>
<hr>
<div>
    <ul class="sql">
        <span>total : {{count($sql_s)}}</span>
        @foreach($sql_s as $sql)
            <li>
                <div>
                    @if(!isset($sql->count))
                        <span style="background-color: #35d0af">{{$sql->time}}ms</span>
                        <a href="{!! url('/query/id/'.$sql->id) !!}" target="_blank">{!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                    @else
                        <span style="background-color: #35d0af">{{isset($sql->count)?$sql->count:1}}</span>
                        <a href="{!! url('/query/sql').'?query='.($sql->query) !!}" target="_blank">{{$sql->query}}</a>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</div>
</body>
</html>