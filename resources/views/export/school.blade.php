@extends('frame.body')
@section('title','Export')

@section('section')
    <div class="col-xs-12 col-sm-6">
        <form action="{!! url('export/school') !!}" method="post">
            <div class="col-xs-8 col-sm-6">
                <div class="form-group">
                    <label>学校ID</label>
                    <input class="form-control" type="number" name="school_id" placeholder="School ID"/>
                </div>
                <div class="form-group">
                    <label>学校IDs</label>
                    <input class="form-control" type="text" name="school_ids" placeholder="School IDs"/>
                </div>
                <div class="form-group">
                    <label>班级ID</label>
                    <input class="form-control" type="number" name="vanclass_id" placeholder="Vanclass ID"/>
                </div>
                <div class="form-group">
                    <label>教师ID</label>
                    <input class="form-control" type="number" name="teacher_id" placeholder="Teacher ID"/>
                </div>
                <div class="form-group">
                    <label>市场专员ID</label>
                    <input class="form-control" type="number" name="marketer_id" placeholder="Marketer ID"/>
                </div>
                <div class="form-group">
                    <label>开始时间</label>
                    <input class="form-control" type="date" name="start" placeholder="Start Date"/>
                </div>
                <div class="form-group">
                    <label>结束时间</label>
                    <input class="form-control" type="date" name="end" placeholder="End Date"/>
                </div>
            </div>
            <div class="col-xs-8 col-sm-6">
                {!! csrf_field() !!}
                <div class="form-group">
                    <label for="database">数据库选择</label>
                    <select class="form-control" name="database" id="database">
                        <option value="0">主数据库</option>
                        <option value="1">词霸天库</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="field_phone">手机号格式</label>
                    <select class="form-control" name="field_phone" id="field_phone">
                        <option value="0">隐藏中位</option>
                        <option value="1">全部显示</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="hide_school_id">隐藏学校ID</label>
                    <select class="form-control" name="hide_school_id" id="hide_school_id">
                        <option value="1">隐藏</option>
                        <option value="0">显示</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="query">导出项</label>
                    <select class="form-control" name="query" id="query">
                        <option value="school_order">学校下订单</option>
                        <option value="school_offline">学校代交</option>
                        <option value="multi_school_order">多所学校订单</option>
                        <option value="multi_school_offline">多所学校代交</option>
                        <option value="no_pay_student">学校下未购买</option>
                        <option value="school_student">学校下学生</option>
                        <option value="teacher_student">教师下学生</option>
                        <option value="schools_teacher">多学校下教师信息</option>
                        <option value="schools_principal">多学校下校长信息</option>
                        <option value="marketer_school">市场专员下学校教师</option>
                        <option value="marketer_order_sum">市场专员下订单汇总</option>
                        <option value="word_pk_activity">词霸天活跃统计</option>
                        <option value="principal_last_login">学校管理者登录统计</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="expire">是否附加有效期查询</label>
                    <select class="form-control" name="expire" id="expire">
                        <option value="0">否</option>
                        <option value="1">是</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="compare">有效期比较</label>
                    <select class="form-control" name="compare" id="compare">
                        <option value="no">否</option>
                        <option value="lte">有效</option>
                        <option value="gt">失效</option>
                    </select>
                </div>
                <input class="btn btn-primary" type="submit" value="导出">
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-sm-6">
        <p><u>学校下订单</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>学校下学生</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>教师下学生</u> 必填参数: [<b>教师 ID</b>]</p>
        <p><u>学校代交</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>市场专员下学校教师</u> 必填参数: [<b>市场专员 ID</b>]</p>
        <p><u>市场专员下订单汇总</u> 必填参数: [<b>市场专员 ID</b>]</p>
        <p><u>词霸天活跃统计</u> 必填参数: [<b>数据库选择</b>]</p>
    </div>
@endsection