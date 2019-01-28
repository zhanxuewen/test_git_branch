<!DOCTYPE html>
<html lang="html">

@include('frame.head')

<body class="hold-transition skin-blue sidebar-mini">

@include('frame.layout.modal')

<!-- Site wrapper -->
<div class="wrapper">

    <header class="main-header">
        @include('frame.header')
    </header>

    <!-- =============================================== -->

    <!-- Left side column. contains the sidebar -->
    <aside class="main-sidebar">
        @include('frame.sidebar')
    </aside>

    <!-- =============================================== -->

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <h1>
                @yield('title')
            </h1>
        </section>
        <!-- Main content -->
        <section class="content">
            <!-- Default box -->
            <div class="box row">
                @yield('section')
            </div>
            <!-- /.box -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->


    <!-- Control Sidebar -->
{{--<aside class="control-sidebar control-sidebar-dark">--}}
{{--@include('frame.control')--}}
{{--</aside>--}}
<!-- /.control-sidebar -->
    <!-- Add the sidebar's background. This div must be placed
         immediately after the control sidebar -->
    <div class="control-sidebar-bg"></div>
</div>

@include('frame.script')

@yield('script')

</body>
</html>