<!DOCTYPE html>
<!--[if lt IE 7]>
<html class="lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>
<html class="lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>
<html class="lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en"><!--<![endif]-->
<head>
    <meta charset="utf-8">

    <!-- Viewport Metatag -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <!-- Plugin Stylesheets first to ease overrides -->
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/plugins/colorpicker/colorpicker.css') }}" media="screen">

    <!-- Required Stylesheets -->
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/bootstrap/css/bootstrap.min.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/fonts/ptsans/stylesheet.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/fonts/icomoon/style.css') }}" media="screen">

    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/mws-style.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/icons/icol16.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/icons/icol32.css') }}" media="screen">

    <!-- Demo Stylesheet -->
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/demo.css') }}" media="screen">

    <!-- jQuery-UI Stylesheet -->
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/jui/css/jquery.ui.all.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/jui/jquery-ui.custom.css') }}" media="screen">

    <!-- Theme Stylesheet -->
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/mws-theme.css') }}" media="screen">
    <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/themer.css') }}" media="screen">

    <title>MWS Admin - Table</title>
</head>

<body>

<!-- Themer (Remove if not needed) -->
<div id="mws-themer">
    <div id="mws-themer-content">
        <div id="mws-themer-ribbon"></div>
        <div id="mws-themer-toggle">
            <i class="icon-bended-arrow-left"></i>
            <i class="icon-bended-arrow-right"></i>
        </div>
        <div id="mws-theme-presets-container" class="mws-themer-section">
            <label for="mws-theme-presets">Color Presets</label>
        </div>
        <div class="mws-themer-separator"></div>
        <div id="mws-theme-pattern-container" class="mws-themer-section">
            <label for="mws-theme-patterns">Background</label>
        </div>
        <div class="mws-themer-separator"></div>
        <div class="mws-themer-section">
            <ul>
                <li class="clearfix"><span>Base Color</span>
                    <div id="mws-base-cp" class="mws-cp-trigger"></div>
                </li>
                <li class="clearfix"><span>Highlight Color</span>
                    <div id="mws-highlight-cp" class="mws-cp-trigger"></div>
                </li>
                <li class="clearfix"><span>Text Color</span>
                    <div id="mws-text-cp" class="mws-cp-trigger"></div>
                </li>
                <li class="clearfix"><span>Text Glow Color</span>
                    <div id="mws-textglow-cp" class="mws-cp-trigger"></div>
                </li>
                <li class="clearfix"><span>Text Glow Opacity</span>
                    <div id="mws-textglow-op"></div>
                </li>
            </ul>
        </div>
        <div class="mws-themer-separator"></div>
        <div class="mws-themer-section">
            <button class="btn btn-danger small" id="mws-themer-getcss">Get CSS</button>
        </div>
    </div>
    <div id="mws-themer-css-dialog">
        <form class="mws-form">
            <div class="mws-form-row">
                <div class="mws-form-item">
                    <textarea cols="auto" rows="auto" readonly="readonly"></textarea>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Themer End -->

<!-- Header -->
<div id="mws-header" class="clearfix">
    <!-- Logo Container -->
    <div id="mws-logo-container">
        <!-- Logo Wrapper, images put within this wrapper will always be vertically centered -->
        <div id="mws-logo-wrap">
            <img src="images/log.png" alt="admin">
        </div>
    </div>
    <!-- User Tools (notifications, logout, profile, change password) -->
</div>

<!-- Start Main Wrapper -->
<div id="mws-wrapper">

    <!-- Necessary markup, do not remove -->
    {{--<div id="mws-sidebar-stitch"></div>--}}
    <div id="mws-sidebar-bg"></div>

    <!-- Sidebar Wrapper -->
    <div id="mws-sidebar">

        <!-- Hidden Nav Collapse Button -->
        <div id="mws-nav-collapse">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Main Navigation -->
        <div id="mws-navigation">
            <ul>
                <li><a href=""><i class="icon-user"></i> <b style="color:yellowgreen;">SQL_AUTH</b></a></li>
                @foreach($auth_s as $auth)
                    <li @if($_auth==$auth) class="" @endif>
                        <a href="{!! url('/sql').'?auth='.$auth.(isset($_type)?'&type='.$_type:'') !!}">
                            <center>{{$auth}}</center>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <div id="mws-navigation">
            <ul>
                <li><a href=""><i class="icon-pencil"></i> <b style="color:yellowgreen;">SQL_TYPE</b></a></li>
                @foreach($types as $type)
                    <li @if($_type==$type) class="" @endif>
                        <a href="{!! url('/sql').'?type='.$type.(isset($_auth)?'&auth='.$_auth:'') !!}">
                            <center>{{$type}}</center>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Main Container Start -->
    <div id="mws-container" class="clearfix">

        <!-- Inner Container Start -->
        <div class="container">
            <div class="mws-panel grid_8">
                <div class="mws-panel-header">
                    <span><i class="icon-table"></i> <b>SQL_LOG</b></span>
                </div>
                @if(isset($sql))
                    <div class="mws-panel-body no-padding">
                        <span class=""><b>Query :</b></span> {{$sql->query}}<br><br>
                        <span class=""><b>Time :</b></span> {{$sql->time}} ms <br><br>
                        <span class=""><b>Created :</b></span> {{$sql->created_at}} <br><br>
                        <span class=""><b>Explain :</b></span>
                        <table class="mws-datatable-fn mws-table">
                            <thead>
                            <tr>
                                @foreach($sql->explain[0] as $key=> $value)
                                    <th>{{$key}}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($sql->explain as $rows)
                                <tr>
                                    @foreach($rows as $key=>$value)
                                        @if($key=='rows')
                                            <td @if (($value/$total[$rows->table])>0.05) bgcolor="#FA6B6B" @endif>
                                                <b>{{$value}} / {{$total[$rows->table]}} ({!! round($value/$total[$rows->table]*100,2) !!}%)</b>
                                            </td>
                                        @else
                                            <td>{{$value}}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mws-panel-header">
                        <span><i class="icon-table"></i> <b>Trace </b></span>
                    </div>
                    <div class="mws-panel-body no-padding">
                        <div class="mws-panel-body">
                            {!! dump(\App\Helper\Helper::convertQuot(json_decode($sql->trace,true))) !!}
                        </div>
                    </div>
                @else
                    <div class="mws-panel-body no-padding">
                        <table class="mws-datatable-fn mws-table">
                            <thead>
                            <tr>
                                <th width="50">count / time</th>
                                <th>sql</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($sql_s as $sql)
                                @if(!isset($sql->count))
                                    <tr>
                                        <td>
                                            <center>{{$sql->time}} <b>ms</b></center>
                                        </td>
                                        <td><a href="{!! url('/sql').'?id='.$sql->id !!}"
                                               style="color:black">{!! vsprintf(str_replace("?", "%s", $sql->query), str_replace('&apos;','\'', str_replace('&quot;','"',json_decode($sql->bindings)))) !!}</a></td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>
                                            <center>{{isset($sql->count)?$sql->count:1}}
                                            </center>
                                        </td>
                                        <td>
                                            <a href="{!! url('/sql').'?'.(isset($_type)?'type='.$_type.'&':'').(isset($_auth)?'auth='.$_auth.'&':'').'query='.($sql->query) !!}"
                                               style="color:black">{{$sql->query}}</a></td>
                                    </tr>
                                @endif

                            @endforeach
                            </tbody>
                        </table>
                    @endif


                    <!-- Panels End -->
                    </div>
                    <!-- Inner Container End -->

                    <!-- Footer -->


            </div>
            <!-- Main Container End -->

        </div>
        <div id="mws-footer">
            Vanthink 2018 Sql_log.
        </div>

        <!-- JavaScript Plugins -->
        <script src="js/libs/jquery-1.8.3.min.js"></script>
        <script src="js/libs/jquery.mousewheel.min.js"></script>
        <script src="js/libs/jquery.placeholder.min.js"></script>
        <script src="custom-plugins/fileinput.js"></script>

        <!-- jQuery-UI Dependent Scripts -->
        <script src="jui/js/jquery-ui-1.9.2.min.js"></script>
        <script src="jui/jquery-ui.custom.min.js"></script>
        <script src="jui/js/jquery.ui.touch-punch.js"></script>

        <!-- Plugin Scripts -->
        <script src="plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="plugins/colorpicker/colorpicker-min.js"></script>

        <!-- Core Script -->
        <script src="bootstrap/js/bootstrap.min.js"></script>
        <script src="js/core/mws.js"></script>

        <!-- Themer Script (Remove if not needed) -->
        <script src="js/core/themer.js"></script>

        <!-- Demo Scripts (remove if not needed) -->
        <script src="js/demo/demo.table.js"></script>
    </div>
</div>
</body>
</html>
