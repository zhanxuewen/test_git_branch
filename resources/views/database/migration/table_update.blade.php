<!-- create -->
@php $create = $column['create'] @endphp
@php $update = $column['update'] @endphp
<tr>
    <td>
        @if($create->after != '-') + @endif {{$create->name}}
        {!! $create->extra != '-' ? '('.$create->extra.')' : '' !!}
        @foreach($update as $item)
            <br> <i>{{$item->name}}{!! $item->extra != '-' ? '('.$item->extra.')' : '' !!}
                <label title="{{$item->mig}}">(change)</label></i>
        @endforeach
    </td>
    <td>{{$create->type}}{!! \App\Helper\BladeHelper::unsigned($column) !!}
        @foreach($update as $item)
            <br> <i>{{$item->type}}{!! \App\Helper\BladeHelper::unsigned($item) !!}</i>
        @endforeach
    </td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($create->default, '-') !!}
        @foreach($update as $item)
            <br> <i>{!! \App\Helper\BladeHelper::equalOrBold($create->default, '-') !!}</i>
        @endforeach
    </td>
    <td>
        @if($create->nullable == 1) <b>Null</b> @else - @endif
        @foreach($update as $item)
            | @if($create->nullable == 1) <b>Null</b> @else - @endif
        @endforeach
    </td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($create->comment, '-') !!}
        @foreach($update as $item)
            <br> <i>{!! \App\Helper\BladeHelper::equalOrBold($create->comment, '-') !!}</i>
        @endforeach
    </td>
</tr>