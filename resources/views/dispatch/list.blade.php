@extends('frame.body')
@section('title','调度 - 列表')

@section('section')
    <div class="col-sm-12">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="type">表类型</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['rail'=>'Rail','object'=>'Object'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="conn">数据库连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online'=>'正式服','trail'=>'体验服','teach'=>'教研服','test'=>'测试用test','dev'=>'测试用dev'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="action">操作</label>
                <select name="action" id="action" class="form-control">
                    @foreach(['search' => '查询', 'create' => '新建'] as $_action => $label)
                        <option value="{{$_action}}">{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">操作</button>
        </form>
    </div>
    <div class="col-sm-6">
        <table class="table table-bordered table-hover">
            <caption>{{ucfirst($type)}}</caption>
            <tr>
                <th>ID</th>
                <th>Code</th>
                @if($type == 'rail')
                    <th>Name</th> @endif
                <th>时间</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td>{{$row->id}}</td>
                    <td>{{$row->code}}</td>
                    @if($type == 'rail')
                        <td>{{$row->name}}</td> @endif
                    <td>{{$row->created_at}}</td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="col-sm-6">
        <form action="{{URL::current()}}" method="GET"></form>
    </div>
@endsection