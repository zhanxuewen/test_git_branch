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
                    @php  $k = ($_group=='toke') ? $key : array_search($key, json_decode($keys, true)) @endphp
                    <input type="hidden" value="{{$item}}" id="row{{$k}}">
                    <div class="chart">
                        <canvas id="myChart{{$k}}" width="1000" height="400"></canvas>
                    </div>
                </div>
                <!-- /.box-body -->
            </div>
        @endforeach
    </div>
@endsection

@section('script')
    <script>
        let data;
        let group = '{{$_group}}';
        let keys = '{{$keys}}'.replace(new RegExp("&quot;", "g"), '"');
        $.each(JSON.parse(keys), function (_k) {
            let set = [];
            let k;
            k = group === 'token' ? this : _k;
            $.each(JSON.parse($('#row' + k).val()), function (index) {
                set.push({
                    label: this.label,
                    fill: false,
                    backgroundColor: '#F' + (9 - index) + index,
                    borderColor: '#F' + (9 - index) + index,
                    data: this.rows
                });
            });

            data = {
                labels: JSON.parse('{!! $times !!}'),
                datasets: set
            };
            new Chart($("#myChart" + k).get(0).getContext("2d"), {type: 'line', data: data});
        });
    </script>
@endsection