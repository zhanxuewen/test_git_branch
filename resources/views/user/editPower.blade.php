@extends('frame.body')
@section('title','Power Edit')

@section('section')
    <div class="col-sm-4">
        <form action="{{url('user/updatePower/'.$power->id)}}" method="POST">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="group">Group</label>
                <select name="group" id="group" class="form-control">
                    @foreach(array_keys($groups) as $group)
                        <option value="{{$group}}" @if($group==$power->group) selected @endif>{{$group}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="group_label">Group Label</label>
                <select name="group_label" id="group_label" class="form-control">
                    @foreach($groups as $group)
                        <option value="{{$group}}" @if($group==$power->group_label) selected @endif>{{$group}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="label">Label</label>
                <input type="text" name="label" class="form-control" id="label" value="{{$power->label}}">
            </div>
            <div class="form-group">
                <label for="code">Code</label>
                <input type="text" name="code" class="form-control" id="code" value="{{$power->code}}">
            </div>
            <div class="form-group">
                <label for="route">Route</label>
                <input type="text" class="form-control" id="route" value="{{$power->route}}" disabled="disabled">
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="{{url('user/listPower')}}" class="btn btn-success pull-right">Back To List</a>
        </form>
    </div>
@endsection