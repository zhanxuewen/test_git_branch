@extends('frame.body')
@section('title','调度 - Map')

@section('section')
    <div class="col-sm-12">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="rail">Rails</label>
                <select name="rail" id="rail" class="form-control">
                    <option value="" @if($rail == '') selected @endif>全部</option>
                    @foreach($rails as $_rail)
                        <option value="{{$_rail->id}}"
                                @if($rail == $_rail->id) selected @endif>{{$_rail->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="object">Objects</label>
                <select name="object" id="object" class="form-control">
                    <option value="" @if($object == '') selected @endif>全部</option>
                    @foreach($objects as $_object)
                        <option value="{{$_object->id}}"
                                @if($object == $_object->id) selected @endif>{{$_object->code}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="conn">数据库连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online'=>'正式服','trail'=>'体验服','teach'=>'教研服','test'=>'测试用test','dev'=>'测试用dev'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-default">查询</button>
        </form>
        <br>
    </div>
    @if(!empty($object))
        @if(!empty($rail))
            @component('dispatch.maps.single', [
                            'objects' => $objects, 'object' => $object, 'rows' => $rows, 'outline' => $outline,
                            'fields' => $fields])
            @endcomponent
        @else
            @component('dispatch.maps.all', [
                            'flags' => $flags, 'objects' => $objects, 'object' => $object, 'raw' => $raw,
                            'rows' => $rows, 'maps' => $maps, 'outline' => $outline, 'ignore' => $ignore])
            @endcomponent
        @endif
    @endif
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $(".edit-map").on('click', function () {
                let id = $(this).attr('id');
                $('#item_id').val(id);
                $('#baseModal').modal()
            });
            $(".sync-out").on('click', function () {
                let id = $(this).attr('id');
                $('#item_id').val(id);
                $('#baseModal').modal()
            });
        });
    </script>
@endsection

@section('modal_body')
    @if(!empty($object) && empty($rail))
        <form action="{{url('dispatch/dispatcher/maps/update')}}" method="GET" style="width: 400px">
            <input type="hidden" name="conn" value="{{$conn}}">
            <input type="hidden" name="item_id" id="item_id">
            <div class="form-group">
                <label for="object">Object</label>
                <input type="text" name="object" id="object" class="form-control"
                       value="{{$objects[$object]->code}}" readonly>
            </div>
            <div class="form-group">
                <label for="method">Method</label>
                <select name="method" id="method" class="form-control">
                    <option value="append">Append</option>
                    <option value="remove">Remove</option>
                </select>
            </div>
            <div class="form-group">
                <label for="rail">Rail</label>
                <select name="rail" id="rail" class="form-control">
                    @foreach($flags as $flag)
                        <option value="{{$flag['id']}}">{{$flag['name']}}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Submit</button>
        </form>
    @endif
    @if(!empty($object) && !empty($rail))
        <form action="{{url('dispatch/dispatcher/sync/items')}}" method="GET" style="width: 400px">
            <input type="hidden" name="conn" value="{{$conn}}">
            <input type="hidden" name="rail" value="{{$rail}}">
            <input type="hidden" name="object" value="{{$object}}">
            <input type="hidden" name="item_id" id="item_id">
            <div class="form-group">
                <label for="object">Object</label>
                <input id="object" class="form-control"
                       value="{{$objects[$object]->code}}" readonly>
            </div>
            <div class="form-group">
                <label for="rail">Rail</label>
                <input id="rail" class="form-control"
                       value="{{$rails[$rail]->code}}" readonly>
            </div>
            <div class="form-group">
                <label for="method">Method</label>
                <select name="method" id="method" class="form-control">
                    <option value="insert">Insert</option>
                    <option value="update">Update</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">确认同步?</button>
        </form>
    @endif
@endsection