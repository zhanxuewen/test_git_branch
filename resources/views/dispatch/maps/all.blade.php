<div class="col-sm-12">
    <p>
        @foreach($flags as $flag)
            <span>{{$flag['name']}}:</span>
            <i class="fa fa-lg fa-circle" style="color: {{$flag['color']}}"></i>
        @endforeach
    </p>
    <table class="table table-bordered table-hover">
        <caption>{{ucfirst($objects[$object]->code)}}</caption>
        <tr>
            <th>Map</th>
            @if(empty($raw))
                @foreach($rows[0] as $key => $value)
                    <th>{{$key}}</th>
                @endforeach
            @else
                @foreach(explode(',', $raw) as $key)
                    <th>{{$key}}</th>
                @endforeach
            @endif
        </tr>
        @foreach($rows as $row)
            <tr>
                <td>
                    <span class="edit-map" id="{{$row->id}}"><i class="fa fa-edit"></i></span>
                    @if(isset($maps[$row->id]))
                        {!! \App\Helper\BladeHelper::dispatchMapShow($maps[$row->id]->rails, $flags, $outline[$row->id], $row, $ignore) !!}
                    @endif
                </td>
                @if(empty($raw))
                    @foreach($row as $key => $value)
                        <td>{{$value}}</td>
                    @endforeach
                @else
                    @foreach(explode(',', $raw) as $key)
                        <td>{{$row->$key}}</td>
                    @endforeach
                @endif
            </tr>
        @endforeach
    </table>
</div>