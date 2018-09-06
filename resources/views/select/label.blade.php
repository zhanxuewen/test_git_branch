@extends('frame.body')
@section('title','Labels')

@section('section')
    <div id="scroll">
        <p>Root</p>
        {!! \App\Helper\BladeHelper::getTree(0, $labels) !!}
    </div>
@endsection