@extends('frame.body')
@section('title','Sql Analyze Query Sql')

@section('section')
    <div class="col-xs-12">
        <nav aria-label="Page navigation">{!! $sql_s->render() !!}</nav>
        <table class="table table-bordered table-hover">
            <caption>total : {{count($sql_s)}}</caption>
            @foreach($sql_s as $sql)
                <tr>
                    <td>
                        <span class="label @if($sql->time >= 1000) bg-red @else bg-gray @endif">{{$sql->time}}ms</span>
                        <a href="{!! url('/query/id/'.$sql->id) !!}" target="_blank">
                            {!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection