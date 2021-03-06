<!-- Logo -->
<a href="{{url('/')}}" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><b>J</b>o</span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><b>Jolyne</b></span>
</a>
<!-- Header Navbar: style can be found in header.less -->
<nav class="navbar navbar-static-top">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
    </a>
    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
            @include('frame.notice')
            <li class="dropdown user user-menu">
                @php $user = \App\Helper\BladeHelper::getUserInfo(); @endphp
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="{!! empty($user['avatar']) ? asset('asset/image/default.png') : $user['avatar'] !!}"
                         class="user-image bg-gray" alt="User Image">
                    <span class="hidden-xs">[{!! $user['role']['name'] ?? '' !!}]
                        <b>{!! $user['nickname'] ?? $user['username'] !!}</b></span>
                </a>
            </li>
            <li class="user user-menu"><a href="{{route('logout')}}"><i class="fa fa-sign-out"></i></a></li>
            <!-- Control Sidebar Toggle Button -->
            {{--<li>--}}
            {{--<a href="#" data-toggle="control-sidebar"><i class="fas fa-gears"></i></a>--}}
            {{--</li>--}}
        </ul>
    </div>
</nav>