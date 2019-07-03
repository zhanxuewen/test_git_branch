@extends('frame.body')
@section('title','Diagram UML')

@section('section')
    <div class="col-sm-12">
        @foreach($projects as $project => $label)
            <a class="btn btn-default @if($project == $_project) btn-primary active @endif"
               href="{!! url('diagrams/uml').'?project='.$project !!}">{{ucfirst($label)}}</a>
        @endforeach
    </div>
    <div class="col-sm-10">
        @foreach($images as $image)
            @if($image == 'Modules.jpg') <h3>项目模块</h3>
            @else <h3>{!! explode('.',$image)[0] !!}</h3> @endif
            <img src="{{asset($dir . $image)}}" alt="" class="img-responsive">
            <hr>
        @endforeach
    </div>
@endsection