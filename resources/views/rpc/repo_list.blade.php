@extends('frame.body')
@section('title','Repository List')

@section('section')
    <div class="col-sm-8">
        @foreach($repos as $repo)
            <a class="btn btn-info margin-bottom margin-r-5" href="#{{$repo['code']}}">{{ucfirst($repo['code'])}}</a>
        @endforeach
        @foreach($repos as $repo)
            <table class="table table-bordered table-hover">
                <caption><a name="{{$repo['code']}}" class="btn btn-default disabled"><b>{{$repo['class_name']}}</b></a></caption>
                <tr class="bg-gray">
                    <th>Modifier</th>
                    <th>Function</th>
                    <th>Params</th>
                </tr>
                @foreach($repo['functions_list'] as $function)
                    <tr>
                        <td>{{$function['modifier']}}</td>
                        <td><i class="fa {!! \App\Helper\BladeHelper::modifierToIcon($function['modifier']) !!}"></i>
                            <a href="{{url('repo/get/functionInfo').'/'.$function['id']}}">{{$function['function_name']}}</a></td>
                        <td>{{$function['params']}}</td>
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