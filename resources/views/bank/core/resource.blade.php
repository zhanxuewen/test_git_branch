@extends('frame.body')
@section('title','在线助教 Resource')

@section('section')
    <div class="col-sm-12">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="search">Resource Name</label>
                <input type="text" class="form-control" name="search" id="search" value="{{$search}}">
            </div>
            <div class="form-group">
                <label for="conn">Connection</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['test'=>'Test','online'=>'Online'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['search'=>'查询','sync'=>'同步Url'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">Search</button>
        </form>
    </div>
    <div class="col-sm-12">
        <hr>
    </div>
    @if(!empty($dev_res))
        <div class="col-sm-6">
            <b>Total: {{count($dev_res)}}</b>
            <ul>
                @foreach($dev_res as $name => $v)
                    @if(isset($online_res[$name]) && $v->url == $online_res[$name]->url)
                        @continue
                    @endif
                    <li>
                        @if(!isset($online_res[$name]))
                            <a href="{{URL::current().'?search='.$search.'&id='.$v->id}}"><u><b>{{$name}}</b></u></a>
                        @else
                            <span>{{$name}}</span>
                        @endif
                        <br>id <b>{{$v->id}}</b> type: <b>{{$v->type}}</b> module:
                        <b>{{$v->module}}</b> group: <b>{{$v->group}}</b> url:
                        <b>{{$v->url}}</b>
                        <br>created_at: <b>{{$v->created_at}} | {{$v->updated_at}}</b>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-sm-6">
            <b>Total: {{count($online_res)}}</b>
            <ul>
                @foreach($online_res as $name => $v)
                    @if(isset($dev_res[$name]) && $v->url == $dev_res[$name]->url)
                        @continue
                    @endif
                    <li>
                        <span>{{$name}}</span>
                        <br>id <b>{{$v->id}}</b> type: <b>{{$v->type}}</b> module:
                        <b>{{$v->module}}</b> group: <b>{{$v->group}}</b> url:
                        <b @if(!isset($dev_res[$name])) class="bg-orange"
                           @elseif($v->url != $dev_res[$name]->url) class="bg-purple" @endif>{{$v->url}}</b>
                        <br>created_at: <b>{{$v->created_at}} | {{$v->updated_at}}</b>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

@endsection