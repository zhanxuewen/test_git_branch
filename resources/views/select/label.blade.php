@extends('frame.body')
@section('title','Labels')

@section('section')
    <div class="col-sm-12">
        <div class="btn-group" role="group">
            @foreach($types as $type)
                <a class="btn btn-default @if($type_id == $type['id']) btn-primary active @endif"
                   href="{{url('select/labels').'?type_id='.$type['id']}}">{{$type['name']}}</a>
            @endforeach
        </div>
        <hr>
    </div>
    <div class="col-sm-8">
        <ul class="sidebar-menu tree" data-widget="tree">
            {!! empty($labels) ? '' : \App\Helper\BladeHelper::getTree(0, $labels) !!}
        </ul>
    </div>
@endsection