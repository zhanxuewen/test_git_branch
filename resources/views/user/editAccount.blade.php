@extends('frame.body')
@section('title','Account Edit')

@section('section')
    <div class="col-sm-4">
        <form action="{{url('user/updateAccount/'.$account->id)}}" method="POST">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" class="form-control" id="username" value="{{$account->username}}">
            </div>
            <div class="form-group">
                <label for="role_id">Role</label>
                <select name="role_id" id="role_id" class="form-control">
                    @foreach($roles as $role)
                        <option value="{{$role->id}}"
                                @if(isset($account->role[0]) && $account->role[0]->id == $role->id) selected @endif>
                            {{$role->label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="{{url('user/listAccount')}}" class="btn btn-success pull-right">Back To List</a>
        </form>
    </div>
@endsection