<div class="col-sm-12">
    <hr>
</div>
<div class="col-sm-4">
    @php $info = $school['info']; $pop = $school['popular']; @endphp
    <img src="{{$info->logo}}" alt="Logo" width="40%">
    &nbsp;
    @php $attr = $info->school_attribute_list; @endphp
    <img src="{!! isset($attr->home_page_src) ? $attr->home_page_src : '' !!}" alt="Home Page" width="40%">
    <hr>
    <h3>{{$info->name}}
        <small>[ID:{{$info->id}}]</small>
    </h3>
    <dl class="dl-horizontal">
        <dt>区域:</dt>
        <dd>{{$info->region}}</dd>
        <dt>注册时间:</dt>
        <dd>{{$info->created_at}}</dd>
        <dt>校长姓名:</dt>
        <dd>{{$info->nickname}}</dd>
        <dt>校长手机号:</dt>
        <dd>{{$info->phone}}</dd>
        <dt>学校类型:</dt>
        <dd>{{$info->type}}</dd>
        <dt>市场专员:</dt>
        <dd>{{$info->marketer_name}}</dd>
        <dt>启动首页:</dt>
        <dd>@if($info->is_cover_available == 1) <span class="bg-green">启用</span> @else 未启用 @endif</dd>
        <dt>合作档:</dt>
        <dd>{{$pop->contract_class}}</dd>
        <dt>签约日期:</dt>
        <dd>{{$pop->sign_contract_date}}</dd>
        <dt>试用期:</dt>
        <dd>{{$pop->school_expired_at}}</dd>
    </dl>
</div>
<div class="col-sm-6">
    <table class="table table-bordered table-hover">
        <caption>
            @foreach($school['count'] as $count)
                <u><b>{{$count->type_name}}: </b>{{$count->coo}}</u>
            @endforeach
        </caption>
        <tr>
            <th>老师</th>
            <th>班级</th>
            <th>学生数</th>
        </tr>
        @foreach($school['teachers'] as $item)
            <tr>
                <td>{{$item->nickname}}</td>
                <td>{{$item->name}}</td>
                <td>{{$item->coo}}</td>
            </tr>
        @endforeach
    </table>
</div>