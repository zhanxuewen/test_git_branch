@extends('frame.body')
@section('title','Diagram UML')

@section('section')
    <div class="col-sm-12">
        @foreach($projects as $project)
            <a class="btn btn-default @if($project == $_project) btn-primary active @endif"
               href="{!! url('diagrams/uml').'?project='.$project !!}">{{ucfirst($project)}}</a>
        @endforeach
    </div>
    <div class="col-sm-10">
        @foreach($images as $image)
            <img src="{{asset($dir . $image)}}" alt="" class="img-responsive">
        @endforeach
    </div>
@endsection