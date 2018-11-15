@extends('frame.body')
@section('title','Yellow Account')

@section('section')
    <div class="col-xs-12">
        <div class="col-xs-12">
            <form class="form-inline" action="{!! url('select/yellow_account') !!}" method="get">
                <div class="form-group">
                    <label for="channel_id">功能</label>
                    <select class="form-control" name="channel_id" id="channel_id">
                        <option value="0" @if($channel_id == 0) selected @endif>全部</option>
                        @foreach($channels as $channel)
                            <option value="{{$channel['id']}}"
                                    @if($channel['id']==$channel_id) selected @endif>{{$channel['name']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="field">查询域</label>
                    <select class="form-control" name="field" id="field">
                        @foreach(['phone'=>'手机号','user_account.id'=>'账号ID'] as $f => $label)
                            <option value="{{$f}}" @if($field == $f) selected @endif>{{$label}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="value">值</label>
                    <input class="form-control" type="number" name="value" value="{{$value}}" id="value"/>
                </div>
                <input class="btn btn-primary" type="submit" value="查询">
            </form>
        </div>
        <table class="table table-bordered table-hover">
            <caption>黄度用户</caption>
            <tr>
                <th>手机</th>
                <th>ID</th>
                <th>昵称</th>
                <th>身份</th>
                <th>功能</th>
                <th>创建时间</th>
            </tr>
            @foreach($accounts as $account)
                <tr>
                    <td>{{$account['phone']}}</td>
                    <td>{{$account['id']}}</td>
                    <td>{{$account['nickname']}}</td>
                    <td>{{$account['user_type_id']}}</td>
                    <td>{{$account['name']}}</td>
                    <td>{{$account['created_at']}}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection