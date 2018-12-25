@extends('frame.body')
@section('title','Export')

@section('section')
    <div class="col-xs-12 col-sm-6">
        <form id="export_single">
            {!! csrf_field() !!}
            <div class="col-xs-8 col-sm-6">
                <div class="form-group">
                    <label>学校IDs</label>
                    <input class="form-control" type="text" name="school_ids" placeholder="School IDs"/>
                </div>
            </div>
            <div class="col-xs-8 col-sm-6">
                <div class="form-group">
                    <label for="query">查询项</label>
                    <select class="form-control" name="query" id="query">
                        <option value="school_year_card_student_count">学校年卡学生数量</option>
                        <option value="school_half_card_student_count">学校半年卡学生数量</option>
                    </select>
                </div>
                <input class="btn btn-primary" type="button" id="submit" value="查询">
            </div>
        </form>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="display_board"></div>
    </div>
@endsection

@section('script')
    <script>
        $("#submit").click(function () {
            let board = $(".display_board");
            board.html("System Searching...<br/>");
            let form_data = $("#export_single").serialize();
            $.ajax({
                type: "POST",
                url: "/export/ajax/single",
                async: true,
                data: form_data,
                success: function (data) {
                    board.append("<br/>Result : <b>" + data + "</b>");
                }
            });
        })
    </script>
@endsection