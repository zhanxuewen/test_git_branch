<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>Error</title>

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
            font-family: 'Consolas';
        }

        .container {
            text-align: center;
            display: table-cell;
            vertical-align: middle;
        }

        .content {
            text-align: center;
            display: inline-block;
        }

        .title {
            font-size: 96px;
            color: #ff0000;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <div class="title">{{$message}}</div>
        <h2>请返回</h2>
    </div>
</div>
</body>
</html>