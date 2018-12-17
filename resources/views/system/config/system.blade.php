<div class="col-sm-12">
    <h4>System Config</h4>
    @foreach($configs as $config)
        <form action="{{url('system/config')}}" class="col-sm-12 form-inline" method="POST">
            {!! csrf_field() !!}
            <input type="hidden" name="config_type" value="system">
            <input type="hidden" name="id" value="{{$config['id']}}">
            <div class="col-sm-12">
                <div class="form-group">
                    <label class="sr-only" for="type">类型</label>
                    <div class="input-group">
                        <div class="input-group-addon"><b>类型</b></div>
                        <input type="text" class="form-control" name="type" id="type" value="{{$config['type']}}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="sr-only" for="label">配置项</label>
                    <div class="input-group">
                        <div class="input-group-addon"><b>配置项</b></div>
                        <input type="text" class="form-control" name="label" id="label"
                               value="{{$config['label']}}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="sr-only" for="key">键</label>
                    <div class="input-group">
                        <div class="input-group-addon"><b>键</b></div>
                        <input type="text" class="form-control" name="key" id="key" value="{{$config['key']}}">
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <select name="json" class="form-control" id="json">
                            {!! \App\Helper\BladeHelper::buildIsJsonOption($config['value']) !!}
                        </select>
                    </label>
                </div>
            </div>
            <div class="form-group col-sm-12">
                <label for="value" class="sr-only">值</label>
                <div class="input-group col-sm-10">
                    <div class="input-group-addon" style="width: 20px"><b>值</b></div>
                    <input type="text" class="form-control" name="value" id="value"
                           @if(\App\Helper\BladeHelper::isJson($config['value']))
                           value="{!! \App\Helper\BladeHelper::buildJsonText($config['value']) !!}"
                           @else value="{{$config['value']}}" @endif >
                </div>
                <div class="col-sm-1 pull-right"><input class="btn btn-primary" type="submit" value="更新"></div>
            </div>
        </form>
        <div class="col-sm-12">
            <hr>
        </div>
    @endforeach
    {{--New Config--}}
    <form action="{{url('system/config')}}" class="col-sm-12 form-inline" method="POST">
        {!! csrf_field() !!}
        <input type="hidden" name="id" value="create">
        <div class="col-sm-12">
            <div class="form-group">
                <label class="sr-only" for="type">类型</label>
                <div class="input-group">
                    <div class="input-group-addon"><b>类型</b></div>
                    <input type="text" class="form-control" name="type" id="type" placeholder="Type">
                </div>
            </div>
            <div class="form-group">
                <label class="sr-only" for="label">配置项</label>
                <div class="input-group">
                    <div class="input-group-addon"><b>配置项</b></div>
                    <input type="text" class="form-control" name="label" id="label" placeholder="Label">
                </div>
            </div>
            <div class="form-group">
                <label class="sr-only" for="key">键</label>
                <div class="input-group">
                    <div class="input-group-addon"><b>键</b></div>
                    <input type="text" class="form-control" name="key" id="key" placeholder="Key">
                </div>
            </div>
            <div class="form-group">
                <label>
                    <select name="json" class="form-control" id="json">
                        <option value="0">不是Json</option>
                        <option value="1">是Json</option>
                    </select>
                </label>
            </div>
        </div>
        <div class="form-group col-sm-12">
            <label for="value" class="sr-only">值</label>
            <div class="input-group col-sm-10">
                <div class="input-group-addon" style="width: 20px"><b>值</b></div>
                <input type="text" class="form-control" name="value" id="value" placeholder="if json > K:V,K:V">
            </div>
            <div class="col-sm-1 pull-right"><input class="btn btn-success" type="submit" value="创建"></div>
        </div>
    </form>
</div>