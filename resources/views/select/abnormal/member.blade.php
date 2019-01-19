<div class="col-sm-8">
    <table class="table table-bordered table-hover">
        <caption>Abnormal School Member</caption>
        <tr>
            <th>Account ID</th>
            <th>School ID</th>
            <th>Type ID</th>
            <th>Count</th>
        </tr>
        @foreach($collect['member'] as $member)
            <tr>
                <td>{{$member->account_id}}</td>
                <td>{{$member->school_id}}</td>
                <td>{{$member->type_id}}</td>
                <td>{{$member->coo}}</td>
            </tr>
        @endforeach
    </table>
</div>
<div class="col-sm-8">
    <table class="table table-bordered table-hover">
        <caption>Abnormal Vanclass Student</caption>
        <tr>
            <th>Student ID</th>
            <th>Vanclass ID</th>
            <th>Count</th>
        </tr>
        @foreach($collect['student'] as $student)
            <tr>
                <td>{{$student->student_id}}</td>
                <td>{{$student->vanclass_id}}</td>
                <td>{{$student->coo}}</td>
            </tr>
        @endforeach
    </table>
</div>