<!-- create -->
@php $create = $column['create'] @endphp
@php $update = $column['update'] @endphp
<tr>
    <td>
        @if($create->after != '-') + @endif {{$create->name}}
        {!! $create->extra != '-' ? '('.$create->extra.')' : '' !!}
        @foreach($column['update'] as $item)
            <br> <i>{{$item->name}}{!! $item->extra != '-' ? '('.$item->extra.')' : '' !!}
                <label title="{{$item->mig}}">(change)</label></i>
        @endforeach
    </td>
    <td>{{$create->type}}{!! \App\Helper\BladeHelper::unsigned($column) !!}
        @foreach($column['update'] as $item)
            <br> <i>{{$item->type}}{!! \App\Helper\BladeHelper::unsigned($item) !!}</i>
        @endforeach
    </td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($create->default, '-') !!}
        @foreach($column['update'] as $item)
            <br> <i>{!! \App\Helper\BladeHelper::equalOrBold($create->default, '-') !!}</i>
        @endforeach
    </td>
    <td>
        <i class="fa fa-lg @if($create->nullable == 1) fa-toggle-on @else fa-toggle-off @endif"></i>
        @foreach($column['update'] as $item)
            | <i class="fa fa-lg @if($create->nullable == 1) fa-toggle-on @else fa-toggle-off @endif"></i>
        @endforeach
    </td>
    <td>{!! \App\Helper\BladeHelper::equalOrBold($create->comment, '-') !!}
        @foreach($column['update'] as $item)
            <br> <i>{!! \App\Helper\BladeHelper::equalOrBold($create->comment, '-') !!}</i>
        @endforeach
    </td>
</tr>