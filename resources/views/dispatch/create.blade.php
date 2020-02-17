@extends('frame.body')
@section('title','调度 - 新建')

@section('section')
    <div class="col-sm-8">
        <form action="{{url('dispatch/dispatcher/list/save')}}" method="POST">
            {!! csrf_field() !!}
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="type">类型</label>
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
                    <label for="code">Code</label>
                    <input type="text" name="code" class="form-control" id="code">
                </div>
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="Required When is [Rail]">
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
@endsection