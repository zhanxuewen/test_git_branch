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

        ul.auth li {
            display: inline-block;
            margin-right: 10px;
        }

        ul.type li {
            display: inline-block;
            margin-right: 10px;
        }

        ul.group li {
            display: inline-block;
            margin-right: 10px;
        }

        ul.sql > li {
            border: #333 1px solid;
            margin: 1px;
        }

        ul.sql > div.display > span.label {
            display: inline-block;
            width: 100px;
            text-align: right;
            margin-right: 5px;
        }

        ul.sql > div.display {
            display: inline-block;
        }

        ul.sql > div.display > table {
            display: inline-block;
        }

    </style>
</head>
<body>
<div>
    <ul class="auth">
        @foreach($auth_s as $auth)
            <li @if($_auth==$auth) class="checked" @endif>
                <a href="{!! url('/newSql').'?auth='.$auth.(isset($_type)?'&type='.$_type:'').'&group='.$_group !!}">{{$auth}}</a>
            </li>
        @endforeach
    </ul>
</div>
<div>
    <ul class="type">
        @foreach($types as $type)
            <li @if($_type==$type) class="checked" @endif>
                <a href="{!! url('/newSql').'?type='.$type.(isset($_auth)?'&auth='.$_auth:'').'&group='.$_group !!}">{{$type}}</a>
            </li>
        @endforeach
    </ul>
</div>
<div>
    <ul class="group">
        <li @if($_group==0) class="checked" @endif>
            <a href="{!! url('/newSql').'?group=0'.(isset($_type)?'&type='.$_type:'').(isset($_auth)?'&auth='.$_auth:'') !!}">group : 0</a>
        </li>
        <li @if($_group==1) class="checked" @endif>
            <a href="{!! url('/newSql').'?group=1'.(isset($_type)?'&type='.$_type:'').(isset($_auth)?'&auth='.$_auth:'') !!}">group : 1</a>
        </li>
    </ul>
</div>
<hr>
<div>

    <ul class="sql">
        @if(isset($sql))
            <div class="display">
                <span class="label">Query :</span>{{$sql->query}} <br>
                <span class="label">Time :</span>{{$sql->time}} ms <br>
                <span class="label">Auth :</span>{{$sql->auth}} <br>
                <span class="label">Created :</span>{{$sql->created_at}} <br>
                <span class="label">Explain :</span>
                <table border="1">
                    <tr>
                        @foreach($sql->explain[0] as $key=> $value)
                            <th>{{$key}}</th>
                        @endforeach
                    </tr>
                    @foreach($sql->explain as $rows)
                        <tr>
                            @foreach($rows as $key=>$value)
                                @if($key=='rows')
                                    <td @if (($value/$total[$rows->table])>0.05) bgcolor="#FA6B6B" @endif>
                                        <b>{{$value}} / {{$total[$rows->table]}} ({!! round($value/$total[$rows->table]*100,2) !!}%)</b>
                                    </td>
                                @else
                                    <td>{{$value}}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </table>
                <br>
                <span class="label">Trace :</span>
                <div style="display: inline-block;">{!! dump(\App\Helper\Helper::convertQuot(json_decode($sql->trace,true))) !!}</div>
                <br>
            </div>

        @else
            <span>total : {{count($sql_s)}}</span>
            @foreach($sql_s as $sql)
                <li>
                    <div>
                        @if(!isset($sql->count))
                            <span style="background-color: #35d0af">{{$sql->time}}ms</span>
                            <a href="{!! url('/newSql').'?id='.$sql->id !!}">{!! vsprintf(str_replace("?", "%s", $sql->query), str_replace('&apos;','\'', str_replace('&quot;','"',\App\Helper\Helper::carbonToString(App\Helper\Helper::decodeBindings($sql->bindings))))) !!}</a>
                        @else
                            <span style="background-color: #35d0af">{{isset($sql->count)?$sql->count:1}}</span>
                            <a href="{!! url('/newSql').'?group='.$_group.'&'.(isset($_type)?'type='.$_type.'&':'').(isset($_auth)?'auth='.$_auth.'&':'').'query='.($sql->query) !!}">{{$sql->query}}</a>
                        @endif
                    </div>
                </li>
            @endforeach
        @endif
    </ul>
</div>
</body>
</html>