@extends('frame.body')
@section('title','Migration History')

@section('section')
    <div class="col-sm-4">
        <div class="btn-group" role="group">
            @foreach(['core','learning'] as $project)
                <a class="btn btn-default @if($_project == $project) btn-primary active @endif"
                   href="{{URL::current().'?project='.$project}}">{{$project}}</a>
            @endforeach
        </div>
        <br><br>
        <div id="the-tree"></div>

        <hr>
        <div class="col-sm-12">
            <a class="btn btn-primary" href="#">Back to Top</a>
        </div>
    </div>
    <div class="col-sm-8 info-side">
        @if(!empty($table))
            <dl class="dl-horizontal">
                <dt>Module</dt>
                <dd>{{$table['module']}}</dd>
                <dt>Table</dt>
                <dd>{{$table['table']}}</dd>
                <dt>Engine</dt>
                <dd>{{$table['engine']}}</dd>
                <dt>Migration</dt>
                <dd>{{$table['migration']}}</dd>
            </dl>
            <table class="table table-bordered table-hover">
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Default</th>
                    <th>Nullable</th>
                    <th>Comment</th>
                </tr>
                @foreach($table['sort'] as $name => $k)
                    @if(!array_key_exists('update', $table['columns'][$name]))
                        @foreach($table['columns'][$name] as $column)
                            @component('database.migration.table_create', ['column' => $column])
                            @endcomponent
                        @endforeach
                    @else
                        @component('database.migration.table_update', ['column' => $table['columns'][$name]])
                        @endcomponent
                    @endif
                @endforeach
            </table>
            <h3>Index</h3>
            <ul class="list-unstyled">
                @foreach($table['index'] as $mig => $group)
                    <li>{{$mig}}</li>
                    <ul>
                        @foreach($group as $_type => $items)
                            @foreach($items as $item)
                                <li><b>{{$item['field']}}</b> : {{$item['type']}}</li>
                            @endforeach
                        @endforeach
                    </ul>
                @endforeach
            </ul>
        @endif
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            let json = JSON.parse('{!! $migrations !!}');
            let data = [];
            $.each(json, function (index) {
                let set = [];
                $.each(this, function () {
                    set.push({text: this.table_name});
                });
                data.push({
                    text: index,
                    state: {expanded: false,},
                    nodes: set
                });
            });
            let the_tree = $('#the-tree');
            the_tree.treeview({data: data});

            the_tree.on('nodeSelected', function (event, data) {
                clickNode(the_tree, event, data);
            });
            the_tree.on('nodeUnselected', function (event, data) {
                clickNode(the_tree, event, data);
            });
        });

        function clickNode(the_tree, event, data) {
            let id = data.nodeId;
            if (!data.hasOwnProperty('parentId')) {
                if (data.state.expanded) {
                    the_tree.treeview('collapseNode', [id, {silent: true, ignoreChildren: false}]);
                } else {
                    the_tree.treeview('collapseAll', {silent: true});
                    the_tree.treeview('expandNode', [id, {silent: true}]);
                }
            } else {
                the_tree.treeview('expandNode', [data.parentId, {silent: true}]);
                window.location.href = "{!! URL::current().'?project='.$_project.'&table=' !!}" + data.text;
            }
        }
    </script>
@endsection