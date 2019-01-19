@extends('frame.body')
@section('title','Config')

@section('section')
    @if(Auth::user()->role[0]->code == 'super_admin')
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
            <form action="{{url('system/config')}}" method="POST">
                {{csrf_field()}}
                <input type="hidden" name="config_type" value="personal">
                <div class="form-group col-sm-2">
                    <label for="conn">审计链接</label>
                    <select name="conn" id="conn" class="form-control">
                        <option value="dev" @if($conn == 'dev') selected @endif>Dev</option>
                        <option value="test" @if($conn == 'test') selected @endif>Test</option>
                        <option value="dev_shorthand" @if($conn == 'dev_shorthand') selected @endif>Dev Shorthand</option>
                    </select>
                </div>
                <div class="form-group col-sm-1">
                    <label for="per-page">分页条数</label>
                    <input type="text" name="perPage" id="per-page" value="{{$perPage}}" size="5"
                           style="ime-mode:disabled" onKeyUp="this.value = numberCheck(this.value, 100)"/>
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