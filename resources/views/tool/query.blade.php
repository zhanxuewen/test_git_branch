@extends('frame.body')
@section('title','Navicat')

@section('section')
    <div class="col-sm-6">
        <form id="form" action="{{URL::current()}}" method="POST" class="form-inline">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="conn">Project - Conn</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach($projects as $project => $conn_s)
                        @foreach($conn_s as $_conn)
                            <option value="{{$project.'-'.$_conn}}">
                                {{ucfirst($project)}} - {{ucfirst($_conn)}}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <button id="query" type="button" class="btn btn-default pull-right">Query</button>
            <div class="form-group">
                <label for="sql">SQL</label>
                <textarea name="sql" id="sql" cols="80" rows="15" class="form-control"></textarea>
            </div>
        </form>
    </div>
    <div class="col-sm-6">
        @if(\App\Helper\BladeHelper::checkSuper())
            <a href="{{url('tool/show/queries')}}" class="btn btn-default pull-right">Show Queries</a>
        @endif
        <p>规则:</p>
        <ul>
            <li>仅用于查询, 执行操作有记录</li>
            <li>语句必须附加 [limit]</li>
        </ul>
        <h3 id="alert" class="text-red"></h3>
    </div>
    <div class="col-sm-12">
        <div class="table-box table-responsive">
            <table id="table" class="table table-bordered table-hover">
            </table>
        </div>
    </div>

    <style>
        .table-box {
            margin-top: 5px;
        }

        #table tr th {
            padding: 5px 10px;
        }
    </style>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $("#query").click(function () {
                $("h3#alert").hide();
                $("#table").html('');
                $.ajax({
                    type: "POST",
                    url: "/tool/query",
                    data: $("#form").serialize(),
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function (data) {
                        console.log(data);
                        if ($.inArray(data, ['f', 'l']) !== -1) {
                            let arr = {'f': 'Connection Failed!', 'l': "缺少 [limit]"};
                            $("h3#alert").html(arr[data]).show();
                        } else {
                            let dat = JSON.parse(data);
                            let keys = dat['keys'];
                            let rows = dat['rows']
                            let table = $("#table");
                            table.append("<caption>use <b>" + dat['time'] + "</b>s</caption>")
                            let th = $("<tr></tr>");
                            $.each(keys, function () {
                                th.append("<th>" + this + "</th>");
                            });
                            table.append(th);
                            let tbo = $("<tbody></tbody>");
                            $.each(rows, function (i, row) {
                                let tr = $("<tr></tr>");
                                $.each(keys, function (j, key) {
                                    tr.append("<td>" + row[key] + "</td>");
                                });
                                tbo.append(tr);
                            })
                            table.append(tbo);
                        }
                    }
                });
            });
        });
    </script>
@endsection