<div class="col-sm-6">
    <h3>{{$h3}}</h3>
    <p>大题</p>
    <ul>
        @foreach($testbank as $key => $value)
            <li>
                <b>{{$key}}: </b> {{$value}}
            </li>
        @endforeach
    </ul>
</div>
<div class="col-sm-6">
    @if(!is_null($extra))
        <p>题目</p>
        <ul>
            @foreach($extra as $key => $value)
                <li>
                    <b>{{$key}}: </b> {{$value}}
                </li>
            @endforeach
        </ul>
    @endif
    <ul>
        @foreach($entities as $entity)
            <li>
                <div class="radio">
                    <label><input type="radio" name="{{$field}}" value="{{$entity->id}}">{{$entity->id}}</label>
                    @if($field == 'core_id')
                        <a class="text-orange" href="{{url('bank/learning/appendOrRemove/entity').
                        '?conn='.$conn.'&type=append&entity_id='.$entity->id}}">+增加小题+</a>
                    @endif
                    @if($field == 'learn_id')
                        <a class="text-red" href="{{url('bank/learning/appendOrRemove/entity').
                        '?conn='.$conn.'&type=remove&entity_id=l_'.$entity->id}}">x删除小题x</a>
                    @endif
                </div>
                <ul>
                    @foreach(json_decode($entity->$item_value,true) as $key => $item)
                        <li>
                            <b>{{$key}}: </b> {!! is_array($item) ? json_encode($item) : $item !!}
                        </li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>
</div>