@extends('frame.body')
@section('title','Monitor Circle Table')

@section('section')
    <div class="col-sm-12">
        <div class="col-sm-12">
            <form action="{{url('monitor/circleTable')}}" class="form-inline" method="GET">
                <div class="form-group">
                    <label for="table">Table</label>
                    <select class="form-control" name="table" id="table">
                        @foreach($tables as $_table)
                            <option value="{{$_table}}" @if($table == $_table) selected @endif>{{$_table}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="start">Start Date</label>
                    <input type="date" class="form-control" name="start" value="{{$start}}" id="start">
                </div>
                <input class="btn btn-primary" type="submit" value="环比">
            </form>
        </div>
        <div class="col-sm-12">
            <input type="hidden" id="circle" value="{{$circles}}">
            <div class="chart">
                <canvas id="myChart" width="1000" height="400"></canvas>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        let rows = JSON.parse('{!! $circles !!}');
        let table = '{!! $table !!}';
        let set = [];
        set.push({
            label: table,
            fill: false,
            backgroundColor: '#F00',
            borderColor: '#F00',
            data: rows.split(',')
        });

        let data = {
            labels: JSON.parse('{!! $dates !!}'),
            datasets: set
        };
        new Chart($("#myChart").get(0).getContext("2d"), {type: 'line', data: data});
    </script>
@endsection