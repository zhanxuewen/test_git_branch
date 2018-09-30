@extends('frame.body')
@section('title','Table List')

@section('section')
    <div class="col-sm-12">
        <table class="table table-bordered table-hover">
            @foreach($tables as $table)
                <tr>
                    <td>
                        <a class="label label-primary" href="{{url('database/get/tableInfo/'.$table['table_name'])}}">{{$table['table_name']}}</a>
                        <br><br>
                        <span>
                            @foreach(explode(',',$table['columns']) as $column)
                                <span class="label label-default">{{$column}}</span>
                            @endforeach
                        </span>
                    </td>
                </tr>
            @endforeach
        </table>
        <hr>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
@endsection