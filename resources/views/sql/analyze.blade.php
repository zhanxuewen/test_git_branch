@extends('frame.body')
@section('title','Sql Analyze')

@section('section')
    <div class="col-xs-12">
        @foreach([$type_s,$group_s,$auth_s] as $key=>$item)
            {!! \App\Helper\BladeHelper::renderOptions($item,$key,[$_type,$_group,$_auth]) !!}
        @endforeach
        <br><br>
        <div class="btn-group" role="group">
            @foreach($days as $day=> $label)
                <a class="btn btn-default @if($_day == $day) btn-primary active @endif"
                   href="{{URL::current().'?day='.$day}}">{{$label}}</a>
            @endforeach
        </div>
        <div class="pull-right">
            <form class="form-inline" action="{{url('query/empty')}}" method="get">
                <div class="form-group">
                    <label for="auth">Auth</label>
                    <select class="form-control" name="auth" id="auth">
                        <option value="0">请选择</option>
                        @foreach($auth_s as $auth)
                            <option value="{{$auth}}">{{$auth}}</option>
                        @endforeach
                    </select>
                </div>
                <input class="btn btn-primary" type="submit" value="清空">
            </form>
        </div>
        <hr>
        <a class="btn btn-warning pull-right" id="toggle-hide">Bad Sql / All</a>
        <nav aria-label="Page navigation">{!! $sql_s->render() !!}</nav>
        <table class="table table-bordered table-hover">
            <caption><b>[{{ucfirst($project)}} - {{ucfirst($conn)}}]</b> total : {{count($sql_s)}}</caption>
            @foreach($sql_s as $sql)
                <tr>
                    <td @if(\App\Helper\Helper::needHide($sql->explain, $conn) == true && $sql->time < 1000) class="need-hide" @endif>
                        @if(!isset($sql->count))
                            <span class="label @if($sql->time >= 1000) bg-red @else bg-gray @endif">{{$sql->time}}ms</span>
                            <span class="label bg-teal">{{$sql->auth}}</span>
                            <span>{!! \App\Helper\Helper::showExplain($sql->explain, $project.'-'.$conn) !!}</span><br>
                            <a href="{!! url('/query/id/'.$sql->id) !!}"
                               target="_blank">{!! \App\Helper\Helper::vsprintf($sql->query,$sql->bindings) !!}</a>
                            @if($sql->time >= 1000 && $_type == 'select')
                                <span class="query_sql bg-orange btn btn-xs">Query Again</span>
                            @endif
                        @else
                            <span class="label bg-green">{{isset($sql->count)?$sql->count:1}}</span>
                            <a href="{!! url('/query/sql').'?query='.($sql->query) !!}"
                               target="_blank">{{$sql->query}}</a>
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
            $("#toggle-hide").click(function () {
                $(".need-hide").toggle();
            });

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