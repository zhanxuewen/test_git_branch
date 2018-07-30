<!DOCTYPE html>
<html>
<head>
    <title>Export</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">

    <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            display: table;
            font-weight: 100;
        }

        div {
            margin: 20px;
        }


    </style>
</head>
<body>
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
            <option value="school_student">学校下学生</option>
            <option value="teacher_student">教师下学生</option>
            <option style="background-color: #fa85aa" value="student_fluency">学生练习单词</option>
            <option style="background-color: #fa5e79" value="fluency_record">学生练习单词详情</option>
            <option style="background-color: #fa2d43" value="teacher_word_homework">教师布置单词</option>
            <option style="background-color: #faa47c" value="get_labels">获取标签</option>
            <option value="school_offline">学校代交</option>
            <option value="marketer_school">市场专员下学校教师</option>
        </select>
        <br>
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
        <input type="submit" value="导出">
    </form>
</div>
<div class="select_info">
    <p><u>学校下订单</u> 必填参数: [<b>学校 ID</b>]</p>
    <p><u>学校下学生</u> 必填参数: [<b>学校 ID</b>]</p>
    <p><u>教师下学生</u> 必填参数: [<b>教师 ID</b>]</p>
    <p><u>学生练习单词</u> 必填参数: [<b>学生 ID</b>]</p>
    <p><u>学生练习单词详情</u> 必填参数: [<b>学生 ID</b>]</p>
    <p><u>教师布置单词</u> 必填参数: [<b>教师 ID</b>]</p>
    <p><u>获取标签</u> 必填参数: [<b>标签 IDs</b>] <i>(逗号隔开:1,2,3)</i> </p>
    <p><u>学校代交</u> 必填参数: [<b>学校 ID</b>]</p>
    <p><u>市场专员下学校教师</u> 必填参数: [<b>市场专员 ID</b>]</p>
</div>
</body>
</html>