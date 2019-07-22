@extends('frame.body')
@section('title','Learning - Search')

@section('section')
    <div class="col-sm-2">
        <form action="{{URL::current()}}" method="GET">
            <div class="form-group">
                <label for="core_id">Core Entity ID</label>
                <input type="number" class="form-control" name="core_id" id="core_id" value="{{$core_id}}">
            </div>
            <div class="form-group">
                <label for="learn_id">Learning Entity ID</label>
                <input type="number" class="form-control" name="learn_id" id="learn_id" value="{{$learn_id}}">
            </div>
            <div class="form-group">
                <label for="ass_id">Assessment Entity ID</label>
                <input type="number" class="form-control" name="ass_id" id="ass_id" value="{{$ass_id}}">
            </div>
            <div class="form-group">
                <label for="conn">Core Testbank ID</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online_learning'=>'正式服','online_trail_learning'=>'体验服'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="type">Replace Type</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['str'=>'字符串','key_value'=>'键值'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" class="form-control" name="search" id="search" value="{{$search}}">
            </div>
            <div class="form-group">
                <label for="replace">Replace</label>
                <input type="text" class="form-control" name="replace" id="replace" value="">
            </div>
            <button type="submit" class="btn btn-default">Check || Search And Replace</button>
        </form>
    </div>
    @if(!is_null($core_id))
        <div class="col-sm-10">
            @if(!is_null($core))
                @php $entity = json_decode($core->testbank_item_value, true) @endphp
                <b>{{$core->id}}</b>
                <ul>
                    @foreach($entity as $key => $value)
                        <li>
                            <b>{{$key}}: </b> {!! is_array($value) ? json_encode($value) : $value !!}
                        </li>
                    @endforeach
                </ul>
                @if(!is_null($learn))
                    <b>{{$learn->id}}</b>
                    <ul>
                        @foreach(json_decode($learn->testbank_item_value, true) as $key => $value)
                            <li>
                                <b>{{$key}}: </b> {!! is_array($value) ? json_encode($value) : $value !!}
                                @if(isset($entity[$key]) && $value != $entity[$key]) <i
                                        class="fa fa-exclamation-triangle text-red"></i> @endif
                            </li>
                        @endforeach
                    </ul>
                    @if(!is_null($ass))
                        <b>{{$ass->id}}</b>
                        <ul>
                            @foreach(json_decode($ass->item_value, true) as $key => $value)
                                <li>
                                    <b>{{$key}}: </b> {!! is_array($value) ? json_encode($value) : $value !!}
                                    @if(isset($entity[$key]) && $value != $entity[$key]) <i
                                            class="fa fa-exclamation-triangle text-red"></i> @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            @endif
        </div>
    @endif


@endsection