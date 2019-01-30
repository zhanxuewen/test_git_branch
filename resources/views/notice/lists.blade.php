@extends('frame.body')
@section('title','Notice List')

@section('section')
    <div class="col-sm-12">
        @php $user_id = Auth::user()->id; @endphp
        @foreach($groups as $key => $group)
            <div class="col-sm-4">
                <div class="box box-primary direct-chat direct-chat-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{$users[$key]['username']}}</h3>
                        <div class="box-tools pull-right">
                            <span data-toggle="tooltip" class="badge bg-light-blue group-count">
                                {{\App\Helper\Helper::countNotRead($group, $user_id)}}</span>
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-minus"></i></button>
                            <button type="button" class="btn btn-box-tool" data-widget="remove">
                                <i class="fa fa-times"></i></button>
                        </div>
                    </div>
                    <div class="box-body">
                        <div class="direct-chat-messages">
                            @foreach($group as $item)
                                @php $is_auth = $item->sender_id == $user_id ? true : false; @endphp
                                <div class="direct-chat-msg @if($is_auth) right @endif">
                                    <div class="direct-chat-info clearfix">
                                        <span class="direct-chat-name @if($is_auth) pull-right @else pull-left @endif">
                                            {{$users[$item->sender_id]['username']}}</span>
                                        <span class="direct-chat-timestamp @if($is_auth) pull-left @else pull-right @endif">{{$item->created_at}}</span>
                                    </div>
                                    <img class="direct-chat-img" src="{{ $users[$item->sender_id]['avatar'] }}"
                                         alt="User Image">
                                    <span class="notice-id hidden">{{$item->id}}</span>
                                    <div class="direct-chat-text @if(!$is_auth && $item->has_read == 0) not-read @endif">
                                        {!! $item->content !!}
                                        <div></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="box-footer">
                        <form action="#" method="post">
                            <div class="input-group">
                                <input type="text" name="message" placeholder="Type Message ..." class="form-control">
                                <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary btn-flat">Send</button>
                      </span>
                            </div>
                        </form>
                    </div>
                    <!-- /.box-footer-->
                </div>
                <!--/.direct-chat -->
            </div>
        @endforeach
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $(".not-read").click(function () {
                let the = $(this);
                let id = the.prev('.notice-id').text();
                $.ajax({
                    type: "GET",
                    url: "/notice/ajax/hasRead",
                    data: "id=" + id,
                    async: false,
                    success: function () {
                        the.removeClass('not-read');
                        let count = the.closest('.box').find('.group-count');
                        count.html(count.html() - 1);
                        let c_div = $('.notice-count');
                        c_div.html(c_div.html() - 1);
                    }
                });
            });
        });
    </script>
@endsection