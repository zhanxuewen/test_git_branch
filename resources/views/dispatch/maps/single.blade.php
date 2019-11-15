<div class="col-sm-12">
    <table class="table table-bordered table-hover">
        <caption>{{ucfirst($objects[$object]->code)}}</caption>
        <tr>
            <th>Map</th>
            @foreach($fields as $key)
                <th>{{$key}}</th>
            @endforeach
        </tr>
        @foreach($rows as $row)
            <tr @if(!isset($outline[$row->id])) class="bg-yellow" @endif>
                <td>
                    <span class="sync-out" id="{{$row->id}}"><i class="fa fa-send"></i></span>
                </td>
                @foreach($fields as $key)
                    <td>
                        @if(isset($outline[$row->id]) && $outline[$row->id]->$key != $row->$key)
                            <span class="text-red">{{$row->$key}}</span>
                        @else
                            <span>{{$row->$key}}</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</div>