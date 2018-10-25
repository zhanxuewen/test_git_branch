@extends('frame.body')
@section('title','Create Role')

@section('section')
    <div class="col-sm-8">
        <form action="{{url('user/saveRole')}}" method="POST">
            {!! csrf_field() !!}
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" name="code" class="form-control" id="code">
                </div>
                <div class="form-group">
                    <label for="label">Label</label>
                    <input type="text" name="label" class="form-control" id="label">
                </div>
                <div class="form-group">
                    <label for="is_active">Is Active</label>
                    <select name="is_active" id="is_active" class="form-control">
                        <option value="1">Enable</option>
                        <option value="0">Disable</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
@endsection