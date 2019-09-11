@extends('frame.body')
@section('title','Table List')

<style>
    .list-module-info {
        margin-left: 10px;
    }
</style>

@section('section')
    <div class="col-sm-12">
        <div class="col-sm-12">
            @foreach(['core'=>'在线助教','learning'=>'百项过'] as $_project => $label)
                <a class="btn btn-default @if($_project == $project) btn-primary active @endif"
                   href="{!! URL::current().'?project='.$_project !!}">{{$label}}</a>
            @endforeach
        </div>
        <div class="col-sm-12">
            <hr>
        </div>
        @foreach($tables as $module => $tabs)
            <div class="col-sm-3">
                <a class="label label-primary"
                   href="{{url('database/get/tableInfo/' . $module . '?project=' . $project)}}">{{ucfirst($module)}}
                </a>
                @if(isset($groups['module'][$module])) <span
                        class="list-module-info bg-green">{{$groups['module'][$module]['name']}}</span>@endif
                <ul>
                    @foreach($tabs as $table)
                        <li>{{$table}}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
        <div class="col-sm-12">
            <hr>
        </div>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
@endsection