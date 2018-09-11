@extends('frame.body')
@section('title','Sql Analyze Query Id')

@section('section')
    <div class="col-xs-12 col-sm-4">
        <table class="table table-bordered table-hover">
            <tr>
                <th class="text-right">Query</th>
                <td>{{$sql->query}}</td>
            </tr>
            <tr>
                <th class="text-right">Time</th>
                <td>{{$sql->time}} ms</td>
            </tr>
            <tr>
                <th class="text-right">Auth</th>
                <td>{{$sql->auth}}</td>
            </tr>
            <tr>
                <th class="text-right">Created</th>
                <td>{{$sql->created_at}}</td>
            </tr>
        </table>
    </div>
    <div class="col-xs-12 col-sm-9">
        <table class="table table-bordered table-hover">
            <caption>Explain</caption>
            <tr>
                @foreach($sql->explain[0] as $key=> $value)
                    <th>{{$key}}</th>
                @endforeach
            </tr>
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
        </table>
    </div>
    <div class="col-xs-12 col-sm-6">
        <h3>Trace</h3>
        {!! dump($sql->trace) !!}
    </div>
@endsection