@extends('frame.body')
@section('title','DB Wiki')

<style>
    .form-span {
        display: inline-block;
        padding: 3px 5px;
        border: 1px dashed #acb0b3;
        border-radius: 5px;
        color: #b199ff;
        font-weight: bold;
    }

    .form-span:hover {
        cursor: default;
    }

    .form-span .fa-close {
        color: #75787a;
    }

    .form-span .fa-close:hover {
        cursor: pointer;
        color: #282828;
    }

    ul {
        list-style: none;
        padding-left: 0;
    }

    #edit-box {
        position: fixed;
        width: 300px;
        background-color: #ffffff;
        border: 1px solid #333333;
        padding: 5px 10px;
        display: none;
    }

    #edit-box form {
        margin: 0;
    }

    #box-close:hover {
        cursor: pointer;
    }
</style>

@section('section')
    <div class="col-sm-12">
        <div class="col-sm-12">
            <form id="the_form" action="{{ URL::current() }}" class="form-inline" method="get">
                <input type="hidden" name="project_id" value="{{$project_id}}">
                @if(!is_null($project_id))
                    <input type="hidden" name="module_id" value="{{$module_id}}">
                @endif
                @if(!is_null($module_id))
                    <input type="hidden" name="table_id" value="{{$table_id}}">
                @endif
                @if(!is_null($project_id))
                    <span class="form-span" id="project_span">{{$projects[$project_id]->code}} <i
                                class="fa fa-close"></i></span>
                @endif
                @if(!is_null($module_id))
                    <span class="form-span" id="module_span">{{$modules[$module_id]->code}} <i
                                class="fa fa-close"></i></span>
                @endif
                @if(!is_null($table_id))
                    <span class="form-span" id="table_span">{{$tables[$table_id]->code}} <i
                                class="fa fa-close"></i></span>
                @endif
            </form>
        </div>
        <div class="col-sm-12">
            <hr>
        </div>
        @component('database.component.ul', [
            'objects' => $projects, 'key' => 'project', 'field' => 'code', 'hide' => !is_null($project_id)])
        @endcomponent
        @if(!is_null($project_id))
            @component('database.component.ul', [
                'objects' => $modules, 'key' => 'module', 'field' => 'code', 'hide' => !is_null($module_id)])
            @endcomponent
        @endif
        @if(!is_null($module_id))
            @component('database.component.ul', [
                'objects' => $tables, 'key' => 'table', 'field' => 'code', 'hide' => !is_null($table_id)])
            @endcomponent
        @endif
        @if(!is_null($table_id))
            @component('database.component.ul', [
                'objects' => $columns, 'key' => 'column', 'field' => 'column', 'hide' => false])
            @endcomponent
        @endif
        <div id="edit-box">
            <i class="fa-close fa pull-right" id="box-close"></i>
            <form action="{{URL::current()}}" method="POST">
                {!! csrf_field() !!}
                <input type="hidden" name="item_id">
                <input type="hidden" name="item_type">
                <input type="hidden" name="project_id">
                <input type="hidden" name="module_id">
                <input type="hidden" name="table_id">
                <div class="form-group">
                    <label for="info">注解</label>
                    <input type="text" class="form-control col-sm-10" name="info" id="info">
                </div>
                <br>
                <button type="submit" class="btn btn-default pull-right">更新</button>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $(".show_li").on('click', function () {
                select($(this));
            });
            $(".form-span .fa-close").on('click', function () {
                let type = getType($(this));
                let func = 'remove' + type.replace(/^\S/, s => s.toUpperCase());
                eval(func + "()");
            });
            $('.show_li').on('contextmenu', function (e) {
                fillEditForm($(this));
                let _x = e.clientX, _y = e.clientY;
                let box = $('#edit-box');
                box.css({top: _y + "px", left: _x + "px"});
                box.show();
                return false;
            });
            $('#box-close').on('click',function () {
                $("#edit-box").hide();
            })
        });

        function select(_this) {
            let _name = _this.attr('class').replace('_li show_li', '_id');
            if (_name === 'column_id') return;
            $("input[name='" + _name + "']").val(_this.parent('li').attr('id'));
            $("#the_form").submit();
        }

        function getType(_this) {
            return _this.parent('span').attr('id').replace('_span', '');
        }

        function removeTable() {
            _hideUl(['column']);
            _hideSpan(['table']);
            _removeInput(['table']);
            _showUl(['table']);
        }

        function removeModule() {
            _hideUl(['column', 'table']);
            _hideSpan(['table', 'module']);
            _removeInput(['table', 'module']);
            _showUl(['module']);
        }

        function removeProject() {
            _hideUl(['column', 'table', 'module']);
            _hideSpan(['table', 'module', 'project']);
            _removeInput(['table', 'module', 'project']);
            _showUl(['project']);
        }

        function _hideSpan(_type) {
            $.each(_type, function () {
                $("#" + this + "_span").hide();
            });
        }

        function _showUl(_type) {
            $.each(_type, function () {
                $("#" + this + "_list").removeClass('hide');
            });
        }

        function _hideUl(_type) {
            $.each(_type, function () {
                $("#" + this + "_list").addClass('hide');
            });
        }

        function _removeInput(_type) {
            $.each(_type, function () {
                $("input[name='" + this + "_id']").val('');
            });
        }

        function fillEditForm(_this) {
            let li = _this.parent('li');
            $("#edit-box input[name='item_id']").val(li.attr('id'));
            let type = li.parent('ul').parent('div').attr('id');
            $("#edit-box input[name='item_type']").val(type.replace('_list', ''));
            $("#edit-box input[name='project_id']").val($("#the_form input[name='project_id']").val());
            $("#edit-box input[name='module_id']").val($("#the_form input[name='module_id']").val());
            $("#edit-box input[name='table_id']").val($("#the_form input[name='table_id']").val());
            $("#edit-box #info").val(_this.next('i').text());
        }

    </script>
@endsection