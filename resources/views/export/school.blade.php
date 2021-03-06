@extends('frame.body')
@section('title','Export')

@section('section')
    <div class="col-xs-12 col-sm-6">
        <form action="{!! url('export/school') !!}" method="post">
            <div class="col-xs-8 col-sm-6">
                <div class="form-group">
                    <label for="school_id">学校ID</label>
                    <input class="form-control" type="number" name="school_id" id="school_id" placeholder="School ID"/>
                </div>
                <div class="form-group">
                    <label for="start">开始时间</label>
                    <input class="form-control" type="date" name="start" id="start" placeholder="Start Date"/>
                </div>
                <div class="form-group">
                    <label for="end">结束时间</label>
                    <input class="form-control" type="date" name="end" id="end" placeholder="End Date"/>
                </div>
            </div>
            <div class="col-xs-8 col-sm-6">
                {!! csrf_field() !!}
                <div class="form-group">
                    <label for="field_phone">手机号格式</label>
                    <select class="form-control" name="field_phone" id="field_phone">
                        <option value="0">隐藏中位</option>
                        <option value="1">全部显示</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="query">导出项</label>
                    <select class="form-control" name="query" id="query">
                        <option value="school_order">学校下订单</option>
                        <option value="school_offline">学校代交</option>
                        <option value="school_student">学校学生</option>
                    </select>
                </div>
                @foreach(['expire_0'=> '有效期查询', 'teacher_1' => '老师名', 'register_0' => '注册时间'] as $key => $label)
                    <div class="form-group">
                        @php list($key, $def) = explode('_',$key)  @endphp
                        <label for="{{$key}}">是否附加{{$label}}</label>
                        <select class="form-control" name="{{$key}}" id="{{$key}}">
                            <option value="0" @if($def == 0) selected @endif>否</option>
                            <option value="1" @if($def == 1) selected @endif>是</option>
                        </select>
                    </div>
                @endforeach
                <input class="btn btn-primary" type="submit" value="导出">
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-sm-6">
        <p><u>学校下订单</u> 必填参数: [<b>学校 ID</b>] (附带退款项)</p>
        <p><u>学校代交</u> 必填参数: [<b>学校 ID</b>] (附带退款项)</p>
    </div>
@endsection