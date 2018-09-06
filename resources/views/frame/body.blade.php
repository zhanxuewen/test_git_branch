<!DOCTYPE html>
<html>
<head>
    <title>@yield('title')</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="{{asset('css/frame.css')}}" type="text/css">
</head>
<body>
<div id="sidebar">
    @include('frame.sidebar')
</div>

<div id="section">
    @yield('section')
</div>
</body>
</html>