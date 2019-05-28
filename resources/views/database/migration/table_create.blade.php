<tr>
    <td>
        @if($column->after != '-') <i class="fa fa-plus text-green"></i> @endif
    @if(isset($column->mig)) <span title="{{$column->mig}}">{{$column->name}}</span>
    @else {{$column->name}} @endif
    {!! $column->extra != '-' ? '('.$column->extra.')' : '' !!}
    <td>{{$column->type}} {!! \App\Helper\BladeHelper::unsigned($column) !!}</td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($column->default, '-') !!}</td>
    <td>
        @if($column->nullable == 1) <b>Null</b> @else - @endif
    </td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($column->comment, '-') !!}</td>
</tr>