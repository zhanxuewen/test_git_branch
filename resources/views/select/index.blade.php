<!DOCTYPE html>
<html>
<head>
    <title>Select</title>

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
            margin: 20px;
        }


    </style>
</head>
<body>
<div class="select_info">
    @foreach($rows as $query =>$row)
        <p>{{$query}}</p>
        <table border="1px">
            @for($i = 0; $i< count($row[0]);$i++)
                <tr>
                    @foreach($row as $value)
                        <td>{{$value[$i]}}</td>
                    @endforeach
                </tr>
            @endfor
        </table>
    @endforeach
</div>
</body>
</html>