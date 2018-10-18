@extends('frame.body')
@section('title','Dashboard')

@section('section')
    <div class="col-sm-12">
        @foreach($rows as $key => $row)
            <input type="hidden" id="row{{$key}}" value="{{$row}}">
            <p>Level {{$key}}</p>
            <canvas id="myChart{{$key}}" width="1000" height="600"></canvas>
        @endforeach
    </div>
@endsection

@section('script')
    <script>
        let row;
        let data;
        $.each(JSON.parse('{{$levels}}'), function () {
            row = $('#row' + this).val();
            let set = [];
            $.each(JSON.parse(row), function (index) {
                set.push({
                    label: this.table,
                    fill: false,
                    backgroundColor: '#F' + (9 - index) + index,
                    borderColor: '#F' + (9 - index) + index,
                    data: this._rows.split(',')
                });
            });

            data = {
                labels: JSON.parse('{!! $dates !!}'),
                datasets: set
            };
            new Chart($("#myChart" + this).get(0).getContext("2d"), {type: 'line', data: data});
        });
    </script>
@endsection