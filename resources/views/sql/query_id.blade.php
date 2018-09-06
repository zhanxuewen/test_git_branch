@extends('frame.body')
@section('title','Sql Analyze Query Id')

@section('section')
    <div id="scroll">
        <table>
            <tr>
                <th class="t-right">Query</th>
                <td>{{$sql->query}}</td>
            </tr>
            <tr>
                <th class="t-right">Time</th>
                <td>{{$sql->time}} ms</td>
            </tr>
            <tr>
                <th class="t-right">Auth</th>
                <td>{{$sql->auth}}</td>
            </tr>
            <tr>
                <th class="t-right">Created</th>
                <td>{{$sql->created_at}}</td>
            </tr>
        </table>
        <p><b>Explain</b></p>
        <table border="1">
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
        <p><b>Trace</b></p>
        <div>{!! dump($sql->trace) !!}</div>
        <br>
    </div>
@endsection