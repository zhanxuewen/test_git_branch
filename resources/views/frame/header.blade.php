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
            @if(session('message'))
                <li class="user user-menu bg-red">
                    <a class="s_error">{{session('message')}}</a>
                </li>
            @endif
            <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="{{asset('asset/image/user-3.jpg')}}" class="user-image" alt="User Image">
                    <span class="hidden-xs">{{ Auth::user()->username }}</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="user-footer">
                        <div class="pull-left">
                            <span><b>Greeting {{ Auth::user()->username }} !</b></span>
                        </div>
                        <div class="pull-right">
                            <a href="{{url('logout')}}" class="btn btn-default btn-flat">Sign out</a>
                        </div>
                    </li>
                </ul>
            </li>
            <!-- Control Sidebar Toggle Button -->
            <li>
                <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
            </li>
        </ul>
    </div>
</nav>