@extends('frame.body')
@section('title','百项过 - 删除题单')

@section('section')
    <div class="col-sm-6">
        <form action="{{URL::current()}}">
            <div class="form-group col-sm-3">
                <label for="conn">连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online'=>'正式服','trail'=>'体验服','teach'=>'教研服','test'=>'测试用test','dev'=>'测试用dev'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-sm-3">
                <label for="with_testbank">是否删除大题</label>
                <select name="with_testbank" id="with_testbank" class="form-control">
                    <option value="1">是</option>
                    <option value="0">否</option>
                </select>
            </div>
            <div class="form-group col-sm-12">
                <label for="ids">ID or IDs </label><i>(请使用逗号分隔 id)</i>
                <input type="text" class="form-control" name="ids" id="ids">
            </div>
            <div class="col-sm-12">
                <button type="submit" class="btn btn-default">Delete</button>
            </div>
        </form>
    </div>
    <div class="col-sm-6 message-box">
        {!! $info !!}
    </div>

@endsection