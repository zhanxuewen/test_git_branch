@extends('frame.body')
@section('title','Model List')

@section('section')
    <div class="col-sm-8">
        @foreach($modules as $module)
            <a class="btn btn-info margin-bottom margin-r-5" href="#{{$module['code']}}">{{ucfirst($module['code'])}}</a>
        @endforeach
        @foreach($modules as $module)
            <table class="table table-bordered table-hover">
                <caption><a name="{{$module['code']}}" class="btn btn-default disabled"><b>{{ucfirst($module['code'])}}</b></a></caption>
                <tr class="bg-gray">
                    <th>Model</th>
                    <th>Class Name</th>
                    <th>Table</th>
                </tr>
                @foreach($module['model_list'] as $model)
                    <tr>
                        <td><i class="fa fa-book"></i> <a href="{{url('db/get/modelInfo').'/'.$model['id']}}">{{$model['code']}}</a></td>
                        <td>{{$model['class_name']}}</td>
                        <td><a href="{{url('database/get/tableInfo/'.$model['table'])}}">{{$model['table']}}</a></td>
                    </tr>
                @endforeach
            </table>
        @endforeach
        <hr>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
@endsection