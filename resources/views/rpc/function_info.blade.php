@extends('frame.body')
@section('title','Repository Info')

@section('section')
    <div class="col-sm-12">
        <h3>{{$function['repository']['class_name']}}</h3>
        <hr>
        <h4><span class="text-gray">{{$function['modifier']}} function</span> <span class="text-blue">{{$function['function_name']}}</span>(<span
                class="text-orange">{{$function['params']}}</span>)
        </h4>
        <h4>{</h4>
        @if(isset($function['set_model']))
            <h4>&nbsp;&nbsp;&nbsp;&nbsp;<span>$this-><span class="text-blue">setModel</span>(
                    <a href="{{url('db/get/modelInfo').'/'.$function['set_model']['id']}}" class="text-green">'<u>{{$function['set_model']['alias']}}</u>'</a>)</span>
            </h4>
        @endif
        <h4>}</h4>
        <hr>
        @if(!empty($function['api_call_list']))
            <table class="table table-bordered table-hover">
                <caption>Api Called</caption>
                <tr class="bg-gray">
                    <th>Service</th>
                    <th>Api Function</th>
                    <th>Params</th>
                </tr>
                @foreach($function['api_call_list'] as $call)
                    <tr>
                        <td>{{$call['api']['service']['code']}}</td>
                        <td><i class="fa {!! \App\Helper\BladeHelper::modifierToIcon($function['modifier']) !!}"></i>
                            <a href="{{url('service/get/apiInfo').'/'.$call['api']['id']}}">{{$call['api']['function_name']}}</a></td>
                        <td>{{$call['api']['params']}}</td>
                    </tr>
                @endforeach
            </table>
        @endif
        <div class="col-sm-12">
            <a class="btn btn-primary" href="{{url('repo/get/repositoryList')}}"><i class="fa fa-arrow-left"></i> Back to List</a>
            <a class="btn btn-primary" href="#"><i class="fa fa-arrow-up"></i> Back to Top</a>
        </div>
    </div>
@endsection