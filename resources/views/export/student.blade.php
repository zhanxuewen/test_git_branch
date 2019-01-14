@extends('frame.body')
@section('title','Export')

@section('section')
    <div class="col-xs-12 col-sm-6">
        <form action="{!! url('export/student') !!}" method="post">
            <div class="col-xs-8 col-sm-6">
                <div class="form-group">
                    <label>学生ID</label>
                    <input class="form-control" type="number" name="student_id" placeholder="Student ID"/>
                </div>
                <div class="form-group">
                    <label>教师ID</label>
                    <input class="form-control" type="number" name="teacher_id" placeholder="Teacher ID"/>
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
                    <label for="query">导出项</label>
                    <select class="form-control" name="query" id="query">
                        <option value="student_fluency">学生练习单词</option>
                        <option value="fluency_record">学生练习单词详情</option>
                        <option value="student_vanclass_word">学生班级单词</option>
                        <option value="teacher_word_homework">教师布置单词</option>
                        <option value="get_labels">获取标签</option>
                        <option value="label_wordbank">标签下单词</option>
                        <option value="parent_label_wordbank">本标签下所有子标签的单词</option>
                    </select>
                </div>
                <input class="btn btn-primary" type="submit" value="导出">
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-sm-6">
        <p><u>学生练习单词</u> 必填参数: [<b>学生 ID</b>]</p>
        <p><u>学生练习单词详情</u> 必填参数: [<b>学生 ID</b>]</p>
        <p><u>学生班级单词</u> 必填参数: [<b>学生 ID</b>]</p>
        <p><u>教师布置单词</u> 必填参数: [<b>教师 ID</b>]</p>
        <p><u>获取标签</u> 必填参数: [<b>标签 IDs</b>] <i>(逗号隔开:1,2,3)</i></p>
        <p><u>本标签下所有子标签的单词</u> 必填参数: [<b>标签 ID</b>]</p>
    </div>
@endsection