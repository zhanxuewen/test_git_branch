@extends('frame.body')
@section('title','Table Info')

<style>
    .info-default {
        color: #FFFFFF;
        background-color: #7D916F;
    }
</style>

@section('section')
    <div class="col-sm-12">
        <div class="col-sm-12">
            <h3>{{ucfirst($module_name)}}</h3>
        </div>
        @foreach($columns as $table => $col)
            <div class="col-sm-12">
                <table class="table table-bordered table-hover">
                    <caption><b>Table:</b> {{$table}}</caption>
                    <tr class="bg-gray">
                        <th>Name</th>
                        <th>Column Type</th>
                        <th>Default</th>
                        <th>Nullable</th>
                        <th class="bg-green">Info</th>
                        <th>Group</th>
                    </tr>
                    @foreach($col as $column)
                        <tr>
                            <td>{{$column['column_name']}}</td>
                            <td>{{$column['column_type']}}</td>
                            <td>@if(is_null($column['column_default']))
                                    <i>Null</i> @else {{$column['column_default']}} @endif</td>
                            <td>{{$column['is_nullable']}}</td>
                            @if(isset($cols[$column['column_name']]))
                                @php $info = App\Helper\BladeHelper::getColumnInfo($cols[$column['column_name']], $column['table_name'], $module_name, $project) @endphp
                                <td>{{$info['info']}}</td>
                                <td class="info-{{$info['code']}}">{{$info['group']}}</td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
@endsection