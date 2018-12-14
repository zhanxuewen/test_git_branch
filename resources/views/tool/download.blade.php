@extends('frame.body')
@section('title','Download')

@section('section')
    <div class="col-sm-6">
        <form action="{!! url('tool/download') !!}" method="POST">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="url">网址</label> <span>{ <u><i>example : http://media.vued.vanthink.cn/word/l/{replace}.mp3</i></u> }</span>
                <input class="form-control" type="text" name="url"
                       value="http://media.vued.vanthink.cn/word/l/{replace}.mp3" id="url" required>
            </div>
            <div class="form-group">
                <label for="error">文件错误信息</label> <span>{ <i>如不懂，请忽略</i> }</span>
                <input class="form-control" type="text" name="error"
                       value='{"error":"Document not found"}' id="error">
            </div>
            <div class="form-group">
                <label for="text">文件</label>
                <textarea class="form-control" id="text" name="text" rows="10"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-flat">Download</button>
        </form>
    </div>
@endsection