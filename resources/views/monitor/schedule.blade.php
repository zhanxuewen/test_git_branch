@extends('frame.body')
@section('title','Monitor Schedule')

@section('section')
    <div class="col-sm-12">
        <div class="btn-group" role="group">
            @foreach([1, 3, 7, 14] as $_day)
                <a class="btn btn-default @if($_day == $day) btn-primary active @endif"
                   href="{!! URL::current().'?day='.$_day !!}">{{$_day}} Day</a>
            @endforeach
        </div>
        <hr>
        <div class="col-sm-8">
            <table class="table table-bordered table-hover">
                <caption><b>Manager Client Schedule</b> [total: {{$total}}]</caption>
                <tr>
                    <th>Time</th>
                    <th>Info</th>
                </tr>
                @foreach($list as $item)
                    <tr @if(strstr($item['time'],' 00:00:')) class="text-bold" @endif>
                        <td>{{$item['time']}}</td>
                        <td>{{$item['message']}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection