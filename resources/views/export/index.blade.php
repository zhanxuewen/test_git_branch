@extends('frame.body')
@section('title','Export')

@section('section')
    <div class="form_box">
        <form action="{!! url('export') !!}" method="post">
            {!! csrf_field() !!}
            <label for="field_phone">手机号格式: </label>
            <select name="field_phone" id="field_phone">
                <option value="0">隐藏中位</option>
                <option value="1">全部显示</option>
            </select>
            <br>
            <label for="query">导出项: </label>
            <select name="query" id="query">
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
            <br>
            <label for="expire">是否附加有效期查询</label>
            <select name="expire" id="expire">
                <option value="0">否</option>
                <option value="1">是</option>
            </select>
            <p>------------------------------------------------</p>
            <label for="school_id">学校ID</label>
            <input type="number" id="school_id" name="school_id"/>
            <br>
            <label for="student_id">学生ID</label>
            <input type="number" id="student_id" name="student_id"/>
            <br>
            <label for="teacher_id">教师ID</label>
            <input type="number" id="teacher_id" name="teacher_id"/>
            <br>
            <label for="marketer_id">市场专员ID</label>
            <input type="number" id="marketer_id" name="marketer_id"/>
            <br>
            <label for="label_ids">标签IDs</label>
            <input type="text" id="label_ids" name="label_ids"/>
            <br>
            <label for="label_id">单标签ID</label>
            <input type="number" id="label_id" name="label_id"/>
            <p>------------------------------------------------</p>
            <label for="start">开始时间</label>
            <input type="text" id="start" name="start"/>
            <br>
            <label for="end">结束时间</label>
            <input type="text" id="end" name="end"/>
            <p>------------------------------------------------</p>
            <input type="submit" value="导出">
        </form>
    </div>
    <div class="select_info">
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