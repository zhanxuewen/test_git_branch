@extends('frame.body')
@section('title','Role Edit')

@section('section')
    <div class="col-sm-4">
        <form action="{{url('user/updateRole/'.$role->id)}}" method="POST">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="code">Code</label>
                <input type="text" name="code" class="form-control" id="code" value="{{$role->code}}">
            </div>
            <div class="form-group">
                <label for="label">Label</label>
                <input type="text" name="label" class="form-control" id="label" value="{{$role->label}}">
            </div>
            <div class="form-group">
                <label for="is_active">Is Active</label>
                <select name="is_active" id="is_active" class="form-control">
                    @foreach([1=>'Enable', 0=>'Disable'] as $value => $label)
                        <option value="{{$value}}" @if($value==$role->is_active) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="{{url('user/listRole')}}" class="btn btn-success pull-right">Back To List</a>
        </form>
    </div>
@endsection