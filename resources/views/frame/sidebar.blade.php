<section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
        {!! \App\Helper\BladeHelper::single_bar('Dashboard', 'dashboard', 'fa-dashboard') !!}
        {!! \App\Helper\BladeHelper::treeview('Export', [
            'School' => 'export/school',
            'Student' => 'export/student',
            'Single' => 'export/single'
        ], 'fa-download') !!}
        {!! \App\Helper\BladeHelper::treeview('Select', [
            'Marketer' => 'select/marketer',
            'Labels' => 'select/labels',
            'Feedback' => 'select/feedback',
            'Abnormal' => 'select/abnormal',
            'Quit Student' => 'select/quit_student',
            'Yellow Account' => 'select/yellow_account',
            'Partner School' => 'select/partner_school',
        ], 'fa-table') !!}
        {!! \App\Helper\BladeHelper::treeview('Database', [
            'Diff' => 'database/diff',
            'Table' => 'database/get/tableList',
            'Table Correct' => 'database/table_correct'
        ], 'fa-database') !!}
        {!! \App\Helper\BladeHelper::treeview('Monitor', [
            'Table' => 'monitor/table',
            'Device' => 'monitor/device',
            'Order' => 'monitor/order',
            'Circle Table' => 'monitor/circleTable',
            'Zabbix' => 'monitor/zabbix',
            'Throttle' => 'monitor/throttle'
        ], 'fa-bar-chart') !!}
        {!! \App\Helper\BladeHelper::treeview('Slow', [
            'Slow Mysql' => 'slow_mysql',
            'Slow Rpc' => 'slow_rpc'
        ], 'fa-spinner') !!}
        {!! \App\Helper\BladeHelper::treeview('Build', [
            'Rpc DB' => 'db/get/modelList',
            'Rpc Repo' => 'repo/get/repositoryList',
            'Rpc Service' => 'service/get/serviceList'
        ], 'fa-object-group') !!}
        {!! \App\Helper\BladeHelper::treeview('Tool', [
            'Download' => 'tool/download',
            'Upload' => 'tool/upload'
        ], 'fa-legal') !!}
        {!! \App\Helper\BladeHelper::treeview('User', [
            'Account' => 'user/listAccount',
            'Role' => 'user/listRole',
            'Power' => 'user/listPower',
        ], 'fa-users') !!}
        {!! \App\Helper\BladeHelper::single_bar('Sql Analyze', 'analyze/select/no_group', 'fa-heartbeat') !!}
        {!! \App\Helper\BladeHelper::single_bar('Logs', 'logs', 'fa-hand-pointer-o') !!}
        {!! \App\Helper\BladeHelper::single_bar('Config', 'system/config', 'fa-cogs') !!}
    </ul>
</section>