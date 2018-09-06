@extends('frame.body')
@section('title','Sql Analyze Query Sql')

@section('section')
    <div id="scroll">
        <ul class="list">
            <span>total : {{count($sql_s)}}</span>
            @foreach($sql_s as $sql)
                <li>
                    <div>
                        <span class="green-bg">{{$sql->time}}ms</span>
                        <a href="{!! url('/query/id/'.$sql->id) !!}" target="_blank">{!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection