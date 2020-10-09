@extends('frame.body')
@section('title','Config')

@section('section')
    @if(\App\Helper\BladeHelper::checkSuper())
        @include('system.config.system')
        <div class="col-sm-12">
            <hr>
        </div>
    @endif

    <div class="col-sm-12">
        <h4>Personal Config
            <small>[config effect 24 hours.]</small>
        </h4>
        <div class="col-sm-12">
            <form action="{{url('system/config')}}" class="form-horizontal" method="POST">
                {{csrf_field()}}
                <input type="hidden" name="config_type" value="personal">
                <div class="form-group">
                    <label for="conn" class="col-sm-2 control-label">审计链接</label>
                    <div class="col-sm-3">
                        <select name="conn" id="conn" class="form-control">
                            @foreach($conn_s as $_conn => $label)
                                <option value="{{$_conn}}" @if($conn == $_conn) selected @endif>{{$label}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="per-page" class="col-sm-2 control-label">分页条数</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control" name="perPage" id="per-page" value="{{$perPage}}"
                               size="5"
                               style="ime-mode:disabled" onKeyUp="this.value = numberCheck(this.value, 100)"/>
                    </div>
                </div>
                <div class="col-sm-12">
                    <input class="btn btn-primary" type="submit" value="设置">
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function numberCheck(n, max) {
            n = n.replace(/[^\.\d]/g, '');
            n = n.replace('.', '');
            if (n > 100) n = max;
            return n;
        }
    </script>
@endsection