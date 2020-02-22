@extends('frame.body')
@section('title','百项过退卡查询')

@section('section')
    <style>
        .num-checked {
            background-color: #E9B6F7;
        }

        .c-box {
            display: block;
            width: 15px;
            height: 15px;
            border-radius: 5px;
            border: 1px solid gray;
        }

        .c-box-checked {
            background-color: #E9B6F7;
        }
    </style>
    <div class="col-sm-12">
        <form class="form" action="{{URL::current()}}" method="get">
            <div class="form-group">
                <label for="phones">学生手机号</label>
                <input class="form-control" type="text" name="phones" id="phones" value="{{$phones}}"/>
            </div>
            <input class="btn btn-primary" type="submit" value="查询">
        </form>
    </div>
    <div class="col-sm-12">
        <hr>
    </div>
    <div class="col-sm-12">
        <button class="btn btn-primary" id="getNums">获取卡号</button>
        <h5 id="nums"></h5>
    </div>
    @if(!is_null($phones))
        <div class="col-sm-12">
            <table class="table table-bordered table-hover">
                <caption>学生卡详情</caption>
                <tr>
                    <th>#</th>
                    <th>手机号</th>
                    <th>昵称</th>
                    <th>卡号</th>
                    <th>卡ID</th>
                    <th>激活时间</th>
                    <th>开卡名称</th>
                    <th>课程名称</th>
                </tr>
                @foreach($rows as $row)
                    <tr class="{{$row->card_number}}">
                        <td><i class="c-box"></i></td>
                        <td>{{$row->phone}}</td>
                        <td>{{$row->nickname}}</td>
                        <td class="num">{{$row->card_number}}</td>
                        <td>{{$row->id}}</td>
                        <td>{{$row->activated_at}}</td>
                        <td>{{$row->name}}</td>
                        <td>{{$row->book}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $("td").on('click', function () {
                let parent = $(this).parent();
                parent.find('.num').toggleClass('num-checked');
                parent.find('.c-box').toggleClass('c-box-checked');
            });
            $('#getNums').on('click', function () {
                let nums = Array();
                $(".c-box-checked").each(function () {
                    let id = $(this).parent().parent().attr('class');
                    if (!nums.includes(id))
                        nums.push(id);
                });
                $('#nums').text(nums.join(','));
                $.each(nums, function (i, v) {
                    let tr = $("." + v);
                    tr.find('.num').addClass('num-checked');
                    tr.find('.c-box').addClass("c-box-checked");
                })
            })
        })
    </script>
@endsection