@extends('frame.body')
@section('title','Throttle')

@section('section')
    <div class="col-sm-8">
        @include('monitor.throttle.head')

        @foreach($list as $key => $item)
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">@if($_group == 'token') {{$accounts[$key]['nickname']}} @else {{$key}} @endif</h3>

                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <input type="hidden" id="row{{$key}}" value="{{$item}}">
                    <div class="chart">
                        <canvas id="myChart{{$key}}" width="1000" height="400"></canvas>
                    </div>
                </div>
                <!-- /.box-body -->
            </div>
        @endforeach
    </div>
@endsection

@section('script')
    {{--<script>--}}
        {{--let row;--}}
        {{--let data;--}}
        {{--$.each(JSON.parse('{{$keys}}'), function () {--}}
            {{--$.each(JSON.parse($('#row' + this).val()), function (b) {--}}
                {{--let set = [];--}}
                {{--$.each(this.items, function (index) {--}}
                    {{--console.log(this);--}}
                    {{--set.push({--}}
                        {{--label: b,--}}
                        {{--fill: false,--}}
                        {{--backgroundColor: '#F' + (9 - index) + index,--}}
                        {{--borderColor: '#F' + (9 - index) + index,--}}
                        {{--data: 1--}}
                    {{--});--}}
                {{--});--}}
                {{--// console.log(set);--}}

            {{--});--}}

            {{--data = {--}}
            {{--labels: JSON.parse('{!! $dates !!}'),--}}
            {{--datasets: set--}}
            {{--};--}}
            {{--new Chart($("#myChart" + this).get(0).getContext("2d"), {type: 'line', data: data});--}}
        {{--});--}}
    {{--</script>--}}
@endsection