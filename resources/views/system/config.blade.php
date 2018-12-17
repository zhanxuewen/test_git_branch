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
        <h4>Personal Config</h4>
        <div class="col-sm-4">
            <form action="{{url('system/config')}}" method="POST">
                {{csrf_field()}}
                <input type="hidden" name="config_type" value="personal">
                <div class="form-group col-sm-6">
                    <label for="conn">审计链接</label>
                    <select name="conn" id="conn" class="form-control">
                        <option value="dev" @if($conn == 'dev') selected @endif>Dev</option>
                        <option value="test" @if($conn == 'test') selected @endif>Test</option>
                    </select>
                </div>
                <div class="col-sm-12">
                    <input class="btn btn-primary" type="submit" value="设置">
                </div>
            </form>
        </div>
    </div>
@endsection
