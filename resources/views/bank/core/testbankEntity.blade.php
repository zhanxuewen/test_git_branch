@extends('frame.body')
@section('title','在线助教 修改小题')

@section('section')
    <style>
        .entity-box:hover {
            cursor: pointer;
            background-color: #eeeeee;
        }

        .checked {
            color: #1163c4;
            font-weight: bold;
        }
    </style>
    <div class="col-sm-12">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="conn">Connection</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['dev'=>'Dev','online'=>'线上'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="id">大题ID</label>
                <input type="text" class="form-control" name="id" id="id" value="{{$id}}">
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['search'=>'查询','sync'=>'更新'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">Do it!</button>
            @if(!empty($id))
                <br><br>
                <input type="hidden" id="entity_id" name="entity_id" value="">
                <div class="form-group">
                    <label for="search">错误内容</label>
                    <input type="text" class="form-control" name="search" id="search" value="">
                </div>
                <div class="form-group">
                    <label for="replace">替换内容</label>
                    <input type="text" class="form-control" name="replace" id="replace" value="">
                </div>
                <div class="form-group">
                    <label for="field">题干/小题</label>
                    <select name="field" id="field" class="form-control">
                        <option value="0">小题</option>
                        <option value="1">题干</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quote">附加引号</label>
                    <select name="quote" id="quote" class="form-control">
                        <option value="0">否</option>
                        <option value="1">是</option>
                    </select>
                </div>
            @endif
        </form>
    </div>
    <div class="col-sm-12">
        <hr>
    </div>
    @if(!empty($id))
        <div class="col-sm-12">
            <ul>
                @foreach($entities as $entity)
                    <li class="entity-box" id="{{$entity['id']}}">
                        {{json_encode(json_decode($entity['testbank_item_value']),JSON_UNESCAPED_UNICODE)}}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $(".entity-box").click(function () {
                $(".entity-box").removeClass('checked')
                $(this).addClass('checked');
                $("#entity_id").val($(this).attr('id'));
            });
        });
    </script>
@endsection