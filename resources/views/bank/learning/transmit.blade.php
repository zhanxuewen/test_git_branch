@extends('frame.body')
@section('title','百项过 - 传输')

@section('section')
    <div class="col-sm-6">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="type">类型</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['bill'=>'题单','testbank'=>'大题'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="conn">连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online'=>'正式服','trail'=>'体验服','teach'=>'教研服'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="id">ID</label>
                <input type="number" class="form-control" name="id" id="id">
            </div>

            <button type="submit" class="btn btn-default">Transmit</button>
        </form>
    </div>
    <div class="col-sm-6">
        <h4>{{$message}}</h4>
    </div>


@endsection