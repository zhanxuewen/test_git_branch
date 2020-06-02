@extends('frame.body')
@section('title','个人中心')

@section('section')
    @if(Auth::user()->role[0]->code == 'guest')
        <blockquote>
            <p>请联系 [<b>LuminEe</b>] 来更改你的角色.</p>
        </blockquote>
    @endif
    <div class="col-sm-4">
        <div class="col-sm-12">
            <form action="{{url('auth/edit')}}" method="post">
                {!! csrf_field() !!}
                <input type="hidden" name="type" value="password">
                <div class="form-group">
                    <label for="old_password">旧密码</label>
                    <input type="password" name="old_password" class="form-control" id="old_password">
                </div>
                <div class="form-group">
                    <label for="new_password">新密码</label>
                    <input type="password" name="new_password" class="form-control" id="new_password">
                </div>
                <div class="form-group">
                    <label for="new_password_check">再次输入新密码</label>
                    <input type="password" name="new_password_check" class="form-control" id="new_password_check">
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
            </form>
        </div>
        <div class="col-sm-12">
            <hr>
        </div>
        <div class="col-sm-12">
            <form action="{{url('auth/edit')}}" method="post">
                {!! csrf_field() !!}
                <input type="hidden" name="type" value="info">
                <div class="form-group">
                    <label for="avatar">头像</label>
                    <input type="text" name="avatar" class="form-control" id="avatar" value="{{Auth::user()->avatar}}">
                </div>
                <div class="form-group">
                    <label for="nickname">昵称</label>
                    <input type="text" name="nickname" class="form-control" id="nickname"
                           value="{{Auth::user()->nickname}}" required>
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
            </form>
        </div>
        <div class="col-sm-12">
            <hr>
        </div>
        <div class="col-sm-12">
            <a href="{{url('flushCache')}}" class="btn btn-warning">清除缓存</a>
        </div>
    </div>

    <div class="col-sm-8">
        <h4>2020.06.02 更新</h4>
        <p><b>*</b> 停用以下 schedule : [监控移动设备] [监控订单]</p>
    </div>
@endsection