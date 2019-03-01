<tr>
    <td @if(in_array($column->name, $keys)) class="text-bold" @endif>
        @if($column->after != '-') <i class="fa fa-plus text-green"></i> @endif
    {{$column->name}}
    {!! $column->extra != '-' ? '('.$column->extra.')' : '' !!}
    <td>{{$column->type}} {!! \App\Helper\BladeHelper::unsigned($column) !!}</td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($column->default, '-') !!}</td>
    <td>
        <i class="fa fa-lg @if($column->nullable == 1) fa-toggle-on @else fa-toggle-off @endif"></i>
    </td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($column->comment, '-') !!}</td>
</tr>