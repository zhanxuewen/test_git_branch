@extends('frame.body')
@section('title','Api Info')

@section('section')
    <div class="col-sm-12">
        <table class="table table-bordered table-hover">
            <tr>
                <th>Service</th>
                <td><a href="{{url('service/get/serviceList').'#'.$api['service']['code']}}">{{$api['service']['class_name']}}</a></td>
            </tr>
            <tr>
                <th>IoC Variables</th>
                <td>{{str_replace(',', ', ', $api['service']['ioc_variables'])}}</td>
            </tr>
            <tr>
                <th>IoC Repositories</th>
                <td>{{str_replace(',', ', ', $api['service']['ioc_repos'])}}</td>
            </tr>
        </table>
        <hr>
        <h4><span class="text-gray">{{$api['modifier']}} function</span> <span class="text-blue">{{$api['function_name']}}</span>(<span
                class="text-orange">{{str_replace(',', ', ', $api['params'])}}</span>)
        </h4>
        <h4>{</h4>
        @if($api['has_transaction'])
            <h4>&nbsp;&nbsp;&nbsp;&nbsp;<span>\DB::<span class="text-blue">beginTransaction</span>()</span></h4>
        @endif
        <br>
        @if(isset($api['call_list']))
            @foreach($api['call_list'] as $call)
                <h4>&nbsp;&nbsp;&nbsp;&nbsp;<span>$this->@if(isset($call['repository'])){{$map[$call['repository']['code']]}}->@endif<span class="text-blue">
                            @if($call['function_id'] != 0)
                                <a href="{{url('repo/get/functionInfo').'/'.$call['function_id']}}" class="text-green">
                                    {{$call['function_name']}}
                                </a>
                            @else
                                {{$call['function_name']}}
                            @endif
                        </span>(<span>@if(!is_null($call['params'])){{$call['params']}}@endif</span>)</span>
                </h4>
            @endforeach
        @endif
        <br>
        @if(!is_null($api['return']))
            <h4>&nbsp;&nbsp;&nbsp;&nbsp;<span>return <span class="text-blue">{{$api['return']}}</span></span></h4>
        @endif
        <h4>}</h4>
        <hr>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="{{url('service/get/serviceList')}}"><i class="fa fa-arrow-left"></i> Back to List</a>
            <a class="btn btn-primary" href="#"><i class="fa fa-arrow-up"></i> Back to Top</a>
        </div>
    </div>
@endsection