@extends('frame.body')
@section('title','Monitor Zabbix')

@section('section')
    <div class="col-sm-12">
        @foreach([1,3,7] as $day)
            <a class="btn btn-default @if($day == $_day) btn-primary active @endif"
               href="{!! URL::current().'?day='.$day !!}">{{$day}} day</a>
        @endforeach
        <hr>
        @foreach($data as $item)
            <img src="{{$item}}" alt=""><br><br>
        @endforeach
    </div>
@endsection