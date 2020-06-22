@extends('frame.body')
@section('title','Dashboard')

@section('section')
    <div class="col-sm-12">
        @foreach(['table'=>'Table Increment','circle'=> 'Table Circle Increment'] as $key => $label)
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">{{$label}} Top 10</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                                    class="fa fa-minus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <input type="hidden" id="{{$key}}_row" value="{{$rows[$key]}}">
                    <input type="hidden" id="{{$key}}_date" value="{{$dates[$key]}}">
                    <div class="chart">
                        <canvas id="my{{$key}}Chart" width="1000" height="400"></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('script')
    <script>
        $.each(['table', 'circle'], function () {
            let set = [];
            $.each(JSON.parse($('#' + this + '_row').val()), function (index) {
                set.push({
                    label: this.item,
                    fill: false,
                    backgroundColor: '#F' + (9 - index) + index,
                    borderColor: '#F' + (9 - index) + index,
                    data: this._count.split(',')
                });
            });
            let data;
            data = {
                labels: JSON.parse($('#' + this + '_date').val()),
                datasets: set
            };
            new Chart($('#my' + this + 'Chart').get(0).getContext("2d"), {type: 'line', data: data});
        });
    </script>
@endsection