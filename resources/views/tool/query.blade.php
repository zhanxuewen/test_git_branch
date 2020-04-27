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

@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $("#query").click(function () {
                $.ajax({
                    type: "POST",
                    url: "/tool/query",
                    data: $("#form").serialize(),
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function (data) {
                        console.log(data);
                    }
                });
            });
        });
    </script>
@endsection