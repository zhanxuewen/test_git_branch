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
                @php $auth_user = Auth::user(); @endphp
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="{!! empty($auth_user->avatar) ? asset('asset/image/default.png') : $auth_user->avatar !!}"
                         class="user-image bg-gray" alt="User Image">
                    <span class="hidden-xs">[{{ $auth_user->role[0]->label }}] <b>{{ $auth_user->username }}</b></span>
                </a>
                <ul class="dropdown-menu">
                    <li class="user-footer">
                        <div class="pull-left">
                            <span><b>Greeting {{ $auth_user->username }} !</b></span>
                        </div>
                        <div class="pull-right">
                            <a href="{{route('logout')}}" class="btn btn-default btn-flat">Sign out</a>
                        </div>
                    </li>
                </ul>
            </li>
            <!-- Control Sidebar Toggle Button -->
            {{--<li>--}}
            {{--<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>--}}
            {{--</li>--}}
        </ul>
    </div>
</nav>