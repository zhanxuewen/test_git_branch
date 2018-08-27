<!DOCTYPE html>
<html>
<head>
    <title>Migration Diff</title>

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
    <p>Dev - Test</p>
    <ul>
        @foreach(array_diff($dev,$test) as $mig)
            <li>{{$mig}}</li>
        @endforeach
    </ul>
    <ul>
        @foreach(array_diff($test,$dev) as $mig)
            <li>{{$mig}}</li>
        @endforeach
    </ul>
    <p>Dev - Online</p>
    <ul>
        @foreach(array_diff($dev,$online) as $mig)
            <li>{{$mig}}</li>
        @endforeach
    </ul>
    <ul>
        @foreach(array_diff($online,$dev) as $mig)
            <li>{{$mig}}</li>
        @endforeach
    </ul>
</div>
</body>
</html>