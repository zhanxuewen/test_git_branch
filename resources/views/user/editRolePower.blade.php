@extends('frame.body')
@section('title','RolePower Edit')

@section('section')
    <h3>{ {{$role->label}} }</h3>
    <form action="{{url('user/updateRolePower/'.$role->id)}}" method="POST">
        {!! csrf_field() !!}
        <div class="col-sm-10">
            @foreach($keys as $key=>$label)
                <h4>{{$label}}</h4>
                @if(isset($groups[$key]))
                    <ul class="list-unstyled list-inline">
                        @foreach($groups[$key] as $item)
                            <li class="margin-r-5">
                                <div class="form-group-sm">
                                    <input type="checkbox" name="power_id[]" id="power_{{$item->id}}"
                                           value="{{$item->id}}" @if(in_array($item->id,$ids)) checked @endif>
                                    <label @if(in_array($item->id,$ids)) class="bg-green" @endif for="power_{{$item->id}}" title="{{$item->code}}">
                                        {{$item->label}}</label>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        </div>
        <div class="col-sm-4">
            <hr>
            <button type="submit" class="btn btn-primary">Submit</button>
            <a href="{{url('user/listRole')}}" class="btn btn-success pull-right">Back To List</a>
        </div>
    </form>
@endsection