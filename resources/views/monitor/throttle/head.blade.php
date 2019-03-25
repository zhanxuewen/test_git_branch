<div class="pull-right">
    <div class="btn-group" role="group">
        @foreach(['record','log'] as $section)
            <a class="btn btn-default @if($section == $_section) btn-primary active  @endif"
               href="{!! URL::current()."?section=".$section."&conn=".$_conn !!}">{{ucfirst($section)}}</a>
        @endforeach
    </div>
    <div class="btn-group" role="group">
        @foreach(['online','dev'] as $conn)
            <a class="btn btn-default @if($conn == $_conn) btn-primary active  @endif"
               href="{!! URL::current()."?section=".$_section."&conn=".$conn !!}">{{ucfirst($conn)}}</a>
        @endforeach
    </div>
</div>
<form class="form-inline" action="{!! URL::current() !!}" method="get">
    <input type="hidden" name="section" value="{{$_section}}">
    <input type="hidden" name="conn" value="{{$_conn}}">
    <div class="form-group">
        <label for="date">日期:</label>
        <input class="form-control" type="text" name="date" value="{{$date}}" id="date">
    </div>
    <button type="submit" class="btn btn-primary btn-flat">Submit</button>
</form>
<br>
@php $url = URL::current()."?section=".$_section."&conn=".$_conn  @endphp
<div>
    <a class="btn btn-default" href="{!! $url."&date={$date}&op=subDay" !!}"><< 前一天</a>
    <a class="btn btn-default" href="{!! $url."&date=".date('Y-m-d') !!}">今天</a>
    <a class="btn btn-default" href="{!! $url."&date={$date}&op=addDay" !!}">后一天 >></a>
</div>
<hr>