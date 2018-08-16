<!DOCTYPE html>
<html>
<head>
    <title>Sql Analyze Query Id</title>

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

        div.display {
            display: inline-block;
            margin: 50px;
        }

        table.label tr th {
            text-align: right;
        }

    </style>
</head>
<body>
<div>
    <div class="display">
        <table class="label">
            <tr>
                <th>Query</th>
                <td>{{$sql->query}}</td>
            </tr>
            <tr>
                <th>Time</th>
                <td>{{$sql->time}} ms</td>
            </tr>
            <tr>
                <th>Auth</th>
                <td>{{$sql->auth}}</td>
            </tr>
            <tr>
                <th>Created</th>
                <td>{{$sql->created_at}}</td>
            </tr>
        </table>
        <p><b>Explain</b></p>
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
        <p><b>Trace</b></p>
        <div>{!! dump($sql->trace) !!}</div>
        <br>
    </div>
</div>
</body>
</html>