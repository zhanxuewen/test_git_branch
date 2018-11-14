@extends('frame.body')
@section('title','Diff')

@section('section')
    <div class="col-sm-12">
        @foreach(['migration','table'] as $type)
            <a class="btn btn-default @if($type == $_type) btn-primary active @endif"
               href="{!! url('database/diff').'?type='.$type !!}">Diff {{ucfirst($type)}}</a>
        @endforeach
    </div>
    <div class="col-sm-6">
        @if(isset($dev) && isset($test))
            {!! \App\Helper\BladeHelper::oneColumnTable('Dev - Test', array_diff($dev, $test)) !!}
            {!! \App\Helper\BladeHelper::oneColumnTable('Test - Dev', array_diff($test, $dev)) !!}
        @endif
        @if(isset($dev) && isset($online))
            {!! \App\Helper\BladeHelper::oneColumnTable('Dev - Online', array_diff($dev, $online)) !!}
            {!! \App\Helper\BladeHelper::oneColumnTable('Online - Dev', array_diff($online, $dev)) !!}
        @endif
    </div>
@endsection