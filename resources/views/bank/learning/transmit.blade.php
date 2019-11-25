@extends('frame.body')
@section('title','百项过 - 传输')

@section('section')
    <div class="col-sm-6">
        <form action="{{URL::current()}}">
            <div class="form-group col-sm-6">
                <label for="type">类型</label>
                <select name="type" id="type" class="form-control">
                    @foreach(['bill'=>'题单','testbank'=>'大题'] as $_type => $label)
                        <option value="{{$_type}}" @if($type == $_type) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-sm-6">
                <label for="conn">连接</label>
                <select name="conn" id="conn" class="form-control">
                    @foreach(['online'=>'正式服','trail'=>'体验服','teach'=>'教研服','test'=>'测试用test','dev'=>'测试用dev'] as $_conn => $label)
                        <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-sm-12">
                <label for="ids">ID or IDs </label><i>(请使用逗号分隔 id)</i>
                <input type="text" class="form-control" name="ids" id="ids">
            </div>
            <div class="col-sm-12">
                <button type="button" class="btn btn-default" onclick="transmit()">Transmit</button>
            </div>
        </form>
    </div>
    <div class="col-sm-6 message-box">
    </div>


@endsection

@section('script')
    <script>
        $(document).ready(function () {

        });

        function transmit() {
            $(".message-box").html("");
            let form = $("form");
            let data = [];
            $.each(form.serializeArray(), function (k, v) {
                data[v.name] = v.value;
            });
            let ids = data.ids.replace(/，/g, ",").split(",");
            let _url = form.attr('action');
            $.each(ids, function (k, id) {
                let url = _url + '?type=' + data.type + '&conn=' + data.conn + '&id=' + id;
                $.get(url, function (result) {
                    let res = JSON.parse(result);
                    let cls = res.error === true ? 'text-red' : 'text-green';
                    $(".message-box").append('<h4 class="' + cls + '">' + res.msg + '</h4>');
                });
            });
        }
    </script>
@endsection