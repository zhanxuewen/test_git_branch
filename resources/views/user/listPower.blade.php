@extends('frame.body')
@section('title','Power List')

{{--Modal Part--}}
@section('modal_title', 'Dispatch Route to All Roles')
@section('modal_size', 'modal-sm')
@section('modal_body')
    <form id="dispatch-form" action="{{url('user/dispatchRoute')}}" method="post">
        {!! csrf_field() !!}
        <input type="hidden" id="dispatch-id" name="power_id" value="">
        <h5>Confirm Route:</h5>
        <span id="dispatch-route"></span>
    </form>
@endsection
@section('modal_submit')
    <button class="btn btn-primary" type="submit" onclick="$('#dispatch-form').submit()">Submit</button>
@endsection


@section('section')
    <div class="col-sm-8">
        <a class="btn btn-default" href="{{url('user/initRoute')}}">Init Route</a>
        <hr>
        <div class="box-group">
            <ul class="list-unstyled">
                @foreach($labels as $label)
                    <li><b>{{$label->name}}</b>
                        <ul class="list-unstyled list-inline">
                            @foreach($groups[$label->id] as $item)
                                <li class="bg-gray">{{$item->label}}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </div>
        <hr>

        <table class="table table-bordered table-hover">
            <caption><b>Powers</b> [Count: {{count($powers)}}] {<u class="text-red">Roles: {{$role_count}}</u>}
            </caption>
            <tr>
                <th>分组</th>
                <th>名称</th>
                <th>路由</th>
                <th>Roles</th>
                <th>操作</th>
            </tr>
            @foreach($powers as $power)
                <tr>

                    <td>{{isset($power->groupLabel) ? $power->groupLabel->name : ''}}</td>
                    <td>{{$power->label}}</td>
                    <td>{{$power->route}}</td>
                    @php $coo = isset($rolePowers[$power->id])? $rolePowers[$power->id]->coo : 0 @endphp
                    <td>@if($coo < $role_count)
                            <button type="button" class="btn btn-default btn-sm" data-toggle="modal"
                                    data-target="#baseModal"
                                    onclick="dispatchRoute({{json_encode($power->toArray())}})">[ {{$coo}} ]
                                -> {{$role_count}}</button> @endif
                    </td>
                    <td><a href="{{url('user/editPower/'.$power->id)}}"><i class="fa fa-edit"></i></a></td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection

@section('script')
    <script>
        function dispatchRoute(power) {
            $('#dispatch-route').html(power['route']);
            $('#dispatch-id').val(power['id']);
        }
    </script>
@endsection