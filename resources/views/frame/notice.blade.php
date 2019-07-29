<li class="user user-menu bg-yellow">
    <a><b>公告: </b>黄度用户查询 (<u>Select - Yellow Account</u>) 将被遗弃.</a>
</li>
@if(session('message'))
    <li class="user user-menu bg-red">
        <a>{{session('message')}}</a>
    </li>
@endif
@if(session('success'))
    <li class="user user-menu bg-green">
        <a>{{session('success')}}</a>
    </li>
@endif
<li class="dropdown messages-menu">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        <i class="fa fa-bell-o fa-lg"></i><span class="label label-success notice-count"></span>
    </a>
    <ul class="dropdown-menu">
        <li class="header">You have <b class="notice-count"></b> messages</li>
        <li class="notice-bar invisible">
            <div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 200px;">
                <ul class="menu" id="notice-list" style="overflow: hidden; width: 100%; height: 200px;"></ul>
            </div>
        </li>
        <li class="footer"><a href="{{url('notice/lists')}}">See All Messages</a></li>
    </ul>
</li>

@section('header_script')
    <script>
        // getNotice();
        // window.setInterval(function () {
        //     getNotice();
        // }, 30 * 1000);

        function getNotice() {
            $.ajax({
                type: "GET",
                url: "/notice/ajax/check",
                async: false,
                success: function (data) {
                    let notices = JSON.parse(data);
                    let total = notices['total'];
                    $('.notice-count').html(total);
                    if (total > 0) {
                        $('.notice-bar').removeClass('invisible').addClass('visible');
                        let notice = '';
                        $.each(notices.data, function (index, item) {
                            let url = item.is_system === 1 ? '/asset/image/system.png' :
                                (item.sender.avatar === '' ? '/asset/image/default.png' : item.sender.avatar);
                            let img = '<div class="pull-left"><img src="' + url + '" class="img-circle" alt="User Image"></div>';
                            let sender = item.is_system === 1 ? 'The System' : item.sender.username;
                            notice += '<li><a>' + img + '<h4>' + sender + '</h4><p>' + item.content + '</p></a></li>';
                        });
                        $('#notice-list').html(notice);
                    }
                }
            });
        }
    </script>
@endsection