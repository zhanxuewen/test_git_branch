<div class="col-sm-8">
    <table class="table table-bordered table-hover">
        <caption>Abnormal School Member</caption>
        <tr>
            <th>Account ID</th>
            <th>School ID</th>
            <th>Count</th>
        </tr>
        @foreach($collect['member'] as $member)
            <tr>
                <td>{{$member->account_id}}</td>
                <td>{{$member->school_id}}</td>
                <td>{{$member->coo}}</td>
            </tr>
        @endforeach
    </table>
</div>