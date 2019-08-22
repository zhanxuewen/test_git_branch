@extends('frame.body')
@section('title','百项过 - 同步小题')

@section('section')
    <div class="col-sm-2">
        <form action="{{URL::current()}}" method="GET">
            <div class="form-group">
                <label for="core_id">在线助教 小题ID</label>
                <input type="number" class="form-control" name="core_id" id="core_id" value="{{$core_id}}">
            </div>
            <div class="form-group">
                <label for="learn_id">百项过题库 小题ID</label>
                <input type="number" class="form-control" name="learn_id" id="learn_id" value="{{$learn_id}}">
            </div>
            <div class="form-group">
                <label for="ass_id">百项过课程 小题ID</label>
                <input type="number" class="form-control" name="ass_id" id="ass_id" value="{{$ass_id}}">
            </div>
            <div class="form-group">
                <label for="conn">数据库连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online_learning'=>'正式服','online_trail_learning'=>'体验服'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="type">操作类型</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['search'=>'查看','update'=>'更新'] as $_type => $label)
                        <option value="{{$_type}}">{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">查看 || 更新</button>
        </form>
    </div>
    @if(!is_null($core_id))
        <div class="col-sm-10">
            @if(!is_null($core))
                @php $entity = json_decode($core->testbank_item_value, true) @endphp
                <b>{{$core->id}}</b>
                <ul>
                    @foreach($entity as $key => $value)
                        <li>
                            <b>{{$key}}: </b> {!! is_array($value) ? json_encode($value) : $value !!}
                        </li>
                    @endforeach
                </ul>
                @if(!is_null($learn))
                    <b>{{$learn->id}}</b>
                    <ul>
                        @foreach(json_decode($learn->testbank_item_value, true) as $key => $value)
                            <li>
                                <b>{{$key}}: </b> {!! is_array($value) ? json_encode($value) : $value !!}
                                @if(isset($entity[$key]) && $value != $entity[$key]) <i
                                        class="fa fa-exclamation-triangle text-red"></i> @endif
                            </li>
                        @endforeach
                    </ul>
                    @if(!is_null($ass))
                        <b>{{$ass->id}}</b>
                        <ul>
                            @foreach(json_decode($ass->item_value, true) as $key => $value)
                                <li>
                                    <b>{{$key}}: </b> {!! is_array($value) ? json_encode($value) : $value !!}
                                    @if(isset($entity[$key]) && $value != $entity[$key]) <i
                                            class="fa fa-exclamation-triangle text-red"></i> @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            @endif
        </div>
    @endif


@endsection