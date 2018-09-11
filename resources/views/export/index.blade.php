@extends('frame.body')
@section('title','Export')

@section('section')
    <div class="col-xs-12 col-sm-6">
        <form action="{!! url('export') !!}" method="post">
            <div class="col-xs-8 col-sm-6">
                <div class="form-group">
                    <label>学校ID</label>
                    <input class="form-control" type="number" name="school_id" placeholder="School ID"/>
                </div>
                <div class="form-group">
                    <label>学生ID</label>
                    <input class="form-control" type="number" name="student_id" placeholder="Student ID"/>
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
                    <label>标签IDs</label>
                    <input class="form-control" type="text" name="label_ids" placeholder="Label IDs"/>
                </div>
                <div class="form-group">
                    <label>单标签ID</label>
                    <input class="form-control" type="number" name="label_id" placeholder="Label ID"/>
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
                        <option value="no_pay_student">学校下未购买</option>
                        <option value="school_student">学校下学生</option>
                        <option value="teacher_student">教师下学生</option>
                        <option style="background-color: #fa85aa" value="student_fluency">学生练习单词</option>
                        <option style="background-color: #fa5e79" value="fluency_record">学生练习单词详情</option>
                        <option style="background-color: #fa6e5e" value="student_vanclass_word">学生班级单词</option>
                        <option style="background-color: #fa2d43" value="teacher_word_homework">教师布置单词</option>
                        <option style="background-color: #faa47c" value="get_labels">获取标签</option>
                        <option value="label_wordbank">标签下单词</option>
                        <option value="marketer_school">市场专员下学校教师</option>
                        <option value="marketer_order_sum">市场专员下订单汇总</option>
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
                    <label>开始时间</label>
                    <input class="form-control" type="date" name="start" placeholder="Start Date"/>
                </div>
                <div class="form-group">
                    <label>结束时间</label>
                    <input class="form-control" type="date" name="end" placeholder="End Date"/>
                </div>
                <input class="btn btn-primary" type="submit" value="导出">
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-sm-6">
        <p><u>学校下订单</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>学校下学生</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>教师下学生</u> 必填参数: [<b>教师 ID</b>]</p>
        <p><u>学生练习单词</u> 必填参数: [<b>学生 ID</b>]</p>
        <p><u>学生练习单词详情</u> 必填参数: [<b>学生 ID</b>]</p>
        <p><u>学生班级单词</u> 必填参数: [<b>学生 ID</b>]</p>
        <p><u>教师布置单词</u> 必填参数: [<b>教师 ID</b>]</p>
        <p><u>获取标签</u> 必填参数: [<b>标签 IDs</b>] <i>(逗号隔开:1,2,3)</i></p>
        <p><u>学校代交</u> 必填参数: [<b>学校 ID</b>]</p>
        <p><u>市场专员下学校教师</u> 必填参数: [<b>市场专员 ID</b>]</p>
        <p><u>市场专员下订单汇总</u> 必填参数: [<b>市场专员 ID</b>]</p>
    </div>
@endsection