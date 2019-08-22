<form action="{{url('bank/learning/sync/entity')}}" method="GET" target="_blank">
    @component('bank.learning.search.display',
        ['h3' => '在线助教','testbank'=>$core_testbank,'extra'=>$core_extra,'field'=>'core_id',
        'entities'=>$core_entities,'item_value'=>'testbank_item_value'])
    @endcomponent
    @if(isset($learn_testbank) && !empty($learn_testbank))
        <div class="col-sm-12">
            <hr>
        </div>
        @component('bank.learning.search.display',
        ['h3' => '百项过题库','testbank'=>$learn_testbank,'extra'=>$learn_extra,'field'=>'learn_id',
        'entities'=>$learn_entities,'item_value'=>'testbank_item_value'])
        @endcomponent
        <div class="col-sm-12">
            <hr>
        </div>
        @if(count($ass_testbank_s) !=0)
            <div class="col-sm-12"><h3>百项过课程题</h3></div>
            @foreach($ass_testbank_s as $testbank)
                <div class="col-sm-6">
                    <p>Question</p>
                    <ul>
                        @foreach($testbank as $key => $value)
                            <li>
                                <b>{{$key}}: </b>
                                @if($key == 'content')
                                    @component('frame.layout.arrayToList', ['array' => json_decode($value,true)])
                                    @endcomponent
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
                                <div class="radio">
                                    <label><input type="radio" name="ass_id"
                                                  value="{{$entity->id}}">{{$entity->id}}</label>
                                </div>
                                <ul>
                                    @foreach(json_decode($entity->item_value,true) as $key => $item)
                                        <li>
                                            <b>{{$key}}
                                                : </b> {!! is_array($item) ? json_encode($item) : $item !!}
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
    <div class="col-sm-12">
        <button type="submit" class="btn btn-default">To Sync</button>
    </div>
</form>