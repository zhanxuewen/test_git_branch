<!-- sidebar: style can be found in sidebar.less -->
<section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>
        {!! \App\Helper\BladeHelper::treeview('Export', [
            'School' => 'export/school',
            'Student' => 'export/student'
        ], 'fa-download') !!}
        {!! \App\Helper\BladeHelper::treeview('Select', [
            'Marketer' => 'select/marketer',
            'Labels' => 'select/labels',
            'Feedback' => 'select/feedback',
            'Quit Student' => route('select_quit_student'),
        ], 'fa-table') !!}
        {!! \App\Helper\BladeHelper::treeview('Database', [
            'Diff' => 'database/diff',
            'Table' => 'database/get/tableList',
            'Table Correct' => 'database/table_correct'
        ], 'fa-database') !!}
        {!! \App\Helper\BladeHelper::treeview('Redis', ['Throttle' => 'redis_throttle'], 'fa-file') !!}
        {!! \App\Helper\BladeHelper::treeview('Slow', ['Slow Mysql' => 'slow_mysql', 'Slow Rpc' => 'slow_rpc'], 'fa-spinner') !!}
        {!! \App\Helper\BladeHelper::treeview('Build', [
            'Rpc DB' => 'db/get/modelList',
            'Rpc Repo' => 'repo/get/repositoryList',
            'Rpc Service' => 'service/get/serviceList'
        ], 'fa-object-group') !!}
        {!! \App\Helper\BladeHelper::single_bar('Sql Analyze', 'analyze/select/no_group', 'fa-heartbeat') !!}
        {!! \App\Helper\BladeHelper::single_bar('Logs', 'logs', 'fa-hand-pointer-o') !!}
    </ul>
</section>
<!-- /.sidebar -->