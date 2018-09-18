@extends('frame.body')
@section('title','Service List')

@section('section')
    <div class="col-sm-8">
        @foreach($services as $service)
            <a class="btn btn-info margin-bottom margin-r-5" href="#{{$service['code']}}">{{ucfirst($service['code'])}}</a>
        @endforeach
        @foreach($services as $service)
            <table class="table table-bordered table-hover">
                <caption><a name="{{$service['code']}}" class="btn btn-default disabled"><b>{{$service['class_name']}}</b></a></caption>
                <tr class="bg-gray">
                    <th>Modifier</th>
                    <th>Function</th>
                    <th>Params</th>
                </tr>
                @foreach($service['api_list'] as $api)
                    <tr>
                        <td>{{$api['modifier']}}</td>
                        <td><i class="fa {!! \App\Helper\BladeHelper::modifierToIcon($api['modifier']) !!}"></i>
                            <a href="{{url('service/get/apiInfo').'/'.$api['id']}}">{{$api['function_name']}}</a></td>
                        <td>{{str_replace(',', ', ', $api['params'])}}</td>
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