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
                <ul class="menu" style="overflow: hidden; width: 100%; height: 200px;">
                    <li><!-- start message -->
                        <a href="#">
                            <div class="pull-left">
                                <img src="dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
                            </div>
                            <h4>
                                Support Team
                                <small><i class="fa fa-clock-o"></i> 5 mins</small>
                            </h4>
                            <p>Why not buy a new awesome theme?</p>
                        </a>
                    </li>
                    <!-- end message -->
                </ul>
            </div>
        </li>
        <li class="footer"><a href="#">See All Messages</a></li>
    </ul>
</li>

@section('header_script')
    <script>
        $.ajax({
            type: "GET",
            url: "/notice/ajax/check",
            async: false,
            data: "user_id=" + 1,
            success: function (data) {
                let notices = JSON.parse(data);
                let total = notices['total'];
                if (total > 0) {
                    $('.notice-count').html(total);
                    $('.notice-bar').removeClass('invisible').removeClass('Abc').addClass('visible');
                }
            }
        });
    </script>
@endsection