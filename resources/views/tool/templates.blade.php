@extends('frame.body')
@section('title','Third Templates')

@section('section')
    <div class="col-xs-12">
        <div class="btn-group" role="group">
            @foreach(['message', 'voice'] as $_type)
                <a class="btn btn-default @if($_type == $type) btn-primary active @endif"
                   href="{!! URL::current().'?type='.$_type.'&project='.$project.'&conn='.$conn !!}">
                    {{ucfirst($_type)}}</a>
            @endforeach
        </div>
        <div class="btn-group" role="group">
            @foreach(['dev', 'online'] as $_conn)
                <a class="btn btn-default @if($_conn == $conn) btn-primary active @endif"
                   href="{!! URL::current().'?type='.$type.'&project='.$project.'&conn='.$_conn !!}">
                    {{ucfirst($_conn)}}</a>
            @endforeach
        </div>
        <br><br>
        <div class="btn-group" role="group">
            @foreach($projects as $_project)
                <a class="btn btn-default @if($_project == $project) btn-primary active @endif"
                   href="{!! URL::current().'?type='.$type.'&project='.$_project.'&conn='.$conn !!}">
                    {{ucfirst($_project)}}</a>
            @endforeach
        </div>
        <br><br>
    </div>
    <div class="col-xs-12">
        <table class="table table-bordered table-hover">
            <tr>
                <th>Code</th>
                <th>Template</th>
            </tr>
            @foreach($rows as $row)
                <tr>
                    <td>{{$row->code}}</td>
                    <td>{!! preg_replace('/\$\{(\w+)\}/', '<span class="text-orange">${$1}</span>',
 $row->template) !!}</td>
                </tr>
            @endforeach
        </table>
    </div>
    @if(\App\Helper\BladeHelper::checkSuper())
        <div class="col-xs-12">
            <form action="{!! url('tool/third/save/template') !!}" method="post">
                {!! csrf_field() !!}
                <input type="hidden" name="conn" value="{{$conn}}">
                <input type="hidden" name="url" value="{{Request::getRequestUri()}}">
                <div class="form-group col-xs-4">
                    <label for="type">类型</label>
                    <select name="type" id="type" class="form-control">
                        @foreach(['message', 'voice'] as $type)
                            <option value="{{$type}}">{{ucfirst($type)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-xs-4">
                    <label for="project">项目</label>
                    <select name="project" id="project" class="form-control">
                        @foreach($projects as $project)
                            <option value="{{$project}}">{{ucfirst($project)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-xs-4">
                    <label for="code">Code</label>
                    <input type="text" name="code" id="code" class="form-control">
                </div>
                <div class="form-group col-xs-12">
                    <label for="template">Template</label>
                    <input type="text" name="template" id="template" class="form-control">
                </div>
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    @endif

@endsection
