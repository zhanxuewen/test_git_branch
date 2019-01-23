<div class="col-sm-8">
    <nav aria-label="Page navigation">{!! $schools->appends($params)->links() !!}</nav>
    <table class="table table-bordered table-hover">
        <caption><b>合作校列表</b> [Total {{$schools->total()}}]</caption>
        <tr>
            <th>ID</th>
            <th>学校</th>
            <th>市场专员</th>
            <th>地区</th>
            <th>合作档</th>
        </tr>
        @foreach($schools as $school)
            <tr>
                <td>{{$school->id}}</td>
                <td><a href="{{URL::current().'?school_id='.$school->id}}">{{$school->name}}</a></td>
                <td>{{$school->nickname}}</td>
                <td>{{$school->region}}</td>
                <td>{{$school->class}}</td>
            </tr>
        @endforeach
    </table>
</div>