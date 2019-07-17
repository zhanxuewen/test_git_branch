<ul>
    @foreach($array as $k => $item)
        <li>
            <b>{{$k}}: </b>
            @if(is_array($item))
                @component('frame.layout.arrayToList', ['array' => $item]) @endcomponent
            @else
                {{$item}}
            @endif
        </li>
    @endforeach
</ul>