@extends('frame.body')
@section('title','Feedback')

@section('section')
    <div class="col-xs-12">
        <nav aria-label="Page navigation">{!! $feedback_s->render() !!}</nav>
        <table class="table table-bordered table-hover">
            <caption>用户反馈</caption>
            <tr>
                <th>用户ID</th>
                <th>昵称</th>
                <th>内容</th>
                <th>创建时间</th>
            </tr>
            @foreach($feedback_s as $item)
                <tr>
                    <td>{{$item->account_id}}</td>
                    <td>{{$item->nickname}}</td>
                    <td>{{$item->content}}</td>
                    <td>{{$item->created_at}}</td>
                </tr>
            @endforeach
        </table>
    </div>
@endsection