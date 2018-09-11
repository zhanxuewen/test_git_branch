@extends('frame.body')
@section('title','Marketer')

@section('section')
    <div class="col-sm-6">
        <table class="table table-bordered table-hover">
            <caption>市场专员</caption>
            <tr>
                <th>ID</th>
                <th>昵称</th>
                <th>手机号</th>
            </tr>
            @foreach($marketers as $marketer)
                <tr>
                    <td>{{$marketer['id']}}</td>
                    <td>{{$marketer['nickname']}}</td>
                    <td>{{$marketer['phone']}}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection