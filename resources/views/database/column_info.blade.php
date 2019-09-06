@extends('frame.body')
@section('title','Column Info')

<style>
    .db-group ul {
        list-style: none;
        padding-left: 10px;
    }

    .db-group ul li span:hover {
        cursor: pointer;
    }
</style>

@section('section')
    <div class="col-sm-12">
        <div class="col-sm-6 db-group">
            <h4>Groups</h4>
            <ul>
                {!! \App\Helper\BladeHelper::buildGroupTree(0,$groups) !!}
            </ul>
            <hr>
            <form action="{{URL::current()}}" method="get">
                <input type="hidden" name="method" value="put_group">
                <input type="hidden" name="parent_id" id="form-parent-id">
                <input type="hidden" name="is_available" value="1">
                <table>
                    <caption>New Group Info</caption>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                    <tr>
                        <td><input class="form-control" type="text" name="code"></td>
                        <td><input class="form-control" type="text" name="name"></td>
                        <td><input class="form-control btn-success" type="submit" value="Create"></td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="col-sm-6">
            <form action="{{URL::current()}}" method="get">
                <input type="hidden" name="method" value="put_column">
                <input type="hidden" name="group_id" id="form-group-id">
                <input type="hidden" name="is_available" value="1">
                <table>
                    <caption>New Column Info</caption>
                    <tr>
                        <th>Column</th>
                        <th>Info</th>
                        <th>Action</th>
                    </tr>
                    <tr>
                        <td><input class="form-control" type="text" name="column"></td>
                        <td><input class="form-control" type="text" name="info"></td>
                        <td><input class="form-control btn-success" type="submit" value="Create"></td>
                    </tr>
                </table>
            </form>
            <hr>
            @foreach($columns as $group => $col)
                <div id="column-{{$group}}" class="column-group" style="display: none">
                    <table class="table table-bordered table-hover">
                        <tr>
                            <th>Column</th>
                            <th>Info</th>
                            <th>Available</th>
                        </tr>
                        @foreach($col as $column)
                            <tr>
                                <td>{{$column->column}}</td>
                                <td>{{$column->info}}</td>
                                <td>{{$column->is_available ? 'Yes' : 'No'}}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        </div>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {

        });

        function showColumns(obj, id) {
            $(".db-group span").removeClass('bg-green');
            $(obj).addClass('bg-green');
            $("#form-group-id").val(id);
            $("#form-parent-id").val(id);
            $(".column-group").hide();
            $("#" + "column-" + id).show();
        }
    </script>
@endsection