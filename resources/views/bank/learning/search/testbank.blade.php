<form action="{{url('bank/learning/sync/entity')}}" method="GET" target="_blank">
    <input type="hidden" name="conn" value="{{$conn}}">
    @component('bank.learning.search.display',
        ['h3' => '在线助教','testbank'=>$core_testbank,'extra'=>$core_extra,'field'=>'core_id',
        'entities'=>$core_entities,'item_value'=>'testbank_item_value','conn'=>$conn])
    @endcomponent
    @if(isset($learn_testbank) && !empty($learn_testbank))
        <div class="col-sm-12">
            <hr>
        </div>
        @component('bank.learning.search.display',
        ['h3' => '百项过题库','testbank'=>$learn_testbank,'extra'=>$learn_extra,'field'=>'learn_id',
        'entities'=>$learn_entities,'item_value'=>'testbank_item_value','conn'=>$conn])
        @endcomponent
        <div class="col-sm-12">
            <hr>
        </div>
        @if(count($ass_testbank_s) !=0)
            <div class="col-sm-12"><h3>百项过课程题</h3></div>
            @foreach($ass_testbank_s as $testbank)
                <div class="col-sm-6">
                    <p>Question <span class="btn btn-info" id="{{$testbank->id}}">查看|修改题干</span></p>
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
                                    <a class="text-red" href="{{url('bank/learning/appendOrRemove/entity').
                        '?conn='.$conn.'&type=remove&entity_id=a_'.$entity->id}}">x删除小题x</a>
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
                <div class="col-sm-12"></div>
            @endforeach
        @endif
    @endif
    <div class="col-sm-12">
        <button type="submit" class="btn btn-default">To Sync</button>
    </div>
</form>
<form action="{{url('bank/learning/sync/article')}}" method="GET" class="form-inline" target="_blank" id="sync_article_form">
    <input type="hidden" name="conn" value="{{$conn}}">
    <input type="hidden" name="core_id" value="{{isset($core_extra->id)?$core_extra->id:null}}">
    <input type="hidden" name="ques_id" value="" id="ques_id">
    @if(isset($learn_testbank) && !empty($learn_testbank))
        <input type="hidden" name="learn_id" value="{{isset($learn_extra->id)?$learn_extra->id:null}}">
    @endif
{{--    @if(count($ass_testbank_s) !=0)--}}
{{--        <div class="form-group">--}}
{{--            <label for="ques_id">百项过课程 大题ID</label>--}}
{{--            <input type="number" class="form-control" name="ques_id" id="ques_id" value="{{$ass_testbank_s[0]->id}}">--}}
{{--        </div>--}}
{{--    @endif--}}
{{--    <button type="submit" class="btn btn-default">查看|修改题干</button>--}}
</form>