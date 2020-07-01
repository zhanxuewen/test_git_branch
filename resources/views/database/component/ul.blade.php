<style>
    .show_li {
        display: inline-block;
        border: 1px solid #acb0b3;
        padding: 3px 5px;
        margin: 3px auto;
        border-radius: 5px;
    }

    .show_li:hover {
        cursor: pointer;
    }

    .column_type {
        font-size: xx-small;
        color: gray;
        border: 1px solid gray;
    }
</style>

<div class="col-sm-12 @if($hide) hide @endif" id="{{$key}}_list">
    <ul>
        @foreach($objects as $object)
            <li id="{{$object->id}}">
                <span class="{{$key}}_li show_li">{{$object->$field}}</span>
                @if($key == 'column') <i class="column_type">{{$object->type}}</i> @endif
                <i class="hidden">{{ $object->info }}</i>
                <span>{!! App\Helper\BladeHelper::textCss($object->info) !!}</span>
            </li>
        @endforeach
    </ul>
</div>