@extends('frame.body')
@section('title','Diff')

@section('section')
    <div class="col-sm-12">
        <div class="btn-group" role="group">
            @foreach($projects as $_project)
                <a class="btn btn-default @if($_project == $project) btn-primary active @endif"
                   href="{!! url('database/diff').'?project='.$_project.'&type='.$type !!}">{{ucfirst($_project)}}</a>
            @endforeach
        </div>
        <div class="btn-group" role="group">
            @foreach($types as $_type)
                <a class="btn btn-default @if($_type == $type) btn-primary active @endif"
                   href="{!! url('database/diff').'?project='.$project.'&type='.$_type !!}">Diff {{ucfirst($_type)}}</a>
            @endforeach
        </div>
    </div>
    <div class="col-sm-9">
        @foreach($rows as $conn => $row)
            @if($conn == 'dev') @continue @endif
            {!! \App\Helper\BladeHelper::oneColumnTable( array_diff($rows['dev'], $row),"Dev - " . ucfirst($conn)) !!}
            {!! \App\Helper\BladeHelper::oneColumnTable( array_diff($row, $rows['dev']),ucfirst($conn) . " - Dev") !!}
        @endforeach
    </div>
@endsection