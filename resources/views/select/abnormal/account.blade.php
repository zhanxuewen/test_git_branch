<div class="col-sm-8">
    <table class="table table-bordered table-hover">
        <caption>异常教师</caption>
        <tr>
            <th>User ID</th>
            <th>自由教师ID</th>
            <th>自由教师昵称</th>
            <th>学校教师ID</th>
            <th>学校教师昵称</th>
        </tr>
        @foreach($collect['teacher'] as $teacher)
            <tr>
                <td>{{$teacher->user_id}}</td>
                <td>{{$teacher->a_id}}</td>
                <td>{{$teacher->a_nick}}</td>
                <td>{{$teacher->b_id}}</td>
                <td>{{$teacher->b_nick}}</td>
            </tr>
        @endforeach
    </table>
</div>
<div class="col-sm-8">
    <table class="table table-bordered table-hover">
        <caption>异常校管</caption>
        <tr>
            <th>User ID</th>
            <th>校长ID</th>
            <th>校长昵称</th>
            <th>校管ID</th>
            <th>校管昵称</th>
        </tr>
        @foreach($collect['school'] as $school)
            <tr>
                <td>{{$school->user_id}}</td>
                <td>{{$school->a_id}}</td>
                <td>{{$school->a_nick}}</td>
                <td>{{$school->b_id}}</td>
                <td>{{$school->b_nick}}</td>
            </tr>
        @endforeach
    </table>
</div>
<div class="col-sm-5">
    <table class="table table-bordered table-hover">
        <caption>重复身份账号</caption>
        <tr>
            <th>手机号</th>
            <th>数量</th>
            <th>身份</th>
        </tr>
        @foreach($collect['multi'] as $type => $accounts)
            @foreach($accounts as $account)
                <tr>
                    <td>{{$account->phone}}</td>
                    <td>{{$account->coo}}</td>
                    <td>{{$type}}</td>
                </tr>
            @endforeach
        @endforeach
    </table>
</div>