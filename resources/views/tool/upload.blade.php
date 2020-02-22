@extends('frame.body')
@section('title','Upload')

@section('section')
    <div class="col-sm-4">
        <form id="form" method="POST" class="form-horizontal" enctype="multipart/form-data">
            <div class="form-group">
                <label class="control-label col-sm-4" for="env">Env</label>
                <div class="col-sm-8">
                    <select name="env" id="env" class="form-control">
                        <option value="dev">Dev</option>
                        <option value="online">Online</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-4" for="type">File Type</label>
                <div class="col-sm-8">
                    <select name="type" id="type" class="form-control">
                        <option value="image">Image</option>
                        <option value="audio">Audio</option>
{{--                        <option value="video">Video</option>--}}
                        <option value="apk">Apk</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-sm-4" for="file">File</label>
                <div class="col-sm-8">
                    <input type="file" name="file" id="file" class="form-control">
                </div>
            </div>
            <button id="upload" type="button" class="btn btn-primary btn-flat">Upload</button>
        </form>
    </div>
    <div class="col-sm-5 pull-right">
        <img class="upload-img img-responsive" src="" alt="">
    </div>
    <div class="col-sm-12 no-padding">
        <hr>
        <p class="ajax-label"></p>
        <a target="_blank" class="show-url" href=""></a>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $("#upload").click(function () {
                let env = $('#env').val();
                let type = $('#type').val();
                let file = document.getElementById("file");
                let formData = new FormData();
                formData.append('env', env);
                formData.append('type', type);
                formData.append('file', file.files[0]);
                $.ajax({
                    type: "POST",
                    url: "/ajax/tool/upload",
                    processData: false,
                    contentType: false,
                    data: formData,
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    success: function (data) {
                        if (data.indexOf('http') >= 0) {
                            $('.ajax-label').html('<b>Url: </b>');
                            $('.show-url').text(data).attr('href', data);
                        } else {
                            $('.ajax-label').html('<span class="text-red"><b>Error</b>: ' + data + '</span>');
                        }
                    }
                });
            });
        });
    </script>
@endsection