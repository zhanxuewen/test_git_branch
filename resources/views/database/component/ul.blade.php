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
</style>

<div class="col-sm-12 @if($hide) hide @endif" id="{{$key}}_list">
    <ul>
        @foreach($objects as $object)
            <li id="{{$object->id}}">
                <span class="{{$key}}_li show_li">{{$object->$field}}</span> <span>{{$object->info}}</span>
            </li>
        @endforeach
    </ul>
</div>