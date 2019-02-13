@extends('frame.body')
@section('title','Order Excels')

@section('section')
    <div class="col-xs-12 col-sm-6">
        <form class="form-inline" action="{{URL::current()}}" method="get">
            <div class="form-group">
                <label for="month">Month</label>
                <select class="form-control" name="month" id="month">
                    {!! \App\Helper\BladeHelper::monthOption($month, '2018/9') !!}
                </select>
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
        <table class="table table-bordered table-hover">
            <captain>Excels</captain>
            <tr>
                <th>Day</th>
                <th>Excel</th>
                <th>Info</th>
            </tr>
            @foreach($list as $day => $excel)
                <tr>
                    <td>{{$day}}</td>
                    <td>@if($excel == '')
                            [ <a href="{{URL::current().'?month='.$month.'&day='.$day}}">Rebuild</a> ]
                        @else{{$excel}} @endif</td>
                    <td>1</td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection