@extends('frame.body')
@section('title','Monitor Table')

@section('section')
    <div class="col-sm-12">
        @foreach($rows as $key => $row)
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Group {{$key + 1}}</h3>

                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <input type="hidden" id="row{{$key}}" value="{{$row}}">
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
    <script>
        let row;
        let data;
        $.each(JSON.parse('{{$keys}}'), function () {
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