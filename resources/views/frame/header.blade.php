<!-- Logo -->
<a href="{{url('/')}}" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><b>J</b>ol</span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><b>Jolyne</b></span>
</a>
<!-- Header Navbar: style can be found in header.less -->
<nav class="navbar navbar-static-top">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
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
                    @if(session('login_user'))
                        <img src="{{asset('bower_components/admin-lte/dist/img/user2-160x160.jpg')}}" class="user-image" alt="User Image">
                        <span class="hidden-xs">{{session('login_user')}}</span>
                    @else
                        <span class="hidden-xs">Sign In or Sign Up </span>
                    @endif
                </a>
                <ul class="dropdown-menu">
                    <li class="user-footer">
                        @if(session('login_user'))
                            <div class="pull-left">
                                <span><b>Greeting {{session('login_user')}} !</b></span>
                            </div>
                            <div class="pull-right">
                                <a href="{{url('logout')}}" class="btn btn-default btn-flat">Sign out</a>
                            </div>
                        @else
                            <form action="{{url('login')}}" method="post">
                                {!! csrf_field() !!}
                                <div class="form-group has-feedback">
                                    <input type="text" class="form-inline" name="username" required="required" placeholder="User Name">
                                    <button type="submit" class="btn-primary btn-flat">Sign In</button>
                                </div>

                            </form>

                            <form action="{{url('register')}}" method="post">
                                {!! csrf_field() !!}
                                <div class="form-group has-feedback">
                                    <input type="text" class="form-inline" name="username" required="required" placeholder="User Name">
                                    <button type="submit" class="btn-primary btn-flat">Sign Up</button>
                                </div>
                            </form>
                        @endif
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