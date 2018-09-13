@extends('frame.body')
@section('title','Quit Student')

@section('section')
    <div class="col-sm-6">
        <form class="form-inline" action="{{url('quit_student')}}" method="get">
            <div class="form-group">
                <label>Student ID</label>
                <input class="form-control" type="number" name="student_id" value="{{$student_id}}" placeholder="Student ID" required="required">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>
        @if(isset($student))
            <hr>
            <p><b>{{$student[0]}}</b> [{{$student[1]}}]</p>
        @endif
        @if(isset($vanclass))
            <table class="table table-bordered table-hover">
                <caption>已退出班级</caption>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>学生人数</th>
                    <th>教师ID</th>
                    <th>教师名</th>
                    <th>学校ID</th>
                </tr>
                @foreach($vanclass as $item)
                    <tr>
                        <td>{{$item['id']}}</td>
                        <td>{{$item['name']}}</td>
                        <td>{{$item['student_count']}}</td>
                        <td>{{$item['teacher_id']}}</td>
                        <td>{{$item['nickname']}}</td>
                        <td>{{$item['school_id']}}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
@endsection