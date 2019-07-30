@extends('frame.body')
@section('title','Learning Wordbank - Transmit')

@section('section')
    <div class="col-sm-6">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="words">类型</label>
                <input type="text" name="words" class="form-control" id="words">
            </div>
            <div class="form-group">
                <label for="conn">连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['dev'=>'Dev','online'=>'Online'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">Transmit</button>
        </form>
    </div>
    <div class="col-sm-6">
        <h4>{{$message}}</h4>
    </div>


@endsection