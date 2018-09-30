@extends('frame.body')
@section('title','Table Info')

@section('section')
    <div class="col-sm-8">
        <div class="col-sm-4">
            <table class="table table-bordered table-hover">
                <caption>{{$table['table_name']}}</caption>
                @foreach(['engine', 'table_rows', 'auto_increment'] as $key)
                    <tr>
                        <th>{{ucwords(str_replace('_',' ',$key))}}</th>
                        <td>{{$table[$key]}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        <div class="col-sm-9">
            <table class="table table-bordered table-hover">
                <caption>Columns</caption>
                <tr class="bg-gray">
                    <th>Name</th>
                    <th>Default</th>
                    <th>Nullable</th>
                    <th>Data Type</th>
                    <th>Column Type</th>
                </tr>
                @foreach($columns as $column)
                    <tr>
                        <td>{{$column['column_name']}}</td>
                        <td>@if(is_null($column['column_default'])) <i>Null</i> @else {{$column['column_default']}} @endif</td>
                        <td>{{$column['is_nullable']}}</td>
                        <td>{{$column['data_type']}}</td>
                        <td>{{$column['column_type']}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
        <hr>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
@endsection