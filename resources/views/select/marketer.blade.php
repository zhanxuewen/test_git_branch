@extends('frame.body')
@section('title','Marketer')

@section('section')
    @foreach($rows as $query =>$row)
        <p>{{$query}}</p>
        <table border="1px">
            @for($i = 0; $i< count($row[0]);$i++)
                <tr>
                    @foreach($row as $value)
                        <td>{{$value[$i]}}</td>
                    @endforeach
                </tr>
            @endfor
        </table>
    @endforeach
@endsection