@extends('frame.body')
@section('title','Labels')

@section('section')
    <div class="col-sm-12">
        <div class="btn-group col-sm-3" role="group">
            @foreach(['core' => '在线助教', 'learning' => '百项过', 'word_short' => '单词速记'] as $k => $label)
                <a class="btn btn-default @if($k == $project) btn-primary active @endif"
                   href="{{URL::current().'?project='.$k.'&type_id='.$type_id}}">{{$label}}</a>
            @endforeach
        </div>
        <div class="btn-group col-sm-9" role="group">
            @foreach($types as $type)
                <a class="btn btn-default @if($type_id == $type['id']) btn-primary active @endif"
                   href="{{URL::current().'?project='.$project.'&type_id='.$type['id']}}">{{$type['name']}}</a>
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