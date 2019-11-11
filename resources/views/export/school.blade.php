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
                    </select>
                </div>
                <div class="form-group">
                    <label for="expire">是否附加有效期查询</label>
                    <select class="form-control" name="expire" id="expire">
                        <option value="0">否</option>
                        <option value="1">是</option>
                    </select>
                </div>
                <input class="btn btn-primary" type="submit" value="导出">
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-sm-6">
        <p><u>学校下订单</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>学校代交</u> 必填参数: [<b>学校 ID</b>]</p>
    </div>
@endsection