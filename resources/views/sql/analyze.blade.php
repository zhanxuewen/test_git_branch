@extends('frame.body')
@section('title','Sql Analyze')

@section('section')
    <div class="col-xs-12">
        @foreach([$type_s,$group_s,$auth_s] as $key=>$item)
            {!! \App\Helper\BladeHelper::renderOptions($item,$key,[$_type,$_group,$_auth]) !!}
        @endforeach
        <hr>
        <nav aria-label="Page navigation">{!! $sql_s->render() !!}</nav>
        <table class="table table-bordered table-hover">
            <caption>total : {{count($sql_s)}}</caption>
            @foreach($sql_s as $sql)
                <tr>
                    <td>
                        @if(!isset($sql->count))
                            <span class="label @if($sql->time >= 1000) bg-red @else bg-gray @endif">{{$sql->time}}ms</span>
                            <a href="{!! url('/query/id/'.$sql->id) !!}" target="_blank">{!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                        @else
                            <span class="label bg-green">{{isset($sql->count)?$sql->count:1}}</span>
                            <a href="{!! url('/query/sql').'?query='.($sql->query) !!}" target="_blank">{{$sql->query}}</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection