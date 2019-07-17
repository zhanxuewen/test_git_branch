@extends('frame.body')
@section('title','Learning - Search')

@section('section')
    <div class="col-sm-12">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="id">Core Testbank ID</label>
                <input type="number" class="form-control" name="id" id="id" value="{{$id}}">
            </div>
            <div class="form-group">
                <label for="conn">Connection</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online_learning'=>'正式服','online_trail_learning'=>'体验服'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">Search</button>
        </form>
    </div>
    @if(!is_null($id))
        <div class="col-sm-6">
            <h3>在线助教</h3>
            <p>Testbank</p>
            <ul>
                @foreach($core_testbank as $key => $value)
                    <li>
                        <b>{{$key}}: </b> {{$value}}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-sm-6">
            @if(!is_null($core_extra))
                <p>Extra</p>
                <ul>
                    @foreach($core_extra as $key => $value)
                        <li>
                            <b>{{$key}}: </b> {{$value}}
                        </li>
                    @endforeach
                </ul>
            @endif
            <ul>
                @foreach($core_entities as $entity)
                    <li>
                        {{$entity->id}}
                        <ul>
                            @foreach(json_decode($entity->testbank_item_value,true) as $key => $item)
                                <li>
                                    <b>{{$key}}: </b> {!! is_array($item) ? json_encode($item) : $item !!}
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-sm-12">
            <hr>
        </div>
        @if(isset($learn_testbank) && !empty($learn_testbank))
            <div class="col-sm-6">
                <h3>百项过题库</h3>
                <p>Testbank</p>
                <ul>
                    @foreach($learn_testbank as $key => $value)
                        <li>
                            <b>{{$key}}: </b> {{$value}}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-sm-6">
                @if(!is_null($learn_extra))
                    <p>Extra</p>
                    <ul>
                        @foreach($learn_extra as $key => $value)
                            <li>
                                <b>{{$key}}: </b> {{$value}}
                            </li>
                        @endforeach
                    </ul>
                @endif
                <ul>
                    @foreach($learn_entities as $entity)
                        <li>
                            {{$entity->id}}
                            <ul>
                                @foreach(json_decode($entity->testbank_item_value,true) as $key => $item)
                                    <li>
                                        <b>{{$key}}: </b> {{$item}}
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-sm-12">
                <hr>
            </div>
            @if(!empty($ass_testbank_s))
                <h3>百项过课程题</h3>
                @foreach($ass_testbank_s as $testbank)
                    <div class="col-sm-6">
                        <p>Question</p>
                        <ul>
                            @foreach($testbank as $key => $value)
                                <li>
                                    <b>{{$key}}: </b>
                                    @if($key == 'content')
                                        <ul>
                                            @foreach(json_decode($value,true) as $k => $item)
                                                <li>
                                                    <b>{{$k}}: </b> {{json_encode($item)}}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {{$value}}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <ul>
                            @foreach($ass_entities[$testbank->id] as $entity)
                                <li>
                                    {{$entity->id}}
                                    <ul>
                                        @foreach(json_decode($entity->item_value,true) as $key => $item)
                                            <li>
                                                <b>{{$key}}: </b> {{$item}}
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        @endif
    @endif


@endsection