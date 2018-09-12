<!-- sidebar: style can be found in sidebar.less -->
<section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>
        {!! single_bar('Export', 'export', 'fa-download') !!}
        {!! treeview('Select', ['Marketer' => 'marketer', 'Quit Student' => 'quit_student', 'Labels' => 'labels'], 'fa-table') !!}
        {!! treeview('Database', ['Migration Diff' => 'migrations', 'Table Correct' => 'table_correct'], 'fa-database') !!}
        {!! treeview('Redis', ['Throttle' => 'redis_throttle'], 'fa-file') !!}
        {!! treeview('Slow', ['Slow Mysql' => 'slow_mysql', 'Slow Rpc' => 'slow_rpc'], 'fa-spinner') !!}
        {!! single_bar('Sql Analyze', 'analyze/select/no_group', 'fa-heartbeat') !!}
        {!! single_bar('Logs', 'logs', 'fa-hand-pointer-o') !!}
    </ul>
</section>
<!-- /.sidebar -->