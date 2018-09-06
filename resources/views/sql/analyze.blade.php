@extends('frame.body')
@section('title','Sql Analyze')

@section('section')
    <div id="scroll">
        @foreach([$type_s,$group_s,$auth_s] as $key=>$item)
            {!! \App\Helper\BladeHelper::renderOptions($item,$key,[$_type,$_group,$_auth]) !!}
        @endforeach
        <hr>
        <ul class="list">
            <span>total : {{count($sql_s)}}</span>
            @foreach($sql_s as $sql)
                <li>
                    @if(!isset($sql->count))
                        <span class="green-bg">{{$sql->time}}ms</span>
                        <a href="{!! url('/query/id/'.$sql->id) !!}" target="_blank">{!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                    @else
                        <span class="green-bg">{{isset($sql->count)?$sql->count:1}}</span>
                        <a href="{!! url('/query/sql').'?query='.($sql->query) !!}" target="_blank">{{$sql->query}}</a>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="page">{!! $sql_s->render() !!}</div>
    </div>
@endsection