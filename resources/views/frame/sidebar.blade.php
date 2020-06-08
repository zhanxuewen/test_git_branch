<section class="sidebar">
    <ul class="sidebar-menu" data-widget="tree">
        {!! \App\Helper\BladeHelper::single_bar('仪表', 'dashboard', 'fa-dashboard') !!}
        {!! \App\Helper\BladeHelper::treeView('导出', [
            'School' => 'export/school',
            'Student' => 'export/student',
            'Order Excels' => 'export/order/listExcels'
        ], 'fa-download') !!}
        {!! \App\Helper\BladeHelper::treeView('查询', [
            'Marketer' => 'select/marketer',
            'Labels' => 'select/labels',
            'Quit Student' => 'select/quit_student',
            '百项过开卡' => 'select/learningCards',
            'Partner School' => 'select/partner_school',
        ], 'fa-table') !!}
        {!! \App\Helper\BladeHelper::treeView('数据库', [
            'Diff' => 'database/diff',
            'DBWiki' => 'database/DBWiki',
            'Migration' => 'database/migration/history',
            'Table Correct' => 'database/table_correct'
        ], 'fa-database') !!}
        {!! \App\Helper\BladeHelper::treeView('调度', [
            'List' => 'dispatch/dispatcher/list',
            'Maps' => 'dispatch/dispatcher/maps',
        ], 'fa-steam-square') !!}
        {!! \App\Helper\BladeHelper::treeView('监控', [
            'Table' => 'monitor/table',
            'Device' => 'monitor/device',
            'Order' => 'monitor/order',
            'Circle Table' => 'monitor/circleTable',
            'Zabbix' => 'monitor/zabbix',
            'Schedule' => 'monitor/schedule',
            'Throttle' => 'monitor/throttle'
        ], 'fa-bar-chart') !!}
        {!! \App\Helper\BladeHelper::treeView('资源库', [
            '百项过题库' => 'bank/learning/search/testbank',
            '在线助教资源' => 'bank/core/resource',
            '在线助教修改小题' => 'bank/core/testbankEntity',
            '同步百项过小题' => 'bank/learning/sync/entity',
            '同步百项过题干' => 'bank/learning/sync/article',
            '同步题库到百项过' => 'bank/transmit/learning/testbank',
            '删除百项过题单' => 'bank/transmit/delete/bill',
        ], 'fa-bank') !!}
        {!! \App\Helper\BladeHelper::treeView('慢查询', [
            'Slow Mysql' => 'slow_mysql',
            'Slow Rpc' => 'slow_rpc'
        ], 'fa-spinner') !!}
        {!! \App\Helper\BladeHelper::treeView('架构', [
            'UML' => 'diagrams/uml'
        ], 'fa-object-group') !!}
        {!! \App\Helper\BladeHelper::treeView('工具', [
            'Download' => 'tool/download',
            'Upload' => 'tool/upload',
            'Query' => 'tool/query'
        ], 'fa-legal') !!}
        {!! \App\Helper\BladeHelper::treeView('用户与权限', [
            'Account' => 'user/listAccount',
            'Role' => 'user/listRole',
            'Power' => 'user/listPower',
        ], 'fa-users') !!}
        {!! \App\Helper\BladeHelper::single_bar('Sql 审计', 'analyze/select/no_group', 'fa-heartbeat') !!}
        {!! \App\Helper\BladeHelper::single_bar('日志', 'logs', 'fa-hand-pointer-o') !!}
        {!! \App\Helper\BladeHelper::single_bar('配置', 'system/config', 'fa-cogs') !!}
    </ul>
</section>