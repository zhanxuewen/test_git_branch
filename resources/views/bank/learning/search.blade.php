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
                    @foreach(['online'=>'正式服','trail'=>'体验服','teach'=>'教研服','test'=>'测试用test','dev'=>'测试用dev'] as $_conn => $label)
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
                            'ass_testbank_s'=>$ass_testbank_s,'ass_entities'=>$ass_entities,'conn'=>$conn])
            @endcomponent
        @endif
        @if($type=='bill')
            @if(!isset($learn_bill))
                <div class="col-sm-12"><span class="bg-red">百项过题库，对应题单不存在</span></div>
            @else
                @component('bank.learning.search.bill', [
                                'core_bill'=>$core_bill,'core_testbank_s'=>$core_testbank_s,'conn'=>$conn,
                                'learn_bill'=>$learn_bill,'learn_testbank_s'=>$learn_testbank_s])
                @endcomponent
            @endif
        @endif
    @endif
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $("span.btn-info").on('click', function () {
                $("input#ques_id").val($(this).attr('id'));
                $("#sync_article_form").submit();
            })
        });
    </script>
@endsection