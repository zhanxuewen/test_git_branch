@extends('frame.body')
@section('title','Migration Diff')

@section('section')
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
@endsection