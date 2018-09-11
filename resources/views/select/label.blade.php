@extends('frame.body')
@section('title','Labels')

@section('section')
    <div class="col-xs-4">
        <ul class="sidebar-menu tree" data-widget="tree">
            {!! \App\Helper\BladeHelper::getTree(0, $labels) !!}
        </ul>
    </div>
@endsection