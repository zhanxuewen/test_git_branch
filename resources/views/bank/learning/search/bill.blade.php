<div class="col-sm-6">
    <h3>在线助教</h3>
    <p>题单</p>
    <ul>
        @foreach($core_bill as $key => $value)
            <li>
                <b>{{$key}}: </b> {{$value}}
            </li>
        @endforeach
    </ul>
</div>
<div class="col-sm-6">
    <form action="{{URL::current()}}" method="GET" target="_blank">
        <input type="hidden" name="type" value="testbank">
        <input type="hidden" name="conn" value="{{$conn}}">
        <ul>
            @foreach($core_testbank_s as $testbank)
                <li>
                    <div class="radio">
                        <label><input type="radio" name="id" value="{{$testbank->id}}">{{$testbank->id}}</label>
                    </div>
                    <ul>
                        @foreach($testbank as $key => $item)
                            <li>
                                <b>{{$key}}: </b> {!! is_array($item) ? json_encode($item) : $item !!}
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
        <button type="submit" class="btn btn-default">查看大题</button>
    </form>
</div>
<div class="col-sm-12">
    <hr>
</div>
<div class="col-sm-6">
    <h3>百项过题库</h3>
    <p>题单</p>
    <ul>
        @foreach($learn_bill as $key => $value)
            <li>
                <b>{{$key}}: </b> {{$value}}
            </li>
        @endforeach
    </ul>
</div>
<div class="col-sm-6">
    <ul>
        @foreach($learn_testbank_s as $testbank)
            <li>
                <div class="radio">
                    <label>{{$testbank->id}}</label>
                </div>
                <ul>
                    @foreach($testbank as $key => $item)
                        <li>
                            <b>{{$key}}: </b> {!! is_array($item) ? json_encode($item) : $item !!}
                        </li>
                    @endforeach
                </ul>
            </li>
        @endforeach
    </ul>
</div>