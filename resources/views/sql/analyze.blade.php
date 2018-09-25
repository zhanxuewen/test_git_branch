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
                            @if($sql->time >= 1000) <span class="query_sql bg-orange btn btn-xs">Query Again</span> @endif
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

@section('script')
    <script>
        $(document).ready(function () {
            $(".query_sql").click(function () {
                let sql = $(this).prev().text();
                let data = $.ajax({
                    type: "GET",
                    url: "/ajax/query/sql",
                    async: false,
                    data: "sql=" + sql,
                });
                let time = data.responseText;
                $(this).html(time + 'ms');
                $(this).removeClass();
                $(this).addClass('label');
                if (time > 1000) {
                    $(this).addClass('bg-red');
                } else {
                    $(this).addClass('bg-green');
                }
            });
        });
    </script>
@endsection