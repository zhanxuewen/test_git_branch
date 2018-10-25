@extends('frame.body')
@section('title','Power List')

@section('section')
    <div class="col-sm-8">
        <a class="btn btn-default" href="{{url('user/initRoute')}}">Init Route</a>
        <hr>
        <div class="box-group">
            <ul class="list-unstyled">
                @foreach($keys as $key=>$label)
                    <li><b>{{$label}}</b>
                        @if(isset($groups[$key]))
                            <ul class="list-unstyled list-inline">
                                @foreach($groups[$key] as $item)
                                    <li class="bg-gray">{{$item->label}}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        <hr>
        <table class="table table-bordered table-hover">
            <caption>Powers</caption>
            <tr>
                <th>分组</th>
                <th>名称</th>
                <th>路由</th>
                <th>操作</th>
            </tr>
            @foreach($powers as $power)
                <tr>
                    <td>{{$power->group_label}}</td>
                    <td>{{$power->label}}</td>
                    <td>{{$power->route}}</td>
                    <td><a href="{{url('user/editPower/'.$power->id)}}">编辑</a></td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection