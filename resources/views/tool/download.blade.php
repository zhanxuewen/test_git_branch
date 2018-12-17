@extends('frame.body')
@section('title','Download')

@section('section')
    <div class="col-sm-6">
        <form action="{!! url('tool/download') !!}" method="POST">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="url">Website Url</label>
                <span>{ U can use simple url Or use an url with <code>{replace}</code> for multi like below. }</span>
                <input class="form-control" type="text" name="url"
                       value="http://media.vued.vanthink.cn/word/l/{replace}.mp3" id="url" required>
            </div>
            <div class="form-group">
                <label for="error">Error Message From Web</label>
                <span>{ If u can't understand, ignore this plz. }</span>
                <input class="form-control" type="text" name="error"
                       value='{"error":"Document not found"}' id="error">
            </div>
            <div class="form-group">
                <label for="separator">Separator</label> <span>{ <code>\r\n</code> for list, u can use other separator. }</span>
                <input class="form-control" type="text" name="separator" id="separator" value="\r\n" required>
            </div>
            <div class="form-group">
                <label for="text">Replace Items</label>
                <textarea class="form-control" id="text" name="text" rows="10"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-flat">Download</button>
        </form>
    </div>
    <div class="col-sm-6">
        @if(session('result'))
            <h4>Result</h4>
            @foreach(json_decode(session('result')) as $key => $type)
                @if($key=='success')
                    <div class="col-sm-4"><span class="text-green">Success: <b>{!! count($type) !!}</b></span></div>
                @endif
                @if($key=='fail')
                    <div class="col-sm-8"><span class="text-red">Fail: <b>{!! count($type) !!}</b></span></div>
                    <hr>
                    <div class="col-sm-12">
                        <b>Detail:</b>
                        <ul>
                            @foreach($type as $item)
                                <li>{{$item}}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
@endsection

@section('script')
    @if(session('file'))
        <script>
            $(document).ready(function () {
                let file = JSON.parse('{!! session('file') !!}');
                let arr = file.split('|');
                let token = $('meta[name="csrf-token"]').attr('content');
                let form = $("<form></form>").attr("action", "/ajax/tool/download").attr("method", "post");
                form.append($("<input />").attr("type", "hidden").attr("name", "_token").attr("value", token));
                form.append($("<input />").attr("type", "hidden").attr("name", "file").attr("value", arr[0]));
                form.append($("<input />").attr("type", "hidden").attr("name", "name").attr("value", arr[1]));
                form.appendTo('body').submit().remove();
            });
        </script>
    @endif
@endsection