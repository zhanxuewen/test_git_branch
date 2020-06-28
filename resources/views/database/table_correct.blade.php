@extends('frame.body')
@section('title','Table Correct')

@section('section')
    <div class="col-sm-12">
        <div class="col-sm-8">
            <p>
                <span class="text-green">绿色为 dev 新增</span> |
                <span class="text-red">红色为 dev 已删除</span>
                <br>红绿为字段变更 其中
                <span class="text-green">绿色为 dev</span>
                <span class="text-red">红色为 online</span> <br>
                <span class="text-blue">蓝条为 表增减</span> |
                <span class="text-orange">橙条为 字段增删或变更</span>
            </p>
            <table class="table table-bordered table-hover">
                @php $i = 0 @endphp
                @foreach($diff as $table => $columns)
                    @php $i++ @endphp
                    @if(is_array($columns))
                        <tr>
                            <td style="border-top: 4px solid orange">{{$i}}. {{$table}}</td>
                        </tr>
                        @foreach($columns as $column => $items)
                            @if(is_array($items))
                                <tr>
                                    <td class="bg-gray">
                                        &nbsp;&nbsp;&nbsp;<i class="fa fa-arrow-right"></i> {{$column}}</td>
                                </tr>
                                @foreach($items as $key => $value)
                                    <tr>
                                        <td class="bg-gray">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            <i class="fa fa-arrow-right"></i> {{$key}}
                                            <span class="text-green">{{$value[0]}}</span>
                                            <span class="text-red">{{$value[1]}}</span></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="@if($items == '+') text-green @else text-red @endif">
                                        &nbsp;&nbsp;&nbsp;<i class="fa fa-arrow-right"></i> {{$column}}</td>
                                </tr>
                            @endif
                        @endforeach
                    @else
                        <tr>
                            <td class="@if($columns == '+') text-green @else text-red @endif"
                                style="border-top: 4px solid blue">
                                {{$i}}. {{$table}}</td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {

        });
    </script>
@endsection