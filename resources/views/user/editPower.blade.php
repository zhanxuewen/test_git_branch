@extends('frame.body')
@section('title','Power Edit')

@section('section')
    <div class="col-sm-4">
        <form action="{{url('user/updatePower/'.$power->id)}}" method="POST">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="group">Group</label>
                <select name="group_id" id="group" class="form-control">
                    @foreach($groups as $group)
                        <option value="{{$group->id}}"
                                @if($group->id==$power->group_id) selected @endif>{{$group->label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="label">Label</label>
                <input type="text" name="label" class="form-control" id="label" value="{{$power->label}}">
            </div>
            <div class="form-group">
                <label for="code">Action</label>
                <input type="text" name="action" class="form-control" id="code" value="{{$power->action}}">
            </div>
            <div class="form-group">
                <label for="route">Route</label>
                <input type="text" class="form-control" id="route" value="{{$power->route}}" disabled="disabled">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="{{url('user/listPower')}}" class="btn btn-success pull-right">Back To List</a>
        </form>
    </div>
    <div class="col-sm-8 pull-right">
        <form action="{{url('user/updatePower/'.$power->id)}}" method="POST" id="deleteForm">
            {!! csrf_field() !!}
            <input type="hidden" name="delete" value="need_delete">
        </form>
        <button type="button" onclick="confDelete()" class="btn btn-danger pull-right">Delete</button>
    </div>
@endsection

@section('script')
    <script>
        function confDelete() {
            if (confirm("是否删除?")) {
                $("#deleteForm").submit();
            }
        }
    </script>
@endsection