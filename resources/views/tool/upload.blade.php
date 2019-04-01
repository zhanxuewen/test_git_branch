@extends('frame.body')
@section('title','Upload')

@section('section')
    <div class="col-sm-6">
        <form id="form" method="POST" class="form-inline" enctype="multipart/form-data">
            <div class="form-group">
                <label for="type">File Type</label>
                <select name="type" id="type" class="form-control">
                    <option value="image">Image</option>
                    {{--<option value="audio">Audio</option>--}}
                    {{--<option value="apk">Apk</option>--}}
                </select>
            </div>
            <div class="form-group">
                <label for="file">File</label>
                <input type="file" name="file" id="file" class="form-control">
            </div>
            <button id="upload" type="button" class="btn btn-primary btn-flat">Upload</button>
        </form>
        <div class="col-sm-12 no-padding">
            <hr>
            <p class="show-url"></p>
        </div>
    </div>
    <div class="col-sm-6">
        <img class="upload-img" src="" alt="">
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $("#upload").click(function () {
                let type = $('#type').val();
                let file = document.getElementById("file");
                let formData = new FormData();
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
                            $('.show-url').html('<b>Url: </b><u>' + data + '</u>');
                        } else {
                            $('.show-url').html('<span class="text-red"><b>Error</b>: ' + data + '</span>');
                        }
                    }
                });
            });
        });
        $('#file').change(function () {
            let _URL = window.URL || window.webkitURL;
            let file, img;
            if ((file = this.files[0])) {
                img = new Image();
                img.onload = function () {
                    $('.upload-img').attr('src', this.src);
                };
                img.src = _URL.createObjectURL(file);
            }
        });
    </script>
@endsection