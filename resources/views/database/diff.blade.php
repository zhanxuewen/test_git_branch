@extends('frame.body')
@section('title','Migration Diff')

@section('section')
    <div class="col-sm-6">
        {!! \App\Helper\BladeHelper::oneColumnTable('Dev - Test', array_diff($dev, $test)) !!}
        {!! \App\Helper\BladeHelper::oneColumnTable('Test - Dev', array_diff($test, $dev)) !!}
        {!! \App\Helper\BladeHelper::oneColumnTable('Dev - Online', array_diff($dev, $online)) !!}
        {!! \App\Helper\BladeHelper::oneColumnTable('Online - Dev', array_diff($online, $dev)) !!}
    </div>
@endsection