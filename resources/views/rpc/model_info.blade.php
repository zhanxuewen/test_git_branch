@extends('frame.body')
@section('title','Model Info')

@section('section')
    <div class="col-sm-8">
        <table class="table table-bordered table-hover">
            <caption>{{$model['code']}}</caption>
            @foreach(['alias', 'class_name', 'table', 'fillable', 'timestamps', 'use_soft_deletes'] as $key)
                <tr>
                    <th>{{ucwords(str_replace('_',' ',$key))}}</th>
                    <td>{{$model[$key]}}</td>
                </tr>
            @endforeach
        </table>
        @if(!empty($model['relation_list']))
            <table class="table table-bordered table-hover">
                <caption>Relation</caption>
                <tr class="bg-gray">
                    <th>Name</th>
                    <th>Type</th>
                    <th>Model</th>
                    <th>Foreign Key</th>
                    <th>Local Key</th>
                </tr>
                @foreach($model['relation_list'] as $relation)
                    <tr>
                        <td>{{$relation['relation']}}</td>
                        <td>{{$relation['relate_type']}}</td>
                        <td><i class="fa fa-book"></i> <a href="{{url('db/get/modelInfo').'/'.$relation['related_model_id']}}">
                                {{$relation['related_model']}}</a></td>
                        <td>{{$relation['foreign_key']}}</td>
                        <td>{{$relation['local_key']}}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        @if(!empty($model['repo_function_list']))
            <table class="table table-bordered table-hover">
                <caption>Repository Function</caption>
                <tr class="bg-gray">
                    <th>Modifier</th>
                    <th>Function</th>
                    <th>Params</th>
                </tr>
                @foreach($model['repo_function_list'] as $function)
                    <tr>
                        <td>{{$function['modifier']}}</td>
                        <td><i class="fa {!! \App\Helper\BladeHelper::modifierToIcon($function['modifier']) !!}"></i>
                            <a href="{{url('repo/get/functionInfo').'/'.$function['id']}}">{{$function['function_name']}}</a></td>
                        <td>{{$function['params']}}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        <hr>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="{{url('db/get/modelList')}}"><i class="fa fa-arrow-left"></i> Back to List</a>
            <a class="btn btn-primary" href="#"><i class="fa fa-arrow-up"></i> Back to Top</a>
        </div>
    </div>
@endsection