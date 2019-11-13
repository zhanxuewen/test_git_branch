@extends('frame.body')
@section('title','Slow Rpc')

@section('section')
    <div class="col-sm-6 rpc_data">
        @foreach([1,3,7] as $day)
            <a class="btn btn-default @if($day == $_day) btn-primary active @endif"
               href="{!! URL::current().'?day='.$day.'&count='.$_count.'&sec='.$_sec !!}">{{$day}} day</a>
        @endforeach
        <h4>[ 数量阀值:{{$_count}} 时间阀值:{{$_sec}} ]</h4>
        <h4>{!! $start !!} - {!! date('Y-m-d H:i:s') !!}</h4>
        <table class="table table-bordered table-hover">
            <caption>次数</caption>
            @foreach($counts as $k=>$v)
                <tr>
                    <td>
                        <span class="label @if($v >= $_count) bg-red @else bg-gray @endif">{{$v}}</span>
                        <span>{{$k}}</span>
                        <button class="pull-right" onclick="show({{$k}})">Show</button>
                    </td>
                </tr>
            @endforeach
        </table>
        <table class="table table-bordered table-hover">
            <caption>时间</caption>
            @foreach($times as $k=>$v)
                <tr>
                    <td>
                        <span class="label @if($v >= $_sec) bg-red @else bg-gray @endif">{{$v}}s</span>
                        <span>{{$k}}</span>
                        <button class="pull-right" onclick="show({{$k}})">Show</button>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="col-sm-6">
        @foreach($methods as $method => $items)
            <div class="detail" id="{{$method}}" style="display: none">
                <h4>{{$method}}</h4>
                <div class="scroll" style="overflow:scroll">
                    <table class="table table-striped table-hover">
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <span class="label @if($item['time'] >= $_sec) bg-red @else bg-gray @endif">{{$item['time']}}s</span> {{$item['at']}}<br><br>
                                    @foreach($item['params'] as $param)
                                        <i class="fa fa-bookmark"></i> @if(is_array($param)) {!! json_encode($param) !!} @else {{$param}} @endif
                                        <br>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $(".scroll").height($(".rpc_data").height());
        });

        function show(key) {
            $(".detail").hide();
            $(key).show();
        }
    </script>
@endsection