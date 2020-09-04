@extends('frame.body')
@section('title','进销存库存')

@section('section')
    <div class="col-sm-12">
        <a href="{{URL::current().'?action=export'}}" class="btn btn-default pull-right">导出</a>
        <table class="table table-bordered table-hover">
            <caption>库存</caption>
            <tr>
                @foreach($mapping as $key => $field)
                    <th>{{$field}}</th>
                @endforeach
            </tr>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $k => $value)
                        <td>{{$value}}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
@endsection