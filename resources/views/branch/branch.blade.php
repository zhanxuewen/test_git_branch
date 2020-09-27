@extends('frame.body')
@section('title','分支设置')

@section('section')
    <div class="col-sm-12">
        <form action="{{url('branch')}}" method="POST" class="form-inline">
            {!! csrf_field() !!}
            <div class="form-group">
                <label for="group">前后端</label>
                <select name="group" id="group" class="form-control">
                    <option value="">全部</option>
                    @foreach($groups as $key=>$one_group)
                        <option value="{{$key}}">{{$one_group}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="project">项目</label>
                <select name="project" id="project" class="form-control">
                    <option value="">全部</option>
                    @foreach($projects as $key=>$one_project)
                        <option value="{{$key}}">{{$one_project}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="project">url地址</label>
                <input type="text" name="url" id="url" class="form-control">
            </div>
            <div class="form-group">
                <label for="project">分支</label>
                <input type="text" name="branch" id="branch" class="form-control">
            </div>
            <div class="form-group">
                <label for="project">描述</label>
                <input type="text" name="label" id="label" class="form-control">
            </div>
            <button type="submit" class="btn btn-default">新增</button>
        </form>
    </div>

    <div class="col-sm-12"  style="margin-top: 40px">
        <form action="{{URL::current()}}" method="GET" class="form-inline">
            <div class="form-group">
                <label for="group">前后端</label>
                <select name="group" id="group" class="form-control">
                    <option value="">全部</option>
                    @foreach($groups as $key=>$one_group)
                        <option value="{{$key}}"
                                @if($group == $key) selected @endif>{{$one_group}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="project">项目</label>
                <select name="project" id="project" class="form-control">
                    <option value="">全部</option>
                    @foreach($projects as $key=>$one_project)
                        <option value="{{$key}}"
                                @if($key == $project) selected @endif>{{$one_project}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="is_available">是否生效</label>
                <select name="is_available" id="is_available" class="form-control">
                    <option  value="1" @if($is_available == '1') selected @endif >生效</option>
                    <option value="2" @if($is_available == '2') selected @endif >全部</option>
                </select>
            </div>
            <button type="submit" class="btn btn-default">Search</button>
        </form>
    </div>

    <div class="col-sm-12">
        <table class="table table-bordered table-hover">
            <tr>
                <th>前后端</th>
                <th>项目</th>
                <th>url地址</th>
                <th>分支</th>
                <th>是否生效</th>
                <th>描述</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            @foreach($branch_sets as $one_set)
                <tr>
                    <td>{{$groups[$one_set->group]}}</td>
                    <td>{{$projects[$one_set->project]}}</td>
                    <td>{{$one_set->url}}</td>
                    <td>{{$one_set->branch}}</td>
                    <td>{{$one_set->is_available=='1'?'生效':'无效'}}</td>
                    <td>{{$one_set->label}}</td>
                    <td>{{$one_set->created_at}}</td>
                    <td>
                        @if($one_set->is_available=='1')
                        <a href="removeBranch?id={{$one_set->id}}&project={{$one_set->project}}&group={{$one_set->group}}" onClick="return confirm('确定删除?');">删除</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
        <nav aria-label="Page navigation">{!! $branch_sets->render() !!}</nav>
    </div>
@endsection