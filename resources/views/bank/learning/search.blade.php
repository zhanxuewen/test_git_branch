@extends('frame.body')
@section('title','百项过 - 查看')

@section('section')
    <div class="col-sm-12">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="id">在线助教 ID</label>
                <input type="number" class="form-control" name="id" id="id" value="{{$id}}">
            </div>
            <div class="form-group">
                <label for="type">类型</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['testbank'=>'大题','bill'=>'题单'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="conn">数据库连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online_learning'=>'正式服','online_trail_learning'=>'体验服'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">查询</button>
        </form>
    </div>
    @if(!is_null($id))
        @if($type=='testbank')
            @component('bank.learning.search.testbank', [
                            'core_testbank'=>$core_testbank,'core_extra'=>$core_extra,'core_entities'=>$core_entities,
                            'learn_testbank'=>$learn_testbank,'learn_extra'=>$learn_extra,'learn_entities'=>$learn_entities,
                            'ass_testbank_s'=>$ass_testbank_s,'ass_entities'=>$ass_entities])
            @endcomponent
        @endif
        @if($type=='bill')
            @component('bank.learning.search.bill', [
                            'core_bill'=>$core_bill,'core_testbank_s'=>$core_testbank_s,'conn'=>$conn,
                            'learn_bill'=>$learn_bill,'learn_testbank_s'=>$learn_testbank_s])
            @endcomponent
        @endif
    @endif
@endsection